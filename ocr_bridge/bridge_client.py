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
import subprocess
import logging
import traceback

# ======================= 配置 =======================
SERVER_HOST = "120.79.161.207"
SERVER_PORT = 1200
KEY_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "bridge_key.txt")
PIPELINE_SCRIPT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "exam_pipeline.py")
PDF_SCRIPT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "export_pdf.py")
PIPELINE_TIMEOUT = 300   # 单任务最大 5 分钟

# MinerU parse-server 挂起配置
MINERU_SERVICE = "mineru-flash"
MINERU_COLD_START_WAIT = 15   # MinerU 冷启动等 15s
IDLE_SUSPEND_SECONDS = 300    # 5 分钟无任务自动挂起 MinerU

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s",
    datefmt="%H:%M:%S",
)
log = logging.getLogger("bridge-client")

# ======================= MinerU 挂起/拉起 =======================
_mineru_last_task = 0  # 最后一次处理任务的时间戳


def _mineru_active():
    """sudo systemctl is-active mineru-flash"""
    try:
        result = subprocess.run(
            ["sudo", "systemctl", "is-active", MINERU_SERVICE],
            capture_output=True, text=True, timeout=5
        )
        return result.stdout.strip() == "active"
    except Exception:
        return False


def _mineru_start():
    """sudo systemctl start mineru-flash + 等冷启动"""
    if _mineru_active():
        return True
    log.info(f"  ⚡ 拉起 {MINERU_SERVICE} (冷启动等待 {MINERU_COLD_START_WAIT}s)...")
    try:
        subprocess.run(["sudo", "systemctl", "start", MINERU_SERVICE], timeout=10)
        time.sleep(MINERU_COLD_START_WAIT)
        # 再确认一次
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
    """sudo systemctl stop mineru-flash"""
    if not _mineru_active():
        return
    log.info(f"  💤 挂起 {MINERU_SERVICE} (空闲 {IDLE_SUSPEND_SECONDS}s)...")
    try:
        subprocess.run(["sudo", "systemctl", "stop", MINERU_SERVICE], timeout=10)
    except Exception as e:
        log.warning(f"  停止异常: {e}")


def _check_idle_suspend():
    """主循环里定期调用: 空闲超时就挂起 MinerU"""
    if _mineru_active() and time.time() - _mineru_last_task > IDLE_SUSPEND_SECONDS:
        _mineru_stop()


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

            # --- 主循环: 心跳 + 收任务 + idle 检查 ---
            last_ping = 0
            last_idle_check = 0
            while True:
                now = time.time()

                # 发心跳
                if now - last_ping > 10:
                    await FrameProto.send(writer, {"type": "PING"})
                    last_ping = now

                # ★ 每 60s 检查一次空闲挂起
                if now - last_idle_check > 60:
                    _check_idle_suspend()
                    last_idle_check = now

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
    """串行处理一个任务 — 按 task_type 分发"""
    global _mineru_last_task
    async with lock:
        task_type = task_data.get("task_type", "ocr_pipeline")

        if task_type == "export_pdf":
            await _handle_export_pdf(writer, job_id, task_data)
        else:
            await _handle_ocr(writer, job_id, task_data)


async def _handle_export_pdf(writer, job_id, task_data):
    """导出 PDF — 纯 CPU 任务, 不需要 MinerU"""
    try:
        _mineru_last_task = time.time()  # 也算活跃
        export_input = task_data.get("export_input", {})

        # 写临时 JSON → 跑 export_pdf.py → 读 PDF → base64
        tmpdir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "_tmp_jobs")
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

        # 清理
        for p in (tmp_in, tmp_out):
            try: os.remove(p)
            except: pass

        await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "result": {"pdf_b64": pdf_b64}})
        log.info(f"  ✅ {job_id} PDF 生成 OK ({len(pdf_b64)//1024}KB base64)")

    except Exception as e:
        log.error(f"  {job_id} export_pdf: {e}\n{traceback.format_exc()}")
        try:
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": str(e)[:200]})
        except:
            pass


async def _handle_ocr(writer, job_id, task_data):
    """OCR Pipeline 任务 — MinerU 解析 + 题目构建"""
    global _mineru_last_task
    _mineru_last_task = time.time()  # 更新活跃时间
    try:
        filename = task_data.get("filename", "unknown.pdf")
        file_b64 = task_data.get("file_b64", "")
        subject = task_data.get("subject", "")
        education_level = task_data.get("education_level", "")

        # ★ 上报阶段: MinerU 解析中
        await FrameProto.send(writer, {"type": "PROGRESS", "job_id": job_id, "stage": "mineru_starting"})

        # ★ MinerU 挂起中就拉起 (冷启动可能 10-20s)
        loop = asyncio.get_event_loop()
        miner_ok = await loop.run_in_executor(None, _mineru_start)
        if not miner_ok:
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": "MinerU parse-server 启动失败"})
            return

        # 写临时文件
        tmpdir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "_tmp_jobs")
        os.makedirs(tmpdir, exist_ok=True)
        tmp_in = os.path.join(tmpdir, f"{job_id}_{filename}")
        tmp_out = os.path.join(tmpdir, f"{job_id}_result.json")

        with open(tmp_in, "wb") as f:
            f.write(base64.b64decode(file_b64))

        log.info(f"  处理 {filename} ({len(file_b64)//1024}KB base64) → {tmp_in}")

        # ★ 上报阶段: Pipeline 已启动, MinerU 正在上传
        await FrameProto.send(writer, {"type": "PROGRESS", "job_id": job_id, "stage": "mineru_uploading"})

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

        # ★ 逐行解析 pipeline stdout → 发细粒度 PROGRESS
        # exam_pipeline.py print 格式: [MinerU] xxx / [Flatten] xxx / [Group] xxx / [Validate] xxx
        _prev_stage = None
        _progress_lines = []  # 收集关键行用于日志
        try:
            async for raw_line in proc.stdout:
                line = raw_line.decode("utf-8", errors="replace").strip()
                if not line:
                    continue
                _progress_lines.append(line)
                # 最多保留 30 行 (日志用)
                if len(_progress_lines) > 30:
                    _progress_lines = _progress_lines[-30:]

                # 按关键字匹配阶段 (pipeline 输出顺序: MinerU → Flatten → Group → Validate)
                new_stage = None
                progress_hint = None  # 进度提示, 如 "3/10 页"

                if "[MinerU] 提交" in line:
                    new_stage = "mineru_uploading"
                elif "[MinerU] job_id=" in line or ("轮询" in line and "MinerU" not in _prev_stage):
                    new_stage = "mineru_parsing"
                elif "[MinerU]" in line and "/" in line and ("(" in line or "页" in line or "/" in line):
                    # MinerU 轮询进度: "[MinerU]   processing  3/10  (15s)"
                    new_stage = "mineru_parsing"
                    m = __import__("re").search(r'(\d+)/(\d+)', line)
                    if m:
                        progress_hint = f"{m.group(1)}/{m.group(2)} 页"
                elif "[MinerU] ✅ 完成" in line or "[MinerU] 下载" in line:
                    new_stage = "mineru_downloading"
                elif "[MinerU] ✅ PageJSON" in line or "[MinerU] ✅ zip" in line or "[MinerU] ✅ zip 解压" in line:
                    new_stage = "flatten_blocks"
                elif "[MinerU] ✅" in line:
                    new_stage = "flatten_blocks"
                elif "[Flatten] ✅" in line:
                    new_stage = "group_questions"
                elif "[Group] ✅ 切出" in line:
                    new_stage = "validate_questions"
                    m = __import__("re").search(r'切出 (\d+) 道', line)
                    if m: progress_hint = f"识别出 {m.group(1)} 道"
                elif "[Validate]" in line and "✅" in line:
                    new_stage = "done"
                elif "[MinerU] 完成" in line:
                    new_stage = "mineru_downloading"

                # 只在阶段变化或有进度提示时发 PROGRESS
                if new_stage and (new_stage != _prev_stage or progress_hint):
                    payload = {"type": "PROGRESS", "job_id": job_id, "stage": new_stage}
                    if progress_hint:
                        payload["progress"] = progress_hint
                    await FrameProto.send(writer, payload)
                    _prev_stage = new_stage
                    log.info(f"  📊 {job_id} → {new_stage}{' ('+progress_hint+')' if progress_hint else ''}")

        except Exception as e:
            log.error(f"  stdout 读取异常: {e}")

        # 等进程结束
        try:
            await asyncio.wait_for(proc.wait(), PIPELINE_TIMEOUT)
        except asyncio.TimeoutError:
            proc.kill()
            await proc.wait()
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": "pipeline timeout"})
            return

        if proc.returncode != 0:
            stderr = (await proc.stderr.read()).decode()
            log.error(f"  pipeline rc={proc.returncode}: {stderr[:500]}")
            await FrameProto.send(writer, {"type": "RESULT", "job_id": job_id, "error": f"pipeline rc={proc.returncode}: {stderr[:200]}"})
            return

        # ★ 上报阶段: 完成
        await FrameProto.send(writer, {"type": "PROGRESS", "job_id": job_id, "stage": "done"})

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
