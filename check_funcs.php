<?php
// 在 CLI 下 require 时不能作为请求路由运行，所以我们只提取函数定义看是否能正确解析
// 用 grep 的方式看我们要的三个函数是否真的在文件里
$f = file_get_contents('public/api.php');
preg_match_all('/function (pcvl_\w+)\s*\(/', $f, $m);
print_r($m[1]);
echo PHP_EOL;
echo 'total bytes: ' . strlen($f) . PHP_EOL;
// Find the function start for pcvl_isPaperCutterVL
$pos = strpos($f, 'function pcvl_isPaperCutterVL');
echo 'pcvl_isPaperCutterVL position: ' . $pos . PHP_EOL;
// Show 50 chars before it
echo 'Context before: ' . substr($f, max(0,$pos-50), 80) . PHP_EOL;
