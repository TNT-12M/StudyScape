<?php
$content = file_get_contents(__DIR__ . '/public/api.php');
$oldBlock = '                // 仅在"题目管理"范围（scope=manage_all）要求管理员必须选定学段；' . PHP_EOL .
            '                // 浏览 Tab 中管理员也允许"全部"跨学段查看，避免下拉"全部"直接报错。' . PHP_EOL .
            '                $scope = isset($_GET[\'scope\']) ? (string)$_GET[\'scope\'] : (isset($_POST[\'scope\']) ? (string)$_POST[\'scope\'] : \'\');' . PHP_EOL .
            '                if (isAdmin() && $scope === \'manage_all\' && $educationLevel === \'\') {' . PHP_EOL .
            '                    jsonOut(false, \'管理员查看题库前请选择学段\');' . PHP_EOL .
            '                }';
$newBlock = '                // 题库浏览和题目管理已合并为同一界面，管理员始终带 scope=manage_all；' . PHP_EOL .
            '                // 不再以 scope 作为强制学段拦截的依据，"全部学段"（空 educationLevel）允许管理员跨学段查看。' . PHP_EOL .
            '                $scope = isset($_GET[\'scope\']) ? (string)$_GET[\'scope\'] : (isset($_POST[\'scope\']) ? (string)$_POST[\'scope\'] : \'\');';
if (strpos($content, $oldBlock) === false) {
    echo "OLD BLOCK NOT FOUND, searching for partial signature...\n";
    $s = strpos($content, '仅在"题目管理"范围');
    echo "Signature position: $s\n";
    if ($s !== false) echo substr($content, $s-50, 400);
} else {
    $new = str_replace($oldBlock, $newBlock, $content);
    file_put_contents(__DIR__ . '/public/api.php', $new);
    echo "OK\n";
}
