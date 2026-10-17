#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
StudyScape Bridge Client — OCR Worker (家里云 ssh home 运行)
========================================================
主动连接公网服务器 120.79.161.207:1200
Challenge-Response 认证 → 保持长连接 → 收任务 → 调 MinerU pipeline → 回传 JSON

自动重连: 指数退避 (2s → 4s → 8s → 最大 60s)
心跳: 每 10s PING 一次

启动: systemctl start studyscape-bridge-client (家里云)
"""

import asyncio
import hmac
import hashlib
import json
import os
import sys
import time
import base64
import io
import secrets
import struct
import logging
import traceback

# ======================= 配置 =======================
SERVER_HOST = "120.79.161.207"
SERVER_PORT = 1200
KEY_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "bridge_key.txt")
PIPELINE_SCRIPT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "exam_pipeline.py")
PIPELINE_TIMEOUT = 300   # 单任务最大 5 分钟

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s",
    datefmt="%H:%M:%S",
)
log = logging.getLogger("bridge-client")

# ======================= 密钥加载 =======================
if not os.path.exists(KEY_FILE):
    log.error(f"密钥文件不存在: {KEY_FILE}")
    raise SystemExit(1)

with open(KEY_FILE, "r") as f:
    SHARED_KEY = f.read().strip()

# ======================= 帧协议 (和 server 一致) =======================
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


# ======================= Worker 主循环 =======================
async def run_worker():
    backoff = 2
    max_backoff = 60

    while True:
        try:
            log.info(f"连接 {SERVER_HOST}:{SERVER_PORT} ...")
            reader, writer = await asyncio.open_connection(SERVER_HOST, SERVER_PORT)

            # --- 认证 ---
            nonce_client = secrets.token_hex(16)
            await FrameProto.send(writer, {"type": "HANDSHAKE", "nonce": nonce_client})

            msg = await asyncio.wait_for(FrameProto.recv(reader), timeout=5)
            if not msg or msg.get("type") != "CHALLENGE":
                log.error("服务端无 challenge")
                break

            nonce_server = msg.get("nonce", "")
            mac = hmac.new(SHARED_KEY.encode(),
                           (nonce_client + nonce_server).encode(),
                           hashlib.sha256).hexdigest()
            await FrameProto.send(writer, {"type": "AUTH", "hmac": mac})

            msg = await asyncio.wait_for(FrameProto.recv(reader), timeout=5)
            if not msg or msg.get("type") != "OK":
                log.error("认证失败!")
                break

            log.info("✅ 认证通过, 等待任务 ...")
            backoff = 2

            loop_lock = asyncio.Lock()

            # --- 主循环: 心跳 + 收任务 ---
            last_ping = 0
            while True:
                # 发心跳
                if time.time() - last_ping > 10:
                    await FrameProto.send(writer, {"type": "PING"})
                    last_ping = time.time()

                # 等消息 (20s 超时)
                try:
                    msg = await asyncio.wait_for(FrameProto.recv(reader), timeout=20)
                except asyncio.TimeoutError:
                    continue

                if msg is None:
                    log.warning("连接关闭")
                    break

                mtype = msg.get("type")

                if mtype == "PONG":
                    continue

                if mtype == "TASK":
                    jid = msg.get("job_id")
                    task_data = msg.get("data", {})
                    log.info(f"📥 收到任务 {jid}: {task_data.get('filename', '?')}")
                    # 串行处理: 上一个任务没完成就等
                    asyncio.create_task(handle_task(writer, jid, task_data, loop_lock))

                elif mtype == "CLOSE":
                    log.info("服务端关闭连接")
                    break

        except asyncio.CancelledError:
            break
        except Exception as e:
            log.error(f"连接异常: {e}")

        # 重连 (指数退避)
        log.info(f"{backoff}s 后重连 ...")
        await asyncio.sleep(backoff)
        backoff = min(backoff * 2, max_backoff)


# ======================= 任务处理 =======================
async def handle_task(writer, job_id, task_data, lock):
    """串行处理一个任务"""
    async with lock:
        try:
            filename = task_data.get("filename", "unknown.pdf")
            file_b64 = task_data.get("file_b64", "")
            subject = task_data.get("subject", "")
            education_level = task_data.get("education_level", "")

            # 写临时文件
            tmpdir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "_tmp_jobs")
            os.makedirs(tmpdir, exist_ok=True)
            tmp_in = os.path.join(tmpdir, f"{job_id}_{filename}")
            tmp_out = os.path.join(tmpdir, f"{job_id}_result.json")

            with open(tmp_in, "wb") as f:
                f.write(base64.b64decode(file_b64))

            log.info(f"  处理 {filename} ({len(file_b64)//1024}KB base64) → {tmp_in}")

            # 调用 exam_pipeline.py
            proc = await asyncio.create_subprocess_exec(
                sys.executable, PIPELINE_SCRIPT,
                tmp_in,
                "-o", tmp_out,
                "--subject", subject or "综合",
                "--education_level", education_level or "",
                "--paper_name", os.path.splitext(filename)[0],
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
            )
            try:
                await asyncio.wait_for(proc.wait(), PIPELINE_TIMEOUT)
            except asyncio.TimeoutError:
                proc.kill()
                await proc.wait()
                await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": "pipeline timeout"})
                return

            if proc.returncode != 0:
                stderr = (await proc.stderr.read()).decode()
                log.error(f"  pipeline rc={proc.returncode}: {stderr[:300]}")
                await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": f"pipeline rc={proc.returncode}"})
                return

            # 读结果
            if not os.path.exists(tmp_out):
                await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": "pipeline no output file"})
                return

            with open(tmp_out, "r", encoding="utf-8") as f:
                result = json.load(f)

            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "result": result})
            qcount = len(result.get("questions", []))
            log.info(f"  ✅ {job_id} 完成 → {qcount} 道题")

            # 清理
            for p in (tmp_in, tmp_out):
                try: os.remove(p)
                except: pass

        except Exception as e:
            log.error(f"  {job_id}: {e}\n{traceback.format_exc()}")
            try:
                await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": str(e)[:200]})
            except:
                pass


if __name__ == "__main__":
    asyncio.run(run_worker())
