<?php
$v = function (string $f): string {
    $p = __DIR__ . '/' . $f;
    return is_file($p) ? (string) filemtime($p) : '1';
};
?><!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0a0d18">
<title>狼人杀 · 联机版</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🐺</text></svg>">
<link rel="stylesheet" href="assets/style.css?v=<?= $v('assets/style.css') ?>">
</head>
<body>
<div id="app"></div>
<div id="toast" class="toast"></div>
<div id="modal" class="modal-wrap" hidden></div>
<script src="assets/app.js?v=<?= $v('assets/app.js') ?>"></script>
</body>
</html>
