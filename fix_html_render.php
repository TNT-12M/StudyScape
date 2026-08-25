<?php
// Fix practice.html
$f = 'public/practice.html';
$c = file_get_contents($f);

// Replace renderBlanks function
$old = 'function renderBlanks(content) {
    if (!content) return '''';
    return escapeHtml(content).replace(/\{\{\s*(\d+)\s*\}\}/g,
        (_, n) => \x60<span class=\"blank-tag\">第\x24{n}空</span>\x60);
}';

echo strpos($c, 'function renderBlanks(content)') !== false ? 'Found' : 'Not found';
echo PHP_EOL;
