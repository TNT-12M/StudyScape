#!/usr/bin/env php
<?php
/**
 * 题库批量导入脚本
 * 用法: php import_questions.php <json文件路径> [--subject 生物] [--dry-run]
 *
 * JSON格式: 见 example_exam.json
 * - 支持选择题 (single/multiple)
 * - 支持非选择题 (fill) 每个小问独立入库
 */

// ==================== 配置 ====================
$db_host = 'localhost';
$db_name = 'exam_system';
$db_user = 'exam_user';
$db_pass = 'ExamUser@2026';

// 默认科目映射（从文件名或 JSON 中的标题自动提取）
$subjectMap = [
    '生物' => '生物', '化学' => '化学', '物理' => '物理',
    '数学' => '数学', '英语' => '英语', '语文' => '语文',
    '历史' => '历史', '地理' => '地理', '政治' => '政治',
    '信息技术' => '信息技术', '计算机' => '信息技术'
];

// ==================== 主逻辑 ====================
if ($argc < 2) {
    echo "用法: php import_questions.php <json文件> [--subject 科目] [--dry-run]\n";
    echo "示例: php import_questions.php exam.json --subject 生物\n";
    exit(1);
}

$jsonFile = $argv[1];
$subjectOverride = null;
$dryRun = false;

for ($i = 2; $i < $argc; $i++) {
    if ($argv[$i] === '--dry-run') $dryRun = true;
    if ($argv[$i] === '--subject' && isset($argv[$i + 1])) $subjectOverride = $argv[++$i];
}

if (!file_exists($jsonFile)) {
    echo "错误: 文件不存在 - $jsonFile\n";
    exit(1);
}

$rawJson = file_get_contents($jsonFile);
if (!$rawJson) {
    echo "错误: 无法读取文件\n";
    exit(1);
}

$data = json_decode($rawJson, true);
if (!$data) {
    echo "错误: JSON 解析失败 - " . json_last_error_msg() . "\n";
    exit(1);
}

// 确定科目
$subject = $subjectOverride ?? detectSubject($data, $jsonFile);
echo "科目: $subject\n";
echo "模式: " . ($dryRun ? "仅预览" : "写入数据库") . "\n\n";

// 连接数据库
$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 提取所有题目
$questions = [];
extractQuestions($data, $subject, $questions);

echo "解析到 " . count($questions) . " 道题\n\n";

if ($dryRun) {
    // 预览模式：展示前3题
    echo "=== 预览前3题 ===\n";
    $preview = array_slice($questions, 0, 3);
    foreach ($preview as $idx => $q) {
        echo "\n--- 第 " . ($idx + 1) . " 题 ---\n";
        echo "题型: {$q['question_type']}\n";
        echo "题干: " . mb_substr($q['content'], 0, 60) . (mb_strlen($q['content']) > 60 ? '...' : '') . "\n";
        if ($q['options']) {
            echo "选项: " . json_encode($q['options'], JSON_UNESCAPED_UNICODE) . "\n";
        }
        echo "答案: {$q['correct_answer']}\n";
        echo "分值: {$q['points']}\n";
        echo "难度: {$q['difficulty']}\n";
    }
    echo "\n（仅预览，未写入数据库）\n";
    exit(0);
}

// 写入数据库
$stats = ['inserted' => 0, 'skipped' => 0, 'errors' => 0];
$stmt = $pdo->prepare("INSERT INTO questions (subject, question_type, content, options, correct_answer, explanation, difficulty, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($questions as $q) {
    try {
        // 查重: 相同题干 + 答案视为重复
        $check = $pdo->prepare("SELECT id FROM questions WHERE content = ? AND correct_answer = ? AND subject = ? LIMIT 1");
        $check->execute([$q['content'], $q['correct_answer'], $subject]);
        if ($check->fetch()) {
            $stats['skipped']++;
            continue;
        }

        $stmt->execute([
            $q['subject'],
            $q['question_type'],
            $q['content'],
            $q['options'] ? json_encode($q['options'], JSON_UNESCAPED_UNICODE) : null,
            $q['correct_answer'],
            $q['explanation'] ?? null,
            $q['difficulty'],
            $q['points']
        ]);
        $stats['inserted']++;
    } catch (Exception $e) {
        $stats['errors']++;
        echo "错误: " . $e->getMessage() . "\n";
    }
}

echo "=== 导入完成 ===\n";
echo "成功入库: {$stats['inserted']} 道\n";
echo "跳过重复: {$stats['skipped']} 道\n";
echo "失败: {$stats['errors']} 道\n";

// ==================== 函数定义 ====================

function detectSubject(array $data, string $filePath): string {
    global $subjectMap;
    // 从标题中提取
    $title = $data['试卷名称'] ?? $data['title'] ?? '';
    foreach ($subjectMap as $keyword => $name) {
        if (str_contains($title, $keyword)) return $name;
    }
    // 从文件名提取
    $basename = pathinfo($filePath, PATHINFO_FILENAME);
    foreach ($subjectMap as $keyword => $name) {
        if (str_contains($basename, $keyword)) return $name;
    }
    return '生物'; // 默认
}

function extractQuestions(array $data, string $subject, array &$questions): void {
    // 处理各卷
    foreach ($data as $key => $section) {
        if (!is_array($section) || !isset($section['题目列表'])) continue;

        $title = is_string($key) ? $key : '';
        $isChoice = str_contains($title, '选择') || str_contains($title, '选择题');
        $isNonChoice = str_contains($title, '非选择') || str_contains($title, '非选择题') || str_contains($title, '主观题') || str_contains($title, '填空') || str_contains($title, '简答');

        $volumePoints = $section['小题分值'] ?? 2; // 选择题每题分值
        $list = $section['题目列表'];

        foreach ($list as $q) {
            // 优先判断是否有小问结构（非选择题）
            if (isset($q['小问'])) {
                $subQuestions = buildNonChoiceQuestions($q, $subject);
                foreach ($subQuestions as $sub) {
                    $questions[] = $sub;
                }
            } elseif ($isChoice || isset($q['选项'])) {
                $questions[] = buildChoiceQuestion($q, $subject, $volumePoints);
            }
        }
    }
}

function buildChoiceQuestion(array $q, string $subject, float $defaultPoints): array {
    $options = $q['选项'] ?? [];
    $letters = array_keys($options);
    $count = count($options);

    // 题型判断
    if ($count === 2) {
        $type = 'judge'; // 判断题
    } elseif ($count === 4) {
        $type = 'single'; // 单选题
    } else {
        $type = 'multiple'; // 多选题
    }

    // 构建 options 数组: ["A. 选项内容", "B. 选项内容", ...]
    $optionArray = [];
    foreach ($options as $letter => $text) {
        $optionArray[] = strtoupper($letter) . '. ' . $text;
    }

    // 构建答案: 找到正确选项的完整文本
    $answerLetter = strtoupper($q['答案'] ?? '');
    $correctText = '';
    foreach ($options as $letter => $text) {
        if (strtoupper($letter) === $answerLetter) {
            $correctText = strtoupper($letter) . '. ' . $text;
            break;
        }
    }
    if (!$correctText) $correctText = $answerLetter;

    // 自动判定难度
    $difficulty = estimateDifficulty($q['题干'] ?? '');

    return [
        'subject' => $subject,
        'question_type' => $type,
        'content' => $q['题干'] ?? '',
        'options' => $optionArray,
        'correct_answer' => $correctText,
        'explanation' => $q['解析'] ?? null,
        'difficulty' => $difficulty,
        'points' => $q['分值'] ?? $defaultPoints
    ];
}

function buildNonChoiceQuestions(array $q, string $subject): array {
    $questions = [];
    $background = $q['题干'] ?? '';
    $totalPoints = $q['分值'] ?? 5;
    $subQuestions = $q['小问'] ?? [];

    $count = count($subQuestions);
    $pointsPerSub = $count > 0 ? round($totalPoints / $count, 1) : $totalPoints;

    foreach ($subQuestions as $sub) {
        $questionText = $sub['问题'] ?? '';
        $refAnswer = $sub['参考答案'] ?? '';
        $subNum = $sub['小问序号'] ?? '';

        // 组合: 如果有多个小问，每个作为独立题
        // 如果有题干背景，作为 content 前缀
        $content = $background;
        if ($subNum) $content .= "\n\n{$subNum} ";
        $content .= $questionText;

        $difficulty = estimateDifficulty($background . $questionText);

        // 参考答案同时作为 correct_answer 和 explanation
        $questions[] = [
            'subject' => $subject,
            'question_type' => 'fill',
            'content' => $content,
            'options' => null,
            'correct_answer' => $refAnswer,
            'explanation' => $refAnswer,
            'difficulty' => $difficulty,
            'points' => $pointsPerSub
        ];
    }

    // 如果没有小问结构，整题作为一道填空题
    if (empty($subQuestions)) {
        $refAnswer = $q['参考答案'] ?? ($q['答案'] ?? '');
        $questions[] = [
            'subject' => $subject,
            'question_type' => 'fill',
            'content' => $background,
            'options' => null,
            'correct_answer' => $refAnswer,
            'explanation' => $refAnswer,
            'difficulty' => estimateDifficulty($background),
            'points' => $totalPoints
        ];
    }

    return $questions;
}

function estimateDifficulty(string $text): int {
    $len = mb_strlen($text);
    // 简单规则: 题干越长，难度越高
    if ($len < 50) return 1;
    if ($len < 150) return 2;
    if ($len < 300) return 3;
    if ($len < 500) return 4;
    return 5;
}
