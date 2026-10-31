<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $http_status ?? 404; ?> — 学境 StudyScape</title>
<style>
  :root {
    --bg: #1f1f1f;
    --fg: #e9e9e9;
    --accent: #4c86f9;
    --accent-ghost: rgba(76, 134, 249, .15);
    --muted: #999;
    --danger: #f6bb02;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: var(--bg);
    color: var(--fg);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC",
                 "Hiragino Sans GB", "Microsoft YaHei", Roboto, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 2rem;
  }
  .wrap { max-width: 480px; width: 100%; }
  .code {
    font-size: clamp(4rem, 18vw, 7rem);
    font-weight: 700;
    line-height: 1;
    letter-spacing: -0.04em;
    background: linear-gradient(135deg, var(--accent) 0%, var(--danger) 100%);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: .5rem;
  }
  .title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: .5rem;
  }
  .desc {
    color: var(--muted);
    font-size: .95rem;
    margin-bottom: 2rem;
    line-height: 1.6;
  }
  .desc strong {
    color: var(--fg);
    font-weight: 500;
  }
  .actions { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }
  .btn {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .55rem 1.25rem;
    border-radius: 6px;
    font-size: .9rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid var(--accent);
    color: var(--accent);
    background: var(--accent-ghost);
    transition: background .15s, border-color .15s, color .15s;
  }
  .btn:hover { background: rgba(76, 134, 249, .25); }
  .btn.primary {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
  }
  .btn.primary:hover { background: #3d6fe0; }
  .icon { font-size: 1rem; }
  footer {
    margin-top: 2.5rem;
    font-size: .75rem;
    color: #555;
  }
</style>
</head>
<body>
  <div class="wrap">
    <div class="code"><?php echo $http_status ?? 404; ?></div>
    <div class="title"><?php echo $title ?? '您访问的页面不存在'; ?></div>
    <div class="desc"><?php echo $message ?? '生活总归带点荒谬'; ?></div>
    <div class="actions">
      <a class="btn primary" href="/admin/" onclick="if(window.parent !== window) window.parent.location.href='/admin/'; else window.location.href='/admin/'; return false;">
        <span class="icon">←</span> 返回后台
      </a>
      <button class="btn" onclick="history.length>1?history.back():location.href='/';">
        <span class="icon">↩</span> 上一页
      </button>
    </div>
    <footer>学境 StudyScape · 学习的新境界</footer>
  </div>
</body>
</html>
