#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
StudyScape Bridge Client — OCR Worker (家里云运行)
================================================
WebSocket 主动连接公网服务器 207.57.127.180:1200
Challenge-Response 认证 → 保持长连接 → 收任务 → 调 MinerU pipeline → 回传 JSON

★ 自实现 WebSocket (零第三方依赖, 纯 asyncio)
★ 认证帧协议沿用原版: WebSocket text frame 直接承载 JSON

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
import subprocess
import logging
import traceback
import hashlib as _hashlib
import base64 as _b64

# ======================= 配置 (全部从 server.env / 环境变量读, 零硬编码) =======================
from config import (
    BRIDGE_REMOTE_HOST, BRIDGE_REMOTE_PORT,
    STORAGE_ROOT, KEY_FILE,
)
SERVER_HOST = BRIDGE_REMOTE_HOST  # 兼容旧代码里的变量名
SERVER_PORT = BRIDGE_REMOTE_PORT
PIPELINE_SCRIPT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "exam_pipeline.py")
PDF_SCRIPT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "export_pdf.py")
PIPELINE_TIMEOUT = 300

# 家里云存储根目录
HOME_STORAGE_ROOT = STORAGE_ROOT
HOME_MATERIALS_DIR = os.path.join(HOME_STORAGE_ROOT, "materials")
HOME_TMP_DIR = os.path.join(HOME_STORAGE_ROOT, "_tmp_jobs")  # OCR pipeline 临时文件也走 /data

WS_MAGIC = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11"
OPCODE_TEXT = 0x01
OPCODE_BINARY = 0x02
OPCODE_CLOSE = 0x08
OPCODE_PING = 0x09
OPCODE_PONG = 0x0A

# MinerU 挂起配置
MINERU_SERVICE = "mineru-flash"
MINERU_COLD_START_WAIT = 15
IDLE_SUSPEND_SECONDS = 300

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s",
    datefmt="%H:%M:%S",
)
log = logging.getLogger("bridge-client")

# ======================= MinerU 挂起/拉起 =======================
_mineru_last_task = 0


def _mineru_active():
    try:
        result = subprocess.run(
            ["sudo", "systemctl", "is-active", MINERU_SERVICE],
            capture_output=True, text=True, timeout=5
        )
        return result.stdout.strip() == "active"
    except Exception:
        return False


def _mineru_start():
    if _mineru_active():
        return True
    log.info(f"  ⚡ 拉起 {MINERU_SERVICE} (冷启动等待 {MINERU_COLD_START_WAIT}s)...")
    try:
        subprocess.run(["sudo", "systemctl", "start", MINERU_SERVICE], timeout=10)
        time.sleep(MINERU_COLD_START_WAIT)
        if _mineru_active():
            log.info(f"  ✅ {MINERU_SERVICE} 已启动")
            return True
        else:
            log.error(f"  ❌ {MINERU_SERVICE} 启动失败")
            return False
    except Exception as e:
        log.error(f"  ❌ 启动异常: {e}")
        return False


def _mineru_stop():
    if not _mineru_active():
        return
    log.info(f"  💤 挂起 {MINERU_SERVICE} (空闲 {IDLE_SUSPEND_SECONDS}s)...")
    try:
        subprocess.run(["sudo", "systemctl", "stop", MINERU_SERVICE], timeout=10)
    except Exception as e:
        log.warning(f"  停止异常: {e}")


def _check_idle_suspend():
    if _mineru_active() and time.time() - _mineru_last_task > IDLE_SUSPEND_SECONDS:
        _mineru_stop()


# ======================= 密钥加载 =======================
if not os.path.exists(KEY_FILE):
    log.error(f"密钥文件不存在: {KEY_FILE}")
    raise SystemExit(1)

with open(KEY_FILE, "r") as f:
    SHARED_KEY = f.read().strip()


# ======================= WebSocket 客户端 (自实现) =======================
async def ws_connect(host, port):
    """发起 WebSocket 连接 → 返回 (reader, writer)"""
    reader, writer = await asyncio.open_connection(host, port)

    # 生成随机 key
    key_b64 = _b64.b64encode(secrets.token_bytes(16)).decode()

    req = (
        f"GET / HTTP/1.1\r\n"
        f"Host: {host}:{port}\r\n"
        f"Upgrade: websocket\r\n"
        f"Connection: Upgrade\r\n"
        f"Sec-WebSocket-Key: {key_b64}\r\n"
        f"Sec-WebSocket-Version: 13\r\n"
        f"\r\n"
    )
    writer.write(req.encode())
    await writer.drain()

    # 读响应
    resp_buf = b""
    while b"\r\n\r\n" not in resp_buf:
        chunk = await asyncio.wait_for(reader.read(4096), timeout=10)
        if not chunk:
            raise ConnectionError("服务器断开")
        resp_buf += chunk
        if len(resp_buf) > 8192:
            break

    resp_text = resp_buf.decode("utf-8", errors="replace")
    if "101" not in resp_text.split("\r\n")[0] or "Sec-WebSocket-Accept" not in resp_text:
        raise ConnectionError(f"握手失败: {resp_text[:200]}")

    # 验证 Accept
    expected = _b64.b64encode(
        _hashlib.sha1((key_b64 + WS_MAGIC).encode()).digest()
    ).decode()
    for line in resp_text.split("\r\n"):
        if line.lower().startswith("sec-websocket-accept:"):
            actual = line.split(":", 1)[1].strip()
            if actual != expected:
                raise ConnectionError(f"Sec-WebSocket-Accept 不匹配")

    return reader, writer


async def _ws_send_frame(writer, opcode, payload, masked=True):
    """写一个 WebSocket 帧
    客户端必须 MASK → 生成随机 mask key"""
    if isinstance(payload, str):
        payload = payload.encode("utf-8")

    length = len(payload)
    frame = bytearray()
    frame.append(0x80 | (opcode & 0x0F))  # FIN + opcode

    mask_bit = 0x80 if masked else 0x00
    if length < 126:
        frame.append(mask_bit | length)
    elif length < 65536:
        frame.append(mask_bit | 126)
        frame.extend(struct.pack(">H", length))
    else:
        frame.append(mask_bit | 127)
        frame.extend(struct.pack(">Q", length))

    if masked:
        mask_key = secrets.token_bytes(4)
        frame.extend(mask_key)
        payload = bytes(b ^ mask_key[i % 4] for i, b in enumerate(payload))

    frame.extend(payload)
    writer.write(bytes(frame))
    await writer.drain()


async def _ws_read_frame(reader):
    """读一个 WebSocket 帧 (服务端发来的 → 不带 MASK)"""
    hdr = await reader.readexactly(2)
    opcode = hdr[0] & 0x0F
    masked = (hdr[1] & 0x80) != 0
    length = hdr[1] & 0x7F

    if length == 126:
        length = struct.unpack(">H", await reader.readexactly(2))[0]
    elif length == 127:
        length = struct.unpack(">Q", await reader.readexactly(8))[0]

    mask_key = await reader.readexactly(4) if masked else b""
    payload = await reader.readexactly(length) if length > 0 else b""

    if masked and payload:
        payload = bytes(b ^ mask_key[i % 4] for i, b in enumerate(payload))

    return opcode, payload


async def _ws_text_recv(reader, writer):
    """读 text frame, 返回 str (自动处理 ping/pong/close)"""
    while True:
        opcode, payload = await _ws_read_frame(reader)
        if opcode == OPCODE_TEXT:
            return payload.decode("utf-8")
        elif opcode == OPCODE_PING:
            await _ws_send_frame(writer, OPCODE_PONG, payload)
        elif opcode == OPCODE_CLOSE:
            # 回复 close
            try:
                await _ws_send_frame(writer, OPCODE_CLOSE, b"")
            except:
                pass
            return None
        elif opcode == OPCODE_PONG:
            pass


async def _ws_text_send(writer, text):
    """发 text frame (客户端带 MASK)"""
    await _ws_send_frame(writer, OPCODE_TEXT, text)


# ======================= JSON 帧 (认证握手) =======================
class FrameProto:
    @staticmethod
    async def recv(reader, writer):
        text = await _ws_text_recv(reader, writer)
        if text is None:
            return None
        return json.loads(text)

    @staticmethod
    async def send(writer, obj):
        text = json.dumps(obj)
        await _ws_text_send(writer, text)


# ======================= Worker 主循环 =======================
async def run_worker():
    backoff = 2
    max_backoff = 60

    while True:
        try:
            log.info(f"🔌 WebSocket 连接 {SERVER_HOST}:{SERVER_PORT} ...")
            reader, writer = await ws_connect(SERVER_HOST, SERVER_PORT)

            # --- Challenge-Response 认证 ---
            nonce_client = secrets.token_hex(16)
            await FrameProto.send(writer, {"type": "HANDSHAKE", "nonce": nonce_client})

            msg = await asyncio.wait_for(FrameProto.recv(reader, writer), timeout=10)
            if not msg or msg.get("type") != "CHALLENGE":
                log.error("服务端无 challenge")
                writer.close()
                continue

            nonce_server = msg.get("nonce", "")
            mac = hmac.new(SHARED_KEY.encode(),
                           (nonce_client + nonce_server).encode(),
                           hashlib.sha256).hexdigest()
            await FrameProto.send(writer, {"type": "AUTH", "hmac": mac})

            msg = await asyncio.wait_for(FrameProto.recv(reader, writer), timeout=10)
            if not msg or msg.get("type") != "OK":
                log.error("认证失败!")
                writer.close()
                continue

            log.info("✅ 认证通过, 等待任务 ...")
            backoff = 2

            loop_lock = asyncio.Lock()
            last_ping = 0
            last_idle_check = 0

            while True:
                now = time.time()

                if now - last_ping > 10:
                    try:
                        await FrameProto.send(writer, {"type": "PING"})
                    except Exception:
                        break
                    last_ping = now

                if now - last_idle_check > 60:
                    _check_idle_suspend()
                    last_idle_check = now

                try:
                    msg = await asyncio.wait_for(FrameProto.recv(reader, writer), timeout=20)
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
                    asyncio.create_task(handle_task(writer, reader, jid, task_data, loop_lock))

                elif mtype == "STORAGE_TASK":
                    req_id = msg.get("request_id")
                    storage_data = msg.get("data", {})
                    log.info(f"💾 STORAGE  {req_id}: {storage_data.get('action')} {storage_data.get('stored_name', '?')}")
                    asyncio.create_task(handle_storage(writer, req_id, storage_data))

                elif mtype == "CLOSE":
                    log.info("服务端关闭连接")
                    break

        except asyncio.CancelledError:
            break
        except Exception as e:
            log.error(f"连接异常: {e}")

        # 重连
        log.info(f"{backoff}s 后重连 ...")
        await asyncio.sleep(backoff)
        backoff = min(backoff * 2, max_backoff)


# ======================= 存储处理 (家里云本地磁盘) =======================
def _safe_path(root_dir, filename):
    """安全路径拼接: 防止 path traversal, 只允许字母数字._-"""
    filename = os.path.basename(filename)
    if not filename or filename == "." or filename == "..":
        return None
    if not all(c.isalnum() or c in "._-" for c in filename):
        return None
    full = os.path.join(root_dir, filename)
    real_root = os.path.realpath(root_dir)
    real_full = os.path.realpath(os.path.dirname(full))
    if not real_full.startswith(real_root):
        return None
    return full


async def handle_storage(writer, req_id, data):
    """处理家里云存储请求: store / retrieve / delete"""
    action = data.get("action", "")
    stored_name = data.get("stored_name", "")
    os.makedirs(HOME_MATERIALS_DIR, exist_ok=True)

    async def reply(result):
        result["request_id"] = req_id
        result["type"] = "STORAGE_RESULT"
        await FrameProto.send(writer, result)

    try:
        safe_fp = _safe_path(HOME_MATERIALS_DIR, stored_name)
        if not safe_fp:
            await reply({"ok": False, "error": "非法文件名"})
            return

        if action == "store":
            file_b64 = data.get("file_b64", "")
            expected_sha = data.get("sha256", "")
            if not file_b64:
                await reply({"ok": False, "error": "缺 file_b64"})
                return
            raw = base64.b64decode(file_b64)
            # SHA256 校验
            actual_sha = hashlib.sha256(raw).hexdigest()
            if expected_sha and not hmac.compare_digest(actual_sha, expected_sha):
                await reply({"ok": False, "error": f"SHA256 mismatch expected={expected_sha} actual={actual_sha}"})
                return
            # 写文件
            tmp_fp = safe_fp + ".tmp"
            with open(tmp_fp, "wb") as f:
                f.write(raw)
            os.replace(tmp_fp, safe_fp)
            log.info(f"  💾 存储完成: {stored_name} ({len(raw)//1024}KB, sha256={actual_sha[:16]}...)")
            await reply({"ok": True, "sha256": actual_sha, "home_path": safe_fp})

        elif action == "retrieve":
            if not os.path.isfile(safe_fp):
                await reply({"ok": False, "error": "文件不存在"})
                return
            raw = open(safe_fp, "rb").read()
            file_b64 = base64.b64encode(raw).decode()
            sha = hashlib.sha256(raw).hexdigest()
            # mime
            mime = ""
            ext = stored_name.rsplit(".", 1)[-1].lower() if "." in stored_name else ""
            mime_map = {"pdf": "application/pdf", "doc": "application/msword",
                        "docx": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                        "xls": "application/vnd.ms-excel", "xlsx": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                        "ppt": "application/vnd.ms-powerpoint", "pptx": "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                        "txt": "text/plain", "md": "text/markdown",
                        "zip": "application/zip", "rar": "application/x-rar-compressed"}
            mime = mime_map.get(ext, "application/octet-stream")
            log.info(f"  📤 读取文件: {stored_name} ({len(raw)//1024}KB)")
            await reply({"ok": True, "file_b64": file_b64, "sha256": sha, "size": len(raw), "mime": mime})

        elif action == "delete":
            if os.path.isfile(safe_fp):
                os.remove(safe_fp)
                log.info(f"  🗑️  删除: {stored_name}")
            await reply({"ok": True})

        else:
            await reply({"ok": False, "error": f"未知 action: {action}"})

    except Exception as e:
        log.error(f"  storage 错误 {req_id}: {e}")
        try:
            await reply({"ok": False, "error": str(e)[:200]})
        except:
            pass


# ======================= 任务处理 =======================
async def handle_task(writer, reader, job_id, task_data, lock):
    global _mineru_last_task
    async with lock:
        task_type = task_data.get("task_type", "ocr_pipeline")
        if task_type == "export_pdf":
            await _handle_export_pdf(writer, job_id, task_data)
        else:
            await _handle_ocr(writer, job_id, task_data)


async def _handle_export_pdf(writer, job_id, task_data):
    try:
        _mineru_last_task = time.time()
        export_input = task_data.get("export_input", {})

        tmpdir = HOME_TMP_DIR
        os.makedirs(tmpdir, exist_ok=True)
        tmp_in = os.path.join(tmpdir, f"{job_id}_export.json")
        tmp_out = os.path.join(tmpdir, f"{job_id}.pdf")

        with open(tmp_in, "w", encoding="utf-8") as f:
            json.dump(export_input, f, ensure_ascii=False)

        log.info(f"  📄 {job_id}: 生成 PDF ({len(export_input.get('questions', []))} 题)")

        proc = await asyncio.create_subprocess_exec(
            sys.executable, PDF_SCRIPT, tmp_in, tmp_out,
            stdout=asyncio.subprocess.PIPE,
            stderr=asyncio.subprocess.PIPE,
        )
        await asyncio.wait_for(proc.wait(), 120)

        if proc.returncode != 0:
            stderr = (await proc.stderr.read()).decode()
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": f"pdf rc={proc.returncode}: {stderr[:200]}"})
            return

        with open(tmp_out, "rb") as f:
            pdf_b64 = base64.b64encode(f.read()).decode()

        for p in (tmp_in, tmp_out):
            try: os.remove(p)
            except: pass

        await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "result": {"pdf_b64": pdf_b64}})
        log.info(f"  ✅ {job_id} PDF 生成 OK")

    except Exception as e:
        log.error(f"  {job_id} export_pdf: {e}\n{traceback.format_exc()}")
        try:
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": str(e)[:200]})
        except:
            pass


async def _handle_ocr(writer, job_id, task_data):
    global _mineru_last_task
    _mineru_last_task = time.time()
    try:
        filename = task_data.get("filename", "unknown.pdf")
        file_b64 = task_data.get("file_b64", "")
        subject = task_data.get("subject", "")
        education_level = task_data.get("education_level", "")

        await FrameProto.send(writer, {"type": "PROGRESS", "job_id": job_id, "stage": "mineru_starting"})

        loop = asyncio.get_event_loop()
        miner_ok = await loop.run_in_executor(None, _mineru_start)
        if not miner_ok:
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": "MinerU parse-server 启动失败"})
            return

        tmpdir = HOME_TMP_DIR
        os.makedirs(tmpdir, exist_ok=True)
        tmp_in = os.path.join(tmpdir, f"{job_id}_{filename}")
        tmp_out = os.path.join(tmpdir, f"{job_id}_result.json")

        with open(tmp_in, "wb") as f:
            f.write(base64.b64decode(file_b64))

        log.info(f"  处理 {filename} ({len(file_b64)//1024}KB base64) → {tmp_in}")

        await FrameProto.send(writer, {"type": "PROGRESS", "job_id": job_id, "stage": "mineru_uploading"})

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

        _prev_stage = None
        _progress_lines = []

        async def _read_stdout():
            nonlocal _prev_stage
            try:
                async for raw_line in proc.stdout:
                    line = raw_line.decode("utf-8", errors="replace").strip()
                    if not line:
                        continue
                    _progress_lines.append(line)
                    if len(_progress_lines) > 30:
                        _progress_lines[-30:]
                    new_stage = None
                    progress_hint = None
                    if "[MinerU] 提交" in line:
                        new_stage = "mineru_uploading"
                    elif "[MinerU] job_id=" in line:
                        new_stage = "mineru_parsing"
                    elif "[MinerU]" in line and "/" in line:
                        new_stage = "mineru_parsing"
                        m = __import__("re").search(r'(\d+)/(\d+)', line)
                        if m: progress_hint = f"{m.group(1)}/{m.group(2)} 页"
                    elif "[MinerU] ✅" in line or "[Flatten] ✅" in line:
                        new_stage = "flatten_blocks"
                    elif "[Group] ✅" in line:
                        new_stage = "group_questions"
                        m = __import__("re").search(r'切出 (\d+) 道', line)
                        if m: progress_hint = f"识别出 {m.group(1)} 道"
                    elif "[Validate]" in line and "✅" in line:
                        new_stage = "done"
                    if new_stage and (new_stage != _prev_stage or progress_hint):
                        await FrameProto.send(writer, {"type": "PROGRESS", "job_id": job_id, "stage": new_stage, "progress": progress_hint or ""})
                        _prev_stage = new_stage
            except Exception as e:
                log.warning(f"stdout reader 异常: {e}")

        reader_task = asyncio.create_task(_read_stdout())

        try:
            await asyncio.wait_for(proc.wait(), PIPELINE_TIMEOUT)
        except asyncio.TimeoutError:
            log.error(f"  pipeline timeout ({PIPELINE_TIMEOUT}s) — 杀进程")
            proc.kill()
            await proc.wait()
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": f"pipeline timeout ({PIPELINE_TIMEOUT}s)"})
            return

        try:
            await asyncio.wait_for(reader_task, 10)
        except asyncio.TimeoutError:
            reader_task.cancel()
        except Exception:
            pass

        if proc.returncode != 0:
            stderr = (await proc.stderr.read()).decode()
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": f"pipeline rc={proc.returncode}: {stderr[:200]}"})
            return

        await FrameProto.send(writer, {"type": "PROGRESS", "job_id": job_id, "stage": "done"})

        if not os.path.exists(tmp_out):
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": "pipeline no output file"})
            return

        with open(tmp_out, "r", encoding="utf-8") as f:
            result = json.load(f)

        await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "result": result})
        qcount = len(result.get("questions", []))
        log.info(f"  ✅ {job_id} 完成 → {qcount} 道题")

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
