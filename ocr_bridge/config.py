#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
StudyScape 分布式架构 — 配置加载器
=================================
与 PHP 端 server.env 格式完全兼容 (KEY=VALUE, 支持 # 注释, 支持 "val" 引号)

优先级: 环境变量 > server.env 文件 > 默认值

用法:
    from config import env, env_int, env_bool, STORAGE_ROOT, ...
    # 或直接 import config 然后 config.SERVER_HOST
"""

import os


def _find_root():
    """向上搜索项目根目录 (包含 server.env 的目录)"""
    here = os.path.dirname(os.path.abspath(__file__))
    # ocr_bridge/ → 项目根 = 上上级
    return os.path.dirname(here)


ROOT_DIR = _find_root()


def _load_env_file(path):
    """加载 .env 格式文件 → dict"""
    result = {}
    if not os.path.isfile(path):
        return result
    with open(path, "r", encoding="utf-8") as f:
        for raw_line in f:
            line = raw_line.strip()
            if not line or line.startswith("#"):
                continue
            eq = line.find("=")
            if eq < 0:
                continue
            key = line[:eq].strip()
            val = line[eq + 1:].strip()
            # 去掉首尾引号
            if (val.startswith('"') and val.endswith('"')) or \
               (val.startswith("'") and val.endswith("'")):
                val = val[1:-1]
            if key:
                result[key] = val
    return result


# 加载 server.env
_ENV_FILE = os.path.join(ROOT_DIR, "server.env")
_ENV = _load_env_file(_ENV_FILE)


def env(key, default=None):
    """统一取值: 环境变量 > server.env > 默认值"""
    v = os.environ.get(key)
    if v is not None and v != "":
        return v
    v = _ENV.get(key)
    if v is not None and v != "":
        return v
    return default


def env_int(key, default=0):
    v = env(key, default)
    try:
        return int(v)
    except (TypeError, ValueError):
        return default


def env_bool(key, default=False):
    v = str(env(key, "true" if default else "false")).lower()
    return v in ("1", "true", "yes", "on")


# ======================= 预定义配置 =======================
# 让各模块可以直接 from config import SERVER_HOST

# --- Bridge Client 连接 Bridge Server ---
# 家里云 bridge_client 连接公网机 bridge_server
BRIDGE_REMOTE_HOST = env("BRIDGE_REMOTE_HOST", "127.0.0.1")
BRIDGE_REMOTE_PORT = env_int("BRIDGE_REMOTE_PORT", 1200)

# --- Bridge Server 监听配置 (公网机) ---
BRIDGE_LISTEN_HOST = env("BRIDGE_LISTEN_HOST", "0.0.0.0")
BRIDGE_LISTEN_PORT = env_int("BRIDGE_LISTEN_PORT", 1200)
PHP_BRIDGE_HTTP_PORT = env_int("PHP_BRIDGE_HTTP_PORT", 1201)

# --- 家里云 SSH 连接 (公网机 rsync 题库用) ---
HOME_SSH_HOST = env("HOME_SSH_HOST", "")
HOME_SSH_PORT = env_int("HOME_SSH_PORT", 22)
HOME_SSH_USER = env("HOME_SSH_USER", "lian")

# --- 题库 DB 路径 ---
# 家里云本地路径 / 公网机本地缓存路径
QUESTIONS_DB_PATH = env("QUESTIONS_DB_PATH", "/data/StudyScape/questions.db")
HOME_QUESTIONS_DB = env("HOME_QUESTIONS_DB", "/data/StudyScape/questions.db")

# --- 家里云存储根 ---
_STORAGE_ROOT = env("STUDYSCAPE_STORAGE_ROOT") or env("HOME_STORAGE_ROOT")
if _STORAGE_ROOT:
    STORAGE_ROOT = _STORAGE_ROOT
elif os.path.isdir("/data/StudyScape"):
    STORAGE_ROOT = "/data/StudyScape"
else:
    STORAGE_ROOT = os.path.join(ROOT_DIR, "data", "StudyScape")

KEY_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "bridge_key.txt")
