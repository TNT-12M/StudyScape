<?php
/**
 * 修复脚本：把错误放在题干(content)中的解析图片移到 explanation 字段
 *
 * 问题原因：旧版 pcvlConvertPaperCutterVL 把 analysis_images 也追加到了 content，
 * 导致题干中出现不相关的图片、同一张图片重复显示。
 *
 * 用法： php fix_images.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dbFile = __DIR__ . '/exam.db';
if (!file_exists($dbFile)) {
    echo "数据库文件不存在: $dbFile\n";
    exit(1);
}

$db = new SQLite3($dbFile);
$db->busyTimeout(5000);

// 查找所有 is_html=1 且 content 中含有 <img 的题目
$rows = $db->query("SELECT id, content, explanation, is_html FROM questions WHERE is_html = 1 AND content LIKE '%<img%'");
if (!$rows) {
    echo "查询失败: " . $db->lastErrorMsg() . "\n";
    exit(1);
}

$fixed = 0;
$scanned = 0;

while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
    $scanned++;
    $id = $row['id'];
    $content = $row['content'];
    $explanation = $row['explanation'] ?? '';

    // 提取 content 中的所有 <img> 块（连同外层 div）
    // 匹配 <div ...><img ...></div> 或独立的 <img ...>
    $imgBlockPattern = '/<div[^>]*>\s*<img[^>]*>\s*<\/div>/s';
    $standaloneImgPattern = '/<img[^>]*>/s';

    // 先提取 div 包裹的图片块
    preg_match_all($imgBlockPattern, $content, $divMatches);
    $divImgs = $divMatches[0];

    // 再提取独立 img（不在 div 中的）
    $contentWithoutDivImgs = preg_replace($imgBlockPattern, '', $content);
    preg_match_all($standaloneImgPattern, $contentWithoutDivImgs, $imgMatches);
    $standaloneImgs = $imgMatches[0];

    $allImgs = array_merge($divImgs, $standaloneImgs);

    if (empty($allImgs)) continue;

    // 提取每张图片的 base64 指纹（前 80 字符）
    $imgInfo = [];
    foreach ($allImgs as $imgHtml) {
        if (preg_match('/src=["\']?(data:image\/[^"\']+)["\']?/i', $imgHtml, $srcMatch)) {
            $src = $srcMatch[1];
            // 提取 base64 部分
            $b64 = preg_replace('/^data:image\/[a-z]+;base64,/i', '', $src);
            $fingerprint = substr($b64, 0, 80);
            $imgInfo[] = [
                'html' => $imgHtml,
                'fingerprint' => $fingerprint,
                'src' => $src,
            ];
        }
    }

    if (count($imgInfo) <= 1) continue; // 只有 0 或 1 张图，不需要修复

    // 去重：保留第一次出现的图片，后续重复的移除
    $seen = [];
    $keptImgs = [];
    $removedImgs = [];
    foreach ($imgInfo as $info) {
        if (isset($seen[$info['fingerprint']])) {
            $removedImgs[] = $info;
        } else {
            $seen[$info['fingerprint']] = true;
            $keptImgs[] = $info;
        }
    }

    if (empty($removedImgs)) {
        // 没有重复图片，但可能有多张不同图片
        // 检查：如果题干文字提到了"示意图"等，但图片数量 > 1，
        // 可能有多余的图片（如解析图被错误放入题干）
        // 保守策略：只处理明确的重复图片
        continue;
    }

    // 从 content 中移除重复的图片块
    $newContent = $content;
    foreach ($removedImgs as $info) {
        // 移除 div 包裹的图片块
        $newContent = str_replace($info['html'], '', $newContent);
        // 也尝试移除可能残留的空 div
        $newContent = preg_replace('/<div[^>]*>\s*<\/div>/', '', $newContent);
    }
    // 清理多余空行
    $newContent = preg_replace('/\n{3,}/', "\n\n", $newContent);
    $newContent = trim($newContent);

    // 把移除的图片追加到 explanation（如果 explanation 中没有这张图）
    $newExplanation = $explanation;
    foreach ($removedImgs as $info) {
        if (strpos($newExplanation, $info['fingerprint']) === false) {
            $newExplanation .= "\n\n" . $info['html'];
        }
    }
    $newExplanation = trim($newExplanation);

    // 更新数据库
    $stmt = $db->prepare("UPDATE questions SET content = :content, explanation = :explanation WHERE id = :id");
    $stmt->bindValue(':content', $newContent, SQLITE3_TEXT);
    $stmt->bindValue(':explanation', $newExplanation !== '' ? $newExplanation : null, SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();

    $fixed++;
    echo "题目 #$id: 移除 " . count($removedImgs) . " 张重复图片到解析\n";
}

$db->close();

echo "\n========== 修复完成 ==========\n";
echo "扫描题目: $scanned\n";
echo "修复题目: $fixed\n";
if ($fixed > 0) {
    echo "重复图片已从题干移至解析字段。\n";
} else {
    echo "没有发现需要修复的重复图片。\n";
}
