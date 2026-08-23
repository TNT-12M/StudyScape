<?php
$content = file_get_contents(__DIR__ . '/public/api.php');
$target = 'if (isAdmin() && $scope === \'manage_all\' && $educationLevel === \'\') {' . PHP_EOL .
          '                    jsonOut(false, \'管理员查看题库前请选择学段\');' . PHP_EOL .
          '                }';
$pos = strpos($content, $target);
if ($pos === false) {
    echo "TARGET NOT FOUND\n";
    // Try alternate: maybe CRLF vs LF
    $target2 = str_replace(PHP_EOL, "\r\n", $target);
    $pos2 = strpos($content, $target2);
    if ($pos2 !== false) {
        echo "Found with CRLF at pos $pos2\n";
        $new = substr_replace($content, '', $pos2, strlen($target2));
        file_put_contents(__DIR__ . '/public/api.php', $new);
        echo "OK CRLF\n";
    } else {
        // Search for just the distinctive line
        $m = [];
        preg_match('/if \(isAdmin\(\) && \$scope === \'manage_all.*?\}/s', $content, $m);
        if ($m) { echo "Regex match: ".strlen($m[0])." chars\n". wordwrap($m[0], 80)."\n"; }
        else echo "Regex also not found.\n";
    }
} else {
    $new = substr_replace($content, '', $pos, strlen($target));
    file_put_contents(__DIR__ . '/public/api.php', $new);
    echo "OK LF\n";
}
