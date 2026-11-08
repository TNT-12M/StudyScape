#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
StudyScape Bridge Server — OCR Relay (公网服务器运行)
===================================================
双监听 (asyncio):
  1. 0.0.0.0:1200  WebSocket — 家里云 Worker 主动连接
                                Challenge-Response 认证 (HMAC-SHA256)
                                非 WS 连接 → 伪装 HTTP 404 后关闭

  2. 127.0.0.1:1201 HTTP — 只允许 PHP-FPM localhost 提交任务
     POST /upload       {filename, file_b64, ...} → 入队
     POST /export_pdf   {export_input} → 家里云生成 PDF
     GET  /status/{id}  返回任务状态 + 结果 JSON
     GET  /health       健康检查

链路:
  家里云 Worker ──WebSocket(1200)──▶ bridge_server ──队列──▶ PHP-FPM ──HTTP(1201)──▶ admin
       ▲                                                          │
       └────── 返回 JSON 结果 ◀───────────────────────────────────┘

★ 自实现 WebSocket (零第三方依赖, 纯 asyncio)
★ 认证帧协议沿用原版: [4字节 big-endian 长度][JSON payload]

启动: systemctl start studyscape-bridge (公网服务器 207.57.127.180)
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
import hashlib as _hashlib
import base64 as _b64
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse

# ======================= 配置 =======================
WS_PORT = 1200           # Worker WebSocket 端口 (公网)
HTTP_PORT = 1201         # PHP 本地接口 (仅 localhost)
KEY_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "bridge_key.txt")
WORKER_TIMEOUT = 30      # Worker 心跳超时 (秒)
RESULT_TTL = 14400       # done/failed 结果保留 4h 后清
QUEUED_TIMEOUT = 600     # queued 10min 没被 worker 取 → failed
QUEUED_MAX_HOURS = 2     # queued 超过 2h 直接清
PDF_CACHE_TTL = 3600     # _pdf_cache 1h 没被访问就删
MAX_QUEUED = 50          # 队列上限

# WebSocket 魔数
WS_MAGIC = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"
OPCODE_TEXT = 0x01
OPCODE_BINARY = 0x02
OPCODE_CLOSE = 0x08
OPCODE_PING = 0x09
OPCODE_PONG = 0x0A

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


# ======================= WebSocket 握手 + 帧 =======================
async def ws_handshake(reader, writer):
    """读 HTTP Upgrade 请求 → 回 101 → 返回 (子协议, None 表示握手失败已关闭)"""
    # 读请求头 (最多 8KB)
    req_buf = b""
    while b"\r\n\r\n" not in req_buf:
        chunk = await asyncio.wait_for(reader.read(4096), timeout=5)
        if not chunk:
            return None
        req_buf += chunk
        if len(req_buf) > 8192:
            return None

    req_text = req_buf.decode("utf-8", errors="replace")
    lines = req_text.split("\r\n")
    if not lines or "Upgrade: websocket" not in req_text.lower():
        # 非 WS → 伪装 404
        writer.write(b"HTTP/1.1 404 Not Found\r\nContent-Length: 0\r\nConnection: close\r\n\r\n")
        await writer.drain()
        writer.close()
        return None

    # 找 Sec-WebSocket-Key
    ws_key = None
    for line in lines[1:]:
        if line.lower().startswith("sec-websocket-key:"):
            ws_key = line.split(":", 1)[1].strip()
            break

    if not ws_key:
        writer.close()
        return None

    # 计算 Sec-WebSocket-Accept
    accept_key = _b64.b64encode(
        _hashlib.sha1((ws_key + WS_MAGIC).encode()).digest()
    ).decode()

    resp = (
        b"HTTP/1.1 101 Switching Protocols\r\n"
        b"Upgrade: websocket\r\n"
        b"Connection: Upgrade\r\n"
        b"Sec-WebSocket-Accept: " + accept_key.encode() + b"\r\n"
        b"\r\n"
    )
    writer.write(resp)
    await writer.drain()
    return "ws"


async def ws_read_frame(reader):
    """读一个 WebSocket 帧, 返回 (opcode, payload_bytes)
    自动处理 MASK (客户端发来的帧必带 MASK)"""
    hdr = await reader.readexactly(2)
    opcode = hdr[0] & 0x0F
    masked = (hdr[1] & 0x80) != 0
    length = hdr[1] & 0x7F

    if length == 126:
        length_bytes = await reader.readexactly(2)
        length = struct.unpack(">H", length_bytes)[0]
    elif length == 127:
        length_bytes = await reader.readexactly(8)
        length = struct.unpack(">Q", length_bytes)[0]

    mask_key = await reader.readexactly(4) if masked else b""
    payload = await reader.readexactly(length) if length > 0 else b""

    if masked and payload:
        payload = bytes(b ^ mask_key[i % 4] for i, b in enumerate(payload))

    return opcode, payload


async def ws_write_frame(writer, opcode, payload_bytes):
    """写一个 WebSocket 帧 (服务端发 → 不带 MASK)"""
    length = len(payload_bytes)
    frame = bytearray()
    frame.append(0x80 | (opcode & 0x0F))  # FIN + opcode
    if length < 126:
        frame.append(length)
    elif length < 65536:
        frame.append(126)
        frame.extend(struct.pack(">H", length))
    else:
        frame.append(127)
        frame.extend(struct.pack(">Q", length))
    frame.extend(payload_bytes)
    writer.write(bytes(frame))
    await writer.drain()


async def ws_text_recv(reader):
    """读一个 text frame, 返回 str (自动处理 ping/pong/close)"""
    while True:
        opcode, payload = await ws_read_frame(reader)
        if opcode == OPCODE_TEXT:
            return payload.decode("utf-8")
        elif opcode == OPCODE_BINARY:
            return payload  # 暂不支持 binary payload, 但也不拒绝
        elif opcode == OPCODE_PING:
            await ws_write_frame(writer, OPCODE_PONG, payload)
        elif opcode == OPCODE_CLOSE:
            return None  # 告诉上层连接关了
        elif opcode == OPCODE_PONG:
            pass


async def ws_text_send(writer, text):
    """发一个 text frame"""
    await ws_write_frame(writer, OPCODE_TEXT, text.encode("utf-8"))


# ======================= 认证帧协议 (沿用原版) =======================
# 帧 = [4字节 big-endian 长度][JSON payload]
# 认证握手:
#   Worker → Server  : HANDSHAKE {nonce_client}
#   Server → Worker  : CHALLENGE {nonce_server}
#   Worker → Server  : AUTH {hmac}
#   Server → Worker  : OK  / FAIL

class FrameProto:
    """JSON 帧 — 通过 WebSocket text frame 承载"""
    @staticmethod
    async def recv(reader):
        text = await ws_text_recv(reader)
        if text is None:
            return None
        # WebSocket text frame 直接就是 JSON (不再有 4 字节长度头)
        return json.loads(text)

    @staticmethod
    async def send(writer, obj):
        text = json.dumps(obj).encode("utf-8")
        await ws_write_frame(writer, OPCODE_TEXT, text)


# ======================= 全局状态 (完全沿用原版) =======================
class BridgeState:
    def __init__(self, persist_dir=None):
        self.worker_reader = None
        self.worker_writer = None
        self.worker_last_ping = 0
        self.worker_lock = asyncio.Lock()
        self.queue = {}
        self.next_id = 0
        self.lock = asyncio.Lock()
        if persist_dir is None:
            persist_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "_bridge_jobs")
        self.persist_dir = persist_dir
        os.makedirs(self.persist_dir, exist_ok=True)
        self.data_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "_job_files")
        os.makedirs(self.data_dir, exist_ok=True)
        self._load_persisted()

    def _job_file(self, jid):
        return os.path.join(self.persist_dir, f"{jid}.json")

    def _data_file(self, jid):
        return os.path.join(self.data_dir, f"{jid}.b64")

    def _persist(self, jid, job):
        status = job.get("status", "")
        fp = self._job_file(jid)

        if status in ("queued", "processing"):
            data_val = job.get("data")
            if data_val and isinstance(data_val, dict) and data_val.get("file_b64"):
                data_fp = self._data_file(jid)
                if not os.path.exists(data_fp):
                    try:
                        with open(data_fp, "w", encoding="utf-8") as f:
                            f.write(data_val["file_b64"])
                    except Exception as e:
                        log.warning(f"data 抽取失败 {jid}: {e}")
                slim = dict(job)
                slim["data"] = None
                slim["data_path"] = data_fp
                slim["data_size"] = len(data_val["file_b64"])
            else:
                slim = job
        else:
            slim = {k: v for k, v in job.items() if k not in ("data", "result")}
            data_fp = self._data_file(jid)
            if os.path.exists(data_fp):
                try: os.remove(data_fp)
                except: pass

        try:
            with open(fp, "w", encoding="utf-8") as f:
                json.dump(slim, f, ensure_ascii=False)
        except Exception as e:
            log.warning(f"持久化失败 {jid}: {e}")

    def _load_data(self, jid, job):
        data_path = job.get("data_path")
        if not data_path:
            return job
        if not os.path.exists(data_path):
            log.warning(f"data 文件丢失 {jid}: {data_path}")
            return job
        try:
            with open(data_path, "r", encoding="utf-8") as f:
                file_b64 = f.read()
            job["data"] = {"file_b64": file_b64}
        except Exception as e:
            log.warning(f"data 读取失败 {jid}: {e}")
        return job

    def _delete_persist(self, jid):
        for fp in (self._job_file(jid), self._data_file(jid)):
            try:
                if os.path.exists(fp):
                    os.remove(fp)
            except Exception:
                pass

    def _load_persisted(self):
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
            queued_count = sum(1 for j in self.queue.values() if j["status"] in ("queued", "processing"))
            if queued_count >= MAX_QUEUED:
                raise RuntimeError(f"queue full ({queued_count}/{MAX_QUEUED})")
            self.next_id += 1
            jid = f"job_{int(time.time())}_{self.next_id}"
            job["job_id"] = jid
            job["status"] = "queued"
            job["enqueued_at"] = time.time()
            self.queue[jid] = job
            self._persist(jid, job)
        return jid

    async def dequeue(self):
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
            out.pop("data", None)
            return out

    async def cleanup_old(self):
        now = time.time()
        async with self.lock:
            to_delete = []
            for jid in list(self.queue.keys()):
                job = self.queue[jid]
                status = job["status"]
                if status in ("done", "failed"):
                    if now - job.get("finished_at", 0) > RESULT_TTL:
                        to_delete.append(jid)
                elif status == "queued":
                    enqueued_at = job.get("enqueued_at", 0)
                    waited = now - enqueued_at
                    if waited > QUEUED_MAX_HOURS * 3600:
                        log.warning(f"🗑️ {jid} queued {int(waited)}s > {QUEUED_MAX_HOURS}h, 丢弃")
                        to_delete.append(jid)
                    elif waited > QUEUED_TIMEOUT:
                        job["status"] = "failed"
                        job["error"] = "timeout: worker unavailable"
                        job["finished_at"] = now
                        self._persist(jid, job)
            for jid in to_delete:
                del self.queue[jid]
                self._delete_persist(jid)

    async def cleanup_pdf_cache(self):
        if not os.path.isdir(PDF_CACHE_DIR):
            return
        now = time.time()
        freed = 0
        for fn in os.listdir(PDF_CACHE_DIR):
            fp = os.path.join(PDF_CACHE_DIR, fn)
            try:
                stat = os.stat(fp)
                if now - stat.st_atime > PDF_CACHE_TTL:
                    freed += stat.st_size
                    os.remove(fp)
            except Exception:
                pass
        if freed:
            log.info(f"🧹 _pdf_cache 清理 {freed//1024}KB")

    async def push_task(self, jid):
        async with self.worker_lock:
            if not self.worker_writer:
                return False
            job = self.queue.get(jid)
            if not job:
                return False
            if job.get("status") == "queued":
                job["status"] = "processing"
                job["started_at"] = time.time()
                job["stage"] = "mineru_starting"
                self._persist(jid, job)
            job_to_send = self._load_data(jid, dict(job))
            await FrameProto.send(self.worker_writer, {
                "type": "TASK",
                "job_id": jid,
                "data": job_to_send,
            })
            log.info(f"  📤 推送任务 {jid} 给 worker")
            return True


STATE = BridgeState()

PDF_CACHE_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "_pdf_cache")
os.makedirs(PDF_CACHE_DIR, exist_ok=True)


def _save_pdf(jid, pdf_b64):
    try:
        raw = base64.b64decode(pdf_b64)
        fp = os.path.join(PDF_CACHE_DIR, f"{jid}.pdf")
        with open(fp, "wb") as f:
            f.write(raw)
        log.info(f"  💾 PDF 落盘: {fp} ({len(raw)//1024}KB)")
    except Exception as e:
        log.warning(f"  PDF 落盘失败 {jid}: {e}")


# ======================= WebSocket Worker 处理 =======================
async def handle_worker(reader, writer):
    addr = writer.get_extra_info("peername")
    log.info(f"WS 连接来自 {addr}")

    # --- WebSocket 握手 ---
    ok = await ws_handshake(reader, writer)
    if ok is None:
        log.warning(f"  {addr} 握手失败 → 关闭")
        try: writer.close()
        except: pass
        return

    # --- Challenge-Response 认证 ---
    try:
        # Step 1: 等 Worker 发 HANDSHAKE
        msg = await asyncio.wait_for(FrameProto.recv(reader), timeout=10)
        if not msg or msg.get("type") != "HANDSHAKE":
            log.warning(f"  {addr} 非 HANDSHAKE → 关闭")
            await ws_write_frame(writer, OPCODE_CLOSE, b"")
            writer.close()
            return

        nonce_client = msg.get("nonce", "")
        if not nonce_client or len(nonce_client) < 8:
            writer.close()
            return

        # Step 2: Server 发 CHALLENGE
        nonce_server = secrets.token_hex(16)
        await FrameProto.send(writer, {"type": "CHALLENGE", "nonce": nonce_server})

        # Step 3: 等 AUTH
        msg = await asyncio.wait_for(FrameProto.recv(reader), timeout=10)
        if not msg or msg.get("type") != "AUTH":
            writer.close()
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
        log.error(f"WS handler 异常 {addr}: {e}")
    finally:
        try:
            await ws_write_frame(writer, OPCODE_CLOSE, b"")
        except:
            pass
        try: writer.close()
        except: pass


# ======================= HTTP 1201 (PHP 接口) =======================
def _worker_online():
    return bool(STATE.worker_writer) and (time.time() - STATE.worker_last_ping) < WORKER_TIMEOUT


class PHPHandler(BaseHTTPRequestHandler):
    def log_message(self, fmt, *args):
        pass

    def _send(self, code, obj):
        body = json.dumps(obj, ensure_ascii=False).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Access-Control-Allow-Origin", "*")
        self.end_headers()
        self.wfile.write(body)

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()

    def do_GET(self):
        path = urlparse(self.path).path
        if path == "/health":
            online = _worker_online()
            queued = sum(1 for j in STATE.queue.values() if j["status"] in ("queued", "processing"))
            self._send(200, {
                "bridge": "ok",
                "protocol": "websocket",
                "worker_online": online,
                "seconds_since_last_ping": int(time.time() - STATE.worker_last_ping) if STATE.worker_last_ping else None,
                "queued_jobs": queued,
            })
            return
        if path.startswith("/status/"):
            jid = path[len("/status/"):]
            job = asyncio.run_coroutine_threadsafe(STATE.get_status(jid), event_loop).result()
            if not job:
                self._send(404, {"error": "任务不存在", "job_id": jid})
                return
            out = {
                "job_id": job["job_id"],
                "status": job["status"],
                "filename": job.get("filename", ""),
                "error": job.get("error"),
                "result": job.get("result"),
                "stage": job.get("stage", ""),
                "progress": job.get("progress", ""),
                "queue_position": int(job.get("queue_position", 0)),
            }
            self._send(200, out)
            return
        self._send(404, {"error": f"未知端点: {path}", "available": ["/health", "/status/{job_id}"]})

    def do_POST(self):
        path = urlparse(self.path).path
        length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(length) if length else b""

        if path == "/upload":
            try:
                payload = json.loads(body.decode("utf-8")) if body else {}
            except json.JSONDecodeError as e:
                self._send(400, {"error": "JSON 格式错误", "detail": str(e)})
                return

            filename = payload.get("filename", "").strip()
            file_b64 = payload.get("file_b64", "").strip()
            subject = payload.get("subject", "")
            education_level = payload.get("education_level", "")
            paper_name = payload.get("paper_name", "")

            if not filename:
                self._send(400, {"error": "缺少 filename 字段"})
                return
            if not file_b64:
                self._send(400, {"error": "缺少 file_b64 字段"})
                return

            try:
                raw = base64.b64decode(file_b64)
            except Exception as e:
                self._send(400, {"error": "file_b64 解码失败", "detail": str(e)})
                return

            max_bytes = 50 * 1024 * 1024
            if len(raw) > max_bytes:
                self._send(413, {"error": f"文件过大 ({len(raw)//1024//1024}MB), 上限 50MB"})
                return

            if not _worker_online():
                self._send(503, {
                    "error": "worker offline",
                    "hint": "家里云 bridge-client 是否在运行?",
                })
                return

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

            job = {
                "filename": filename,
                "file_b64": file_b64,
                "ftype": ftype,
                "subject": subject,
                "education_level": education_level,
                "paper_name": paper_name or os.path.splitext(filename)[0],
            }
            try:
                jid = asyncio.run_coroutine_threadsafe(STATE.enqueue(job), event_loop).result()
            except RuntimeError as e:
                self._send(503, {"error": str(e), "hint": "OCR 队列积压, 请稍后重试"})
                return

            asyncio.run_coroutine_threadsafe(STATE.push_task(jid), event_loop)
            self._send(202, {"job_id": jid, "status": "queued", "message": "任务已提交"})
            return

        if path == "/export_pdf":
            try:
                payload = json.loads(body.decode("utf-8")) if body else {}
            except json.JSONDecodeError as e:
                self._send(400, {"error": "JSON 格式错误", "detail": str(e)})
                return

            export_input = payload.get("export_input")
            if not export_input or not isinstance(export_input, dict):
                self._send(400, {"error": "缺少 export_input 字段"})
                return
            if not export_input.get("questions"):
                self._send(400, {"error": "questions 为空"})
                return

            if not _worker_online():
                self._send(503, {"error": "worker offline"})
                return

            job = {"task_type": "export_pdf", "export_input": export_input}
            try:
                jid = asyncio.run_coroutine_threadsafe(STATE.enqueue(job), event_loop).result()
            except RuntimeError as e:
                self._send(503, {"error": str(e), "hint": "队列满, 请稍后"})
                return

            asyncio.run_coroutine_threadsafe(STATE.push_task(jid), event_loop)
            self._send(202, {"job_id": jid, "status": "queued", "message": "PDF 生成任务已提交"})
            return

        self._send(404, {"error": f"未知端点: {path}", "available": ["/upload (POST)", "/export_pdf (POST)"]})


# ======================= 主入口 =======================
async def cleanup_loop():
    while True:
        await asyncio.sleep(60)
        await STATE.cleanup_old()
        await STATE.cleanup_pdf_cache()


async def watchdog_loop():
    RESEND_GRACE = 60
    SCAN_INTERVAL = 30
    _resent = {}
    MAX_RESEND = 3
    log.info(f"🐕 watchdog 启动 (每 {SCAN_INTERVAL}s 扫, queued>{RESEND_GRACE}s 重推)")

    while True:
        try:
            async with STATE.lock:
                queued = [(jid, job) for jid, job in STATE.queue.items() if job["status"] == "queued"]
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

    # WebSocket 1200 server (家里云 Worker 连接)
    ws_server = await asyncio.start_server(
        handle_worker, "0.0.0.0", WS_PORT
    )
    log.info(f"✅ WebSocket Worker 端口: 0.0.0.0:{WS_PORT} (无第三方依赖, 纯 asyncio)")

    # HTTP 1201 server (PHP 本地接口)
    http_server = ThreadingHTTPServer(("127.0.0.1", HTTP_PORT), PHPHandler)
    import threading
    threading.Thread(target=http_server.serve_forever, daemon=True).start()
    log.info(f"✅ HTTP PHP 接口: 127.0.0.1:{HTTP_PORT} (仅 localhost)")

    asyncio.create_task(cleanup_loop())
    asyncio.create_task(watchdog_loop())

    async with ws_server:
        await ws_server.serve_forever()


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        log.info("退出")
