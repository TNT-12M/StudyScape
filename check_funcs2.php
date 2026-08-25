<?php
// 查找函数定义的括号配对
$f = file_get_contents('public/api.php');
$pos = strpos($f, 'function pcvlConvertPaperCutterVL');
echo 'position: ' . $pos . PHP_EOL;
// 读取 2000 字符
echo substr($f, $pos, 2000);
echo PHP_EOL . '----END PREVIEW----' . PHP_EOL;
// 检查文件末尾
echo "file last 500 bytes:\n";
echo substr($f, -500);
