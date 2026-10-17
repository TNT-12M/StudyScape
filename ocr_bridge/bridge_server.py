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
import logging
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import urlparse

# ======================= 配置 =======================
TCP_PORT = 1200          # Worker 连接端口 (公网)
HTTP_PORT = 1201         # PHP 本地接口 (仅 localhost)
KEY_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "bridge_key.txt")
WORKER_TIMEOUT = 30      # Worker 心跳超时 (秒)
RESULT_TTL = 1800        # 结果缓存 30 分钟 (秒)

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
    def __init__(self):
        self.worker_reader = None
        self.worker_writer = None
        self.worker_last_ping = 0
        self.worker_lock = asyncio.Lock()
        self.queue = {}     # job_id → job dict {enqueued_at, status, data, result}
        self.next_id = 0
        self.lock = asyncio.Lock()

    async def enqueue(self, job):
        async with self.lock:
            self.next_id += 1
            jid = f"job_{int(time.time())}_{self.next_id}"
            job["job_id"] = jid
            job["status"] = "queued"
            job["enqueued_at"] = time.time()
            self.queue[jid] = job
        return jid

    async def dequeue(self):
        """Worker 拉取一个待处理任务。返回 (jid, job_data) 或 None"""
        async with self.lock:
            for jid, job in list(self.queue.items()):
                if job["status"] == "queued":
                    job["status"] = "processing"
                    job["started_at"] = time.time()
                    return jid, job
        return None

    async def set_result(self, jid, result_json, error=None):
        async with self.lock:
            if jid in self.queue:
                self.queue[jid]["status"] = "done" if error is None else "failed"
                self.queue[jid]["result"] = result_json
                self.queue[jid]["error"] = error
                self.queue[jid]["finished_at"] = time.time()

    async def get_status(self, jid):
        async with self.lock:
            return self.queue.get(jid)

    async def cleanup_old(self):
        now = time.time()
        async with self.lock:
            for jid in list(self.queue.keys()):
                job = self.queue[jid]
                if job["status"] in ("done", "failed"):
                    if now - job.get("finished_at", 0) > RESULT_TTL:
                        del self.queue[jid]
                elif job["status"] == "queued":
                    if now - job.get("enqueued_at", 0) > 600:
                        # 10 分钟没被 worker 取走 → 超时
                        job["status"] = "failed"
                        job["error"] = "timeout: worker unavailable"
                        job["finished_at"] = now


STATE = BridgeState()

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

                    elif mtype == "RESULT":
                        jid = msg.get("job_id")
                        result = msg.get("result")
                        error = msg.get("error")
                        await STATE.set_result(jid, result, error)
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
    STATE.worker_last_ping > 0 and (time.time() - STATE.worker_last_ping) < WORKER_TIMEOUT

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

    def do_GET(self):
        path = urlparse(self.path).path
        # 健康检查
        if path == "/health":
            online = _worker_online()
            self._send(200, {"bridge": "ok", "worker_online": online})
            return
        # 查状态
        if path.startswith("/status/"):
            jid = path[len("/status/"):]
            job = asyncio.run_coroutine_threadsafe(STATE.get_status(jid), event_loop).result()
            if not job:
                self._send(404, {"error": "not found"})
                return
            # 清理敏感字段
            out = {k: v for k, v in job.items() if k in ("job_id", "status", "error", "result", "filename")}
            self._send(200, out)
            return
        self._send(404, {"error": "unknown endpoint"})

    def do_POST(self):
        path = urlparse(self.path).path
        length = int(self.headers.get("Content-Length", 0))
        body = self.rfile.read(length) if length else b""

        if path == "/upload":
            try:
                payload = json.loads(body.decode("utf-8"))
            except:
                self._send(400, {"error": "invalid json"})
                return

            # 必需字段
            filename = payload.get("filename", "")
            file_b64 = payload.get("file_b64", "")
            subject = payload.get("subject", "")
            education_level = payload.get("education_level", "")

            if not filename or not file_b64:
                self._send(400, {"error": "filename + file_b64 required"})
                return

            # Worker 在线检查
            if not _worker_online():
                self._send(503, {"error": "worker offline"})
                return

            # 入队
            job = {
                "filename": filename,
                "file_b64": file_b64,
                "subject": subject,
                "education_level": education_level,
            }
            jid = asyncio.run_coroutine_threadsafe(STATE.enqueue(job), event_loop).result()

            # 推送给 Worker (TASK 直接带 data, 不用再 round-trip)
            async def push_to_worker():
                async with STATE.worker_lock:
                    if STATE.worker_writer:
                        await FrameProto.send(STATE.worker_writer, {
                            "type": "TASK",
                            "job_id": jid,
                            "data": job,
                        })
                return True
            asyncio.run_coroutine_threadsafe(push_to_worker(), event_loop)

            self._send(202, {"job_id": jid, "status": "queued"})
            return

        self._send(404, {"error": "unknown endpoint"})


# ======================= 主入口 =======================
async def cleanup_loop():
    while True:
        await asyncio.sleep(60)
        await STATE.cleanup_old()


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

    async with tcp_server:
        await tcp_server.serve_forever()


if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        log.info("退出")
