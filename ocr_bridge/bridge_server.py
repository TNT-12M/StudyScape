#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
StudyScape Bridge Server — OCR Relay (公网服务器运行)
===================================================
双监听 (asyncio):
  1. 0.0.0.0:1200  TCP  — 接受家里云 Worker 主动连接
                         Challenge-Response 认证 (HMAC-SHA256)
                         未认证连接 → 伪装 HTTP 404 后关闭

  2. 127.0.0.1:1201 HTTP — 只允许 PHP-FPM localhost 提交任务
     POST /upload       {job_id, filename, file_b64, ...} → 入队
     GET  /status/{id}  返回任务状态 + 结果 JSON

链路:
  家里云 Worker ──TCP(1200)──▶ bridge_server ──队列──▶ PHP-FPM ──HTTP(1201)──▶ admin 页面
       ▲                                                          │
       └────── 返回 JSON 结果 ◀───────────────────────────────────┘

启动: systemctl start studyscape-bridge (公网服务器)
"""

import asyncio
import hmac
import hashlib
import json
import time
import os
import secrets
import struct
import base64
import logging
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse

# ======================= 配置 =======================
TCP_PORT = 1200          # Worker 连接端口 (公网)
HTTP_PORT = 1201         # PHP 本地接口 (仅 localhost)
KEY_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "bridge_key.txt")
WORKER_TIMEOUT = 30      # Worker 心跳超时 (秒)
RESULT_TTL = 14400        # 结果缓存 4 小时 (秒) — 公网机磁盘小, 且 PHP 端捞完就删了

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s",
    datefmt="%H:%M:%S",
)
log = logging.getLogger("bridge")

# ======================= 密钥加载 =======================
if not os.path.exists(KEY_FILE):
    log.error(f"密钥文件不存在: {KEY_FILE}")
    log.error("生成: echo '$(python3 -c \"import secrets; print(secrets.token_hex(32))\")' > bridge_key.txt")
    raise SystemExit(1)

with open(KEY_FILE, "r") as f:
    SHARED_KEY = f.read().strip()

if len(SHARED_KEY) < 32:
    log.error("密钥太短 (需要 >= 32 字符)")
    raise SystemExit(1)

# ======================= 帧协议 =======================
# 帧 = [4字节 big-endian 长度][JSON payload]
# 认证握手:
#   Worker → Server  : HANDSHAKE {nonce_client}
#   Server → Worker  : CHALLENGE {nonce_server}
#   Worker → Server  : AUTH {hmac}  hmac = HMAC-SHA256(key, nonce_client + nonce_server)
#   Server → Worker  : OK  / FAIL

class FrameProto:
    @staticmethod
    async def recv(reader):
        hdr = await reader.readexactly(4)
        size = struct.unpack(">I", hdr)[0]
        if size == 0:
            return None
        buf = b""
        while len(buf) < size:
            chunk = await reader.read(size - len(buf))
            if not chunk:
                return None
            buf += chunk
        return json.loads(buf.decode("utf-8"))

    @staticmethod
    async def send(writer, obj):
        data = json.dumps(obj).encode("utf-8")
        writer.write(struct.pack(">I", len(data)) + data)
        await writer.drain()


# ======================= 全局状态 =======================
class BridgeState:
    def __init__(self, persist_dir=None):
        self.worker_reader = None
        self.worker_writer = None
        self.worker_last_ping = 0
        self.worker_lock = asyncio.Lock()
        self.queue = {}     # job_id → job dict {enqueued_at, status, data, result}
        self.next_id = 0
        self.lock = asyncio.Lock()
        # ★ 磁盘持久化: bridge 重启不丢 jobs
        if persist_dir is None:
            persist_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "_bridge_jobs")
        self.persist_dir = persist_dir
        os.makedirs(self.persist_dir, exist_ok=True)
        self._load_persisted()

    def _job_file(self, jid):
        return os.path.join(self.persist_dir, f"{jid}.json")

    def _persist(self, jid, job):
        """写磁盘 —— queued/processing 必须完整存 data (含 file_b64), done/failed 排除 data 省空间"""
        if job.get("status") in ("done", "failed"):
            slim = {k: v for k, v in job.items() if k != "data"}
        else:
            slim = job  # queued/processing 完整存, 重启恢复后 worker 能继续干
        try:
            with open(self._job_file(jid), "w", encoding="utf-8") as f:
                json.dump(slim, f, ensure_ascii=False)
        except Exception as e:
            logging.warning(f"持久化失败 {jid}: {e}")

    def _delete_persist(self, jid):
        try:
            fp = self._job_file(jid)
            if os.path.exists(fp):
                os.remove(fp)
        except Exception:
            pass

    def _load_persisted(self):
        """启动时从磁盘加载遗留 jobs
        
        processing 状态的 job 在 bridge 重启后 worker 那边也丢了 → 改 failed
        queued 的继续等 worker 拉
        """
        loaded = 0
        try:
            for fn in os.listdir(self.persist_dir):
                if not fn.endswith(".json"):
                    continue
                fp = os.path.join(self.persist_dir, fn)
                try:
                    with open(fp, "r", encoding="utf-8") as f:
                        job = json.load(f)
                    jid = job.get("job_id") or fn[:-5]
                    if job.get("status") == "processing":
                        job["status"] = "failed"
                        job["error"] = "bridge 重启恢复, processing 任务已失效"
                        job["finished_at"] = job.get("finished_at") or time.time()
                        self._persist(jid, job)
                    self.queue[jid] = job
                    self.next_id = max(self.next_id, int(jid.split("_")[-1]) if "_" in jid else 0)
                    loaded += 1
                except Exception as e:
                    logging.warning(f"加载遗留 job {fn} 失败: {e}")
        except Exception as e:
            logging.warning(f"扫描持久化目录失败: {e}")
        if loaded:
            logging.info(f"★ 从磁盘加载 {loaded} 个遗留 jobs")

    async def enqueue(self, job):
        async with self.lock:
            self.next_id += 1
            jid = f"job_{int(time.time())}_{self.next_id}"
            job["job_id"] = jid
            job["status"] = "queued"
            job["enqueued_at"] = time.time()
            self.queue[jid] = job
            self._persist(jid, job)
        return jid

    async def dequeue(self):
        """Worker 拉取一个待处理任务。返回 (jid, job_data) 或 None"""
        async with self.lock:
            queued_sorted = sorted(
                [(jid, job) for jid, job in self.queue.items() if job["status"] == "queued"],
                key=lambda x: x[1]["enqueued_at"]
            )
            if not queued_sorted:
                return None
            jid, job = queued_sorted[0]
            job["status"] = "processing"
            job["started_at"] = time.time()
            job["stage"] = "mineru_starting"
            self._persist(jid, job)
            return jid, job

    async def set_result(self, jid, result_json, error=None):
        async with self.lock:
            if jid in self.queue:
                self.queue[jid]["status"] = "done" if error is None else "failed"
                self.queue[jid]["result"] = result_json
                self.queue[jid]["error"] = error
                self.queue[jid]["stage"] = "done" if error is None else "failed"
                self.queue[jid]["finished_at"] = time.time()
                self._persist(jid, self.queue[jid])

    async def update_stage(self, jid, stage, progress=""):
        """Worker 上报进度阶段 + 可选进度提示 (如 '3/10 页')"""
        async with self.lock:
            if jid in self.queue:
                self.queue[jid]["stage"] = stage
                if progress:
                    self.queue[jid]["progress"] = progress
                self._persist(jid, self.queue[jid])

    async def get_status(self, jid):
        async with self.lock:
            job = self.queue.get(jid)
            if not job:
                return None
            # 计算队列位置: 当前 processing = 0, queued 按 enqueued_at 排
            qpos = 0
            if job["status"] == "queued":
                all_queued = sorted(
                    [j for j in self.queue.values() if j["status"] == "queued"],
                    key=lambda j: j["enqueued_at"]
                )
                for i, qj in enumerate(all_queued):
                    if qj.get("job_id") == jid:
                        qpos = i + 1
                        break
            out = dict(job)
            out["queue_position"] = qpos
            # 不回传 data (base64)
            out.pop("data", None)
            return out

    async def cleanup_old(self):
        now = time.time()
        async with self.lock:
            for jid in list(self.queue.keys()):
                job = self.queue[jid]
                if job["status"] in ("done", "failed"):
                    if now - job.get("finished_at", 0) > RESULT_TTL:
                        del self.queue[jid]
                        self._delete_persist(jid)
                elif job["status"] == "queued":
                    if now - job.get("enqueued_at", 0) > 600:
                        # 10 分钟没被 worker 取走 → 超时
                        job["status"] = "failed"
                        job["error"] = "timeout: worker unavailable"
                        job["finished_at"] = now
                        self._persist(jid, job)

    async def push_task(self, jid):
        """把 queue 里的 jid 任务推给当前 worker"""
        async with self.worker_lock:
            if not self.worker_writer:
                return False
            job = self.queue.get(jid)
            if not job:
                return False
            await FrameProto.send(self.worker_writer, {
                "type": "TASK",
                "job_id": jid,
                "data": job,
            })
            log.info(f"  📤 推送任务 {jid} 给 worker")
            return True


STATE = BridgeState()

# PDF 缓存目录 (给 api.php 直接读)
PDF_CACHE_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "_pdf_cache")
os.makedirs(PDF_CACHE_DIR, exist_ok=True)


def _save_pdf(jid, pdf_b64):
    """把 worker 回传的 PDF base64 落盘"""
    try:
        raw = base64.b64decode(pdf_b64)
        fp = os.path.join(PDF_CACHE_DIR, f"{jid}.pdf")
        with open(fp, "wb") as f:
            f.write(raw)
        log.info(f"  💾 PDF 落盘: {fp} ({len(raw)//1024}KB)")
    except Exception as e:
        log.warning(f"  PDF 落盘失败 {jid}: {e}")

# ======================= TCP 认证 =======================
async def handle_worker(reader, writer):
    addr = writer.get_extra_info("peername")
    log.info(f"TCP 连接来自 {addr}")

    # --- Challenge-Response 认证 ---
    try:
        # Step 1: 等 Worker 发 HANDSHAKE
        msg = await asyncio.wait_for(FrameProto.recv(reader), timeout=5)
        if not msg or msg.get("type") != "HANDSHAKE":
            # 伪装成 HTTP 404 让扫端口的人以为是 HTTP server 但路径错了
            writer.write(b"HTTP/1.1 404 Not Found\r\nContent-Length: 0\r\nConnection: close\r\n\r\n")
            await writer.drain()
            writer.close()
            log.warning(f"  {addr} 非 HANDSHAKE, 返回 HTTP 404 后关闭")
            return

        nonce_client = msg.get("nonce", "")
        if not nonce_client or len(nonce_client) < 8:
            writer.close()
            log.warning(f"  {addr} 非法 nonce")
            return

        # Step 2: Server 发 CHALLENGE
        nonce_server = secrets.token_hex(16)
        await FrameProto.send(writer, {"type": "CHALLENGE", "nonce": nonce_server})

        # Step 3: 等 AUTH
        msg = await asyncio.wait_for(FrameProto.recv(reader), timeout=5)
        if not msg or msg.get("type") != "AUTH":
            writer.close()
            log.warning(f"  {addr} 非 AUTH")
            return

        client_hmac = msg.get("hmac", "")
        expected = hmac.new(SHARED_KEY.encode(),
                           (nonce_client + nonce_server).encode(),
                           hashlib.sha256).hexdigest()

        if not hmac.compare_digest(client_hmac, expected):
            writer.close()
            log.warning(f"  {addr} HMAC 不匹配 → 拒绝")
            return

        # --- 认证成功 ---
        await FrameProto.send(writer, {"type": "OK"})
        log.info(f"  ✅ Worker {addr} 认证通过")

        async with STATE.worker_lock:
            if STATE.worker_writer:
                # 踢掉旧 worker
                try: STATE.worker_writer.close()
                except: pass
            STATE.worker_reader = reader
            STATE.worker_writer = writer
            STATE.worker_last_ping = time.time()

        # --- Worker 主循环 ---
        async def worker_loop():
            while True:
                try:
                    msg = await asyncio.wait_for(FrameProto.recv(reader), timeout=20)
                    if msg is None:
                        break

                    mtype = msg.get("type")
                    if mtype == "PING":
                        await FrameProto.send(writer, {"type": "PONG"})
                        STATE.worker_last_ping = time.time()

                    elif mtype == "PROGRESS":
                        jid = msg.get("job_id")
                        stage = msg.get("stage", "")
                        progress = msg.get("progress", "")
                        if jid and stage:
                            await STATE.update_stage(jid, stage, progress)
                            log.info(f"  📊 {jid} stage={stage}{' progress='+progress if progress else ''}")

                    elif mtype == "RESULT":
                        jid = msg.get("job_id")
                        result = msg.get("result")
                        error = msg.get("error")
                        await STATE.set_result(jid, result, error)
                        # PDF 导出结果直接落盘 (方便 api.php 直接读)
                        if result and result.get("pdf_b64"):
                            _save_pdf(jid, result["pdf_b64"])
                        log.info(f"  📦 Worker 完成 {jid}: status={'OK' if error is None else 'FAIL'}")

                    elif mtype == "BYE":
                        break

                except asyncio.TimeoutError:
                    if time.time() - STATE.worker_last_ping > WORKER_TIMEOUT:
                        log.warning(f"  Worker 心跳超时")
                        break
                except Exception as e:
                    log.error(f"  Worker loop 错误: {e}")
                    break

            log.info(f"  Worker {addr} 断开")
            async with STATE.worker_lock:
                if STATE.worker_writer is writer:
                    STATE.worker_reader = None
                    STATE.worker_writer = None

        await worker_loop()

    except Exception as e:
        log.error(f"TCP handler 异常 {addr}: {e}")
    finally:
        try: writer.close()
        except: pass


# ======================= HTTP 1201 (PHP 接口) =======================
def _worker_online():
    """判断家里云 Worker 是否在线: 已认证 + 最近 WORKER_TIMEOUT 内有心跳"""
    return bool(STATE.worker_writer) and (time.time() - STATE.worker_last_ping) < WORKER_TIMEOUT


class PHPHandler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        pass  # 静默（主日志已覆盖）

    def _send(self, code, obj):
        body = json.dumps(obj, ensure_ascii=False).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()
        self.wfile.write(body)

    def do_OPTIONS(self):
        # CORS preflight
        self.send_response(200)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()

    def do_GET(self):
        path = urlparse(self.path).path
        # 健康检查
        if path == "/health":
            online = _worker_online()
            self._send(200, {
                "bridge": "ok",
                "worker_online": online,
                "seconds_since_last_ping": int(time.time() - STATE.worker_last_ping) if STATE.worker_last_ping else None,
                "queued_jobs": sum(1 for j in STATE.queue.values() if j["status"] in ("queued", "processing")),
            })
            return
        # 查状态
        if path.startswith("/status/"):
            jid = path[len("/status/"):]
            job = asyncio.run_coroutine_threadsafe(STATE.get_status(jid), event_loop).result()
            if not job:
                self._send(404, {"error": "任务不存在", "job_id": jid})
                return
            qpos = int(job.get("queue_position", 0))
            # 清理敏感字段（file_b64 不回传）
            out = {
                "job_id": job["job_id"],
                "status": job["status"],   # queued / processing / done / failed
                "filename": job.get("filename", ""),
                "error": job.get("error"),  # 失败时的错误字符串
                "result": job.get("result"),  # done 时的 Contract B JSON
                "stage": job.get("stage", ""),          # ★ 细粒度阶段
                "progress": job.get("progress", ""),    # ★ 进度提示 (如 "3/10 页")
                "queue_position": qpos,                 # ★ 队列位置
            }
            self._send(200, out)
            return
        self._send(404, {"error": f"未知端点: {path}", "available": ["/health", "/status/{job_id}"]})

    def do_POST(self):
        path = urlparse(self.path).path
        length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(length) if length else b""

        if path == "/upload":
            # ① JSON 解析
            try:
                payload = json.loads(body.decode("utf-8")) if body else {}
            except json.JSONDecodeError as e:
                self._send(400, {
                    "error": "JSON 格式错误",
                    "detail": str(e),
                    "hint": "检查 Content-Type 是否为 application/json",
                })
                return

            # ② 必需字段
            filename = payload.get("filename", "").strip()
            file_b64 = payload.get("file_b64", "").strip()
            subject = payload.get("subject", "")
            education_level = payload.get("education_level", "")
            paper_name = payload.get("paper_name", "")

            if not filename:
                self._send(400, {"error": "缺少 filename 字段", "hint": "需要传原始 PDF/图片文件名"})
                return
            if not file_b64:
                self._send(400, {"error": "缺少 file_b64 字段", "hint": "需要传文件的 base64 编码"})
                return

            # ③ base64 可解码性验证
            try:
                raw = base64.b64decode(file_b64)
            except Exception as e:
                self._send(400, {"error": "file_b64 解码失败", "detail": str(e)})
                return

            # ④ 文件大小上限 (50MB)
            max_bytes = 50 * 1024 * 1024
            if len(raw) > max_bytes:
                self._send(413, {"error": f"文件过大 ({len(raw)//1024//1024}MB), 上限 50MB"})
                return

            # ⑤ Worker 在线检查
            if not _worker_online():
                online_flag = bool(STATE.worker_writer)
                last_ping = STATE.worker_last_ping
                detail_parts = []
                if not online_flag:
                    detail_parts.append("家里云 Worker 尚未连入公网 bridge")
                else:
                    elapsed = int(time.time() - last_ping)
                    detail_parts.append(f"Worker 心跳已断 {elapsed}s (超时 {WORKER_TIMEOUT}s)")
                self._send(503, {
                    "error": "worker offline",
                    "detail": "; ".join(detail_parts),
                    "hint": "家里云检查: sudo systemctl status studyscape-bridge-client",
                })
                return

            # ⑥ 推断文件类型
            ftype = "pdf"
            if raw[:8] == b"%PDF-":
                ftype = "pdf"
            elif raw[:3] == b"\xff\xd8\xff":
                ftype = "jpg"
            elif raw[:8] == b"\x89PNG\r\n\x1a\n":
                ftype = "png"
            elif raw[:4] == b"GIF8":
                ftype = "gif"
            elif raw[:12] == b"RIFF" and raw[8:12] == b"WEBP":
                ftype = "webp"
            log.info(f"收到上传: {filename} ({ftype}, {len(raw)//1024}KB) → subject={subject}")

            # ⑦ 入队并推送
            job = {
                "filename": filename,
                "file_b64": file_b64,
                "ftype": ftype,
                "subject": subject,
                "education_level": education_level,
                "paper_name": paper_name or os.path.splitext(filename)[0],
            }
            jid = asyncio.run_coroutine_threadsafe(STATE.enqueue(job), event_loop).result()

            # 立即推送 + watchdog 保险 (queued 超过 60s 自动重推)
            asyncio.run_coroutine_threadsafe(STATE.push_task(jid), event_loop)

            self._send(202, {
                "job_id": jid,
                "status": "queued",
                "message": "任务已提交, 等待家里云处理",
            })
            return

        if path == "/export_pdf":
            """接收 PDF 导出请求: 题目 JSON → 家里云生成 PDF → 回传 base64"""
            try:
                payload = json.loads(body.decode("utf-8")) if body else {}
            except json.JSONDecodeError as e:
                self._send(400, {"error": "JSON 格式错误", "detail": str(e)})
                return

            export_input = payload.get("export_input")
            if not export_input or not isinstance(export_input, dict):
                self._send(400, {"error": "缺少 export_input 字段"})
                return
            questions = export_input.get("questions", [])
            if not questions:
                self._send(400, {"error": "questions 为空"})
                return

            if not _worker_online():
                self._send(503, {"error": "worker offline"})
                return

            job = {"task_type": "export_pdf", "export_input": export_input}
            jid = asyncio.run_coroutine_threadsafe(STATE.enqueue(job), event_loop).result()

            # 立即推送 + watchdog 保险
            asyncio.run_coroutine_threadsafe(STATE.push_task(jid), event_loop)

            self._send(202, {"job_id": jid, "status": "queued", "message": "PDF 生成任务已提交"})
            return

        self._send(404, {"error": f"未知端点: {path}", "available": ["/upload (POST)", "/export_pdf (POST)"]})


# ======================= 主入口 =======================
async def cleanup_loop():
    while True:
        await asyncio.sleep(60)
        await STATE.cleanup_old()


async def watchdog_loop():
    """看门狗: worker 在线但 queued jobs 超过 60s 没派发 → 重新推"""
    RESEND_GRACE = 60   # queued 超过 60s 就重推
    SCAN_INTERVAL = 30  # 每 30s 扫一次
    _resent = {}  # jid → 上次重推时间
    MAX_RESEND = 3  # 最多重推 3 次

    log.info(f"🐕 watchdog 启动 (每 {SCAN_INTERVAL}s 扫, queued>{RESEND_GRACE}s 重推)")

    # 首次立即扫 —— 重启后遗留的 queued jobs 可能已经等很久了
    while True:
        try:
            async with STATE.lock:
                queued = [
                    (jid, job) for jid, job in STATE.queue.items()
                    if job["status"] == "queued"
                ]
            if queued:
                log.info(f"🐕 扫描到 {len(queued)} 个 queued jobs")
            else:
                _resent.clear()

            if queued and _worker_online():
                now = time.time()
                for jid, job in queued:
                    enqueued_at = job.get("enqueued_at", 0)
                    waited = now - enqueued_at
                    last_resent = _resent.get(jid, 0)
                    resent_count = _resent.get(f"{jid}_count", 0)

                    if waited > RESEND_GRACE and now - last_resent > 30 and resent_count < MAX_RESEND:
                        await STATE.push_task(jid)
                        _resent[jid] = now
                        _resent[f"{jid}_count"] = resent_count + 1
                        log.warning(f"🐕 watchdog: {jid} 排队 {int(waited)}s, 第 {resent_count+1} 次重推")
        except Exception as e:
            log.error(f"watchdog 异常: {e}")

        await asyncio.sleep(SCAN_INTERVAL)


async def main():
    global event_loop
    event_loop = asyncio.get_running_loop()

    # TCP 1200 server
    tcp_server = await asyncio.start_server(
        handle_worker, "0.0.0.0", TCP_PORT
    )
    log.info(f"✅ TCP Worker 端口: 0.0.0.0:{TCP_PORT}")

    # HTTP 1201 server (仅 localhost)
    http_server = ThreadingHTTPServer(("127.0.0.1", HTTP_PORT), PHPHandler)
    import threading
    threading.Thread(target=http_server.serve_forever, daemon=True).start()
    log.info(f"✅ HTTP PHP 接口: 127.0.0.1:{HTTP_PORT} (仅 localhost)")

    # 清理定时
    asyncio.create_task(cleanup_loop())

    # 看门狗: queued jobs 自动重推
    asyncio.create_task(watchdog_loop())

    async with tcp_server:
        await tcp_server.serve_forever()


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        log.info("退出")
