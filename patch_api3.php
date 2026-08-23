<?php
$content = file_get_contents(__DIR__ . '/public/api.php');
$m = [];
if (preg_match('/if \(isAdmin\(\) && \$scope === \'manage_all.*?\}/s', $content, $m, PREG_OFFSET_CAPTURE)) {
    $block = $m[0][0];
    $pos = $m[0][1];
    echo "Found block at $pos, len=".strlen($block)."\n";
    $new = substr_replace($content, '', $pos, strlen($block));
    file_put_contents(__DIR__ . '/public/api.php', $new);
    echo "OK, deleted block\n";
} else {
    echo "NOT FOUND\n";
}
