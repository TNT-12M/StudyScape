@echo off
setlocal EnableDelayedExpansion
REM ==========================================================================
REM  学补在线刷题平台 · 一键启动脚本（Windows / CMD / PowerShell 通用）
REM  功能：
REM    1) 启动 PHP 内置 Web 服务器（以 public/ 为文档根，api.php + 全部静态页面）
REM    2) 可选：启动配套 Python 题目入库 / OCR 预热进程（预留接口）
REM    3) 打开浏览器自动访问首页
REM
REM  使用方法（两种方式，二选一即可）：
REM    A) 直接双击本 .bat 文件运行（最简单）
REM    B) 在 CMD 中进入项目目录后运行： start_server.bat
REM
REM  默认参数：
REM    监听地址 = 127.0.0.1
REM    监听端口 = 8080
REM    PHP 路径 = D:\php-8.5.9\php.exe   （如不匹配，请手动修改 PHP_BIN）
REM    配置文件 = %~dp0php.ini  （项目目录的 php.ini，启用 sqlite3/gd/fileinfo/curl）
REM ==========================================================================

cd /d "%~dp0"

REM ---------- 配置项：可按实际路径修改 ----------
set PHP_BIN=D:\php-8.5.9\php.exe
set PHP_INI=%~dp0php.ini
set HOST=127.0.0.1
set PORT=8080
set ROOT=%~dp0public
set URL=http://%HOST%:%PORT%/index.html

REM ---------- 检查 PHP ----------
if not exist "%PHP_BIN%" (
    echo [ERROR] 未找到 PHP: %PHP_BIN%
    echo        请先编辑 start_server.bat 把 PHP_BIN 改成真实路径。
    echo        例如：set PHP_BIN=C:\php8\php.exe
    pause
    exit /b 1
)

REM ---------- 检查项目关键文件 ----------
if not exist "%ROOT%\api.php" (
    echo [ERROR] 未找到 public\api.php，请确认此 bat 在项目根目录下。
    pause
    exit /b 1
)

if not exist "%ROOT%\index.html" (
    echo [ERROR] 未找到 public\index.html。
    pause
    exit /b 1
)

REM ---------- 检查 PHP 扩展（关键） ----------
"%PHP_BIN%" -c "%PHP_INI%" -m 2>nul | findstr /I "sqlite3 gd fileinfo" >nul
if errorlevel 1 (
    echo [WARN] 关键扩展未加载（sqlite3/gd/fileinfo 至少缺失一个），仍尝试启动。
    echo        如后续报错，可手动运行：  "%PHP_BIN%" -c "%PHP_INI%" -m
    echo        确认扩展加载情况。
)

REM ---------- 检查端口占用 ----------
netstat -ano | findstr ":%PORT%" | findstr LISTENING >nul
if not errorlevel 1 (
    echo [ERROR] 端口 %PORT% 已被占用，请先关闭占用程序，或修改脚本中的 PORT。
    pause
    exit /b 1
)

REM ---------- 确保所需目录存在 ----------
if not exist "%~dp0materials" mkdir "%~dp0materials"
if not exist "%~dp0uploads"   mkdir "%~dp0uploads"
if not exist "%~dp0data"      mkdir "%~dp0data"
if not exist "%~dp0data\logs" mkdir "%~dp0data\logs"

echo ============================================================
echo   学补在线刷题平台  启动中...
echo   项目目录: %~dp0
echo   文档根  : %ROOT%
echo   PHP     : %PHP_BIN%
echo   php.ini : %PHP_INI%
echo   访问地址: %URL%
echo   停止方法: 直接关闭本窗口 或 按 Ctrl+C
echo ============================================================
echo.

REM ---------- 可选：PHP 启动时顺带跑一次 extract.py 自检 ----------
echo [自检] Python 与 extract.py 依赖检查...
where python >nul 2>nul
if not errorlevel 1 (
    python -c "import sys, json" 2>nul
    if not errorlevel 1 (
        python "%~dp0extract.py" 2>nul | python -c "import sys,json as j; s=sys.stdin.read(); d=j.loads(s) if s.strip() else {}; print('   extract.py ready:', d.get('message','no-error'))" 2>nul
    )
) else (
    echo   [WARN] 未找到 python，DOC/PDF 文档解析功能将不可用。
)

REM ---------- 启动 PHP WebServer（文档根 = public/） ----------
echo.
echo [启动] PHP 内置服务器: %URL%
echo        首次运行会自动创建 exam.db（SQLite 数据库）
echo.

REM 打开浏览器
timeout /t 2 /nobreak >nul
start "" "%URL%"

REM 启动（前台阻塞）
"%PHP_BIN%" -c "%PHP_INI%" -S %HOST%:%PORT% -t "%ROOT%"

REM 服务器结束
echo.
echo [结束] PHP 服务器已停止。
pause
endlocal
