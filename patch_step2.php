<?php
$f = __DIR__ . '/public/api.php';
$c = file_get_contents($f);

$old = 'function importExtractQuestions(array $data, string $subject, array &$questions): void {
    // 模式1：扁平数组（每条已是标准结构）
    if (isset($data[0]) && is_array($data[0]) && (isset($data[0][\'content\']) || isset($data[0][\'题干\']))) {';
$new = 'function importExtractQuestions(array $data, string $subject, array &$questions): void {
    // 模式0：PaperCutter-VL 格式（含 question_id/question_content/question_options/question_images 等）
    if (pcvl_isPaperCutterVL($data)) {
        $pcvlSubject = $subject !== \'\' ? $subject : pcvl_detectSubject($data);
        pcvlConvertPaperCutterVL($data, $pcvlSubject, $questions);
        return;
    }
    // 模式1：扁平数组（每条已是标准结构）
    if (isset($data[0]) && is_array($data[0]) && (isset($data[0][\'content\']) || isset($data[0][\'题干\']))) {';
if (strpos($c, $old) === false) { echo "OLD NOT FOUND\n"; exit(1); }
$c = str_replace($old, $new, $c);

// 新追加到文件末尾的 PaperCutter-VL 辅助函数
$append = '

// =====================================================
// ============== PaperCutter-VL 格式识别与转换 ==============
// =====================================================

/**
 * 判断是否为 PaperCutter-VL 输出格式
 * 特征：顶层数组每条均为对象，含 question_content / question_options / question_type 等字段
 */
function pcvl_isPaperCutterVL(array $data): bool {
    if (!is_array($data) || isset($data[0]) && !is_array($data[0])) return false;
    if (!isset($data[0]) || !is_array($data[0])) return false;
    $keys = array_keys($data[0]);
    $required = [\'question_content\', \'question_type\'];
    $hit = 0;
    foreach ($required as $r) {
        if (in_array($r, $keys, true)) $hit++;
    }
    // 至少命中 2 个 PaperCutter 特有字段即判定为真
    if ($hit < 2) return false;
    // 且必须具备 PaperCutter 独特字段之一
    $paperCutterOnly = [\'question_id\', \'question_images\', \'analysis_images\', \'source_province\', \'source_year\'];
    foreach ($paperCutterOnly as $k) {
        if (in_array($k, $keys, true)) return true;
    }
    return false;
}

/**
 * 从整份 PaperCutter-VL 数据推断科目
 */
function pcvl_detectSubject(array $data): string {
    // 先看是否有 subject 字段非空
    foreach ($data as $q) {
        if (!empty($q[\'subject\']) && is_string($q[\'subject\'])) {
            $s = trim($q[\'subject\']);
            if ($s !== \'\') return $s;
        }
    }
    // 从 source 里猜（例如 "2025年安徽省初中学业水平考试 道德与法治（开卷）"）
    $subjectMap = [
        \'道德与法治\' => \'道德与法治\', \'政治\' => \'政治\',
        \'历史\' => \'历史\', \'地理\' => \'地理\',
        \'语文\' => \'语文\', \'数学\' => \'数学\', \'英语\' => \'英语\',
        \'物理\' => \'物理\', \'化学\' => \'化学\', \'生物\' => \'生物\',
        \'信息技术\' => \'信息技术\',
    ];
    foreach ($data as $q) {
        $src = $q[\'source\'] ?? \'\';
        $cnt = $q[\'question_content\'] ?? \'\';
        $blob = $src . $cnt;
        foreach ($subjectMap as $k => $v) {
            if ($k !== \'政治\' && mb_strpos($blob, $k) !== false) return $v;
        }
    }
    return \'综合\';
}

/**
 * PaperCutter-VL 格式 → 系统扁平数组
 * 主要处理：题型识别、选项（去除 A./B. 前缀）、题干 HTML 富化（base64 图片嵌入、表格内嵌）
 */
function pcvlConvertPaperCutterVL(array $data, string $subject, array &$questions): void {
    $typeMap = [
        \'单选题\' => \'single\', \'单选题（四选一）\' => \'single\',
        \'多选题\' => \'multiple\', \'多选题（不定项）\' => \'multiple\',
        \'判断题\' => \'judge\', \'对错题\' => \'judge\',
        \'填空题\' => \'fill\', \'多空填空题\' => \'multi_fill\',
        \'简答题\' => \'short\', \'问答题\' => \'short\', \'论述题\' => \'short\',
        \'选择题\' => \'single\',
    ];

    foreach ($data as $q) {
        if (!is_array($q)) continue;
        $rawType = $q[\'question_type\'] ?? \'\';
        $typeKey = $typeMap[$rawType] ?? \'single\';

        // 选项处理
        $rawOpts = $q[\'question_options\'] ?? [];
        $options = [];
        if (is_array($rawOpts) && !empty($rawOpts)) {
            foreach ($rawOpts as $opt) {
                if (!is_string($opt)) continue;
                // 去掉 "A. " / "A、" / "A)" / "A)" 这类前缀，保留内容
                $clean = preg_replace(\'/^\\s*[A-Z][.、)）:：\\s]+/\', \'\', $opt);
                // 如果只剩了一个字母（比如 "A"），就把原串留下
                if ($clean === \'\' && preg_match(\'/^\\s*([A-Z])\\s*$\', $opt, $m)) {
                    $options[] = $opt;
                } else {
                    $options[] = $clean !== \'\' ? $clean : $opt;
                }
            }
        }

        // 根据选项数推断类型（若只有 "选择题" 这种含糊标签）
        if ($rawType === \'选择题\') {
            $cnt = count($options);
            if ($cnt === 2) $typeKey = \'judge\';
            elseif ($cnt >= 4) $typeKey = \'single\';
        }

        // 答案处理（PaperCutter-VL 通常 answer 字段为空，这里尽力而为）
        $answerText = $q[\'answer\'] ?? \'\';
        $explanation = $q[\'resolve\'] ?? \'\';

        // 题干富化：把 question_images、analysis_images 里的 base64 补到 HTML 中
        $content = $q[\'question_content\'] ?? \'\';
        $tables = $q[\'question_tables\'] ?? [];
        $qImages = $q[\'question_images\'] ?? [];
        $aImages = $q[\'analysis_images\'] ?? [];
        $isHtml = false;

        // 若 content 本身已含 HTML（PaperCutter-VL 常常把 base64 <img> 直接塞在 content 里），直接保留
        if (preg_match(\'/<[a-zA-Z][^>]*>/\', $content)) {
            $isHtml = true;
        }
        // 把附加表格追加到 content
        if (is_array($tables) && !empty($tables)) {
            foreach ($tables as $t) {
                if (is_string($t) && trim($t) !== \'\') {
                    $content .= "\\n\\n" . $t;
                    $isHtml = true;
                }
            }
        }
        // 把 question_images / analysis_images 里独立的 base64 图追成 <img> 插到末尾
        $appendImgs = function (array $imgs) use (&$content, &$isHtml) {
            foreach ($imgs as $b64) {
                if (!is_string($b64) || trim($b64) === \'\') continue;
                // 若已经带 data: 前缀，直接用；否则补全
                if (preg_match(\'/^data:/i\', $b64)) {
                    $src = $b64;
                } else {
                    $src = \'data:image/jpeg;base64,\' . ltrim($b64);
                }
                $content .= "\\n\\n<div style=\\"text-align:center\\"><img src=\\"" . htmlspecialchars($src, ENT_QUOTES) . "\\" alt=\\"图片\\" style=\\"max-width:100%;height:auto;\\"></div>";
                $isHtml = true;
            }
        };
        $appendImgs($qImages);
        $appendImgs($aImages);

        // 推断难度
        $diff = intval($q[\'difficulty\'] ?? 0);
        if ($diff <= 0) $diff = importEstimateDifficulty($content);

        // 题干太长时用 content-box 展示全文（不再只截断 200 字）
        $points = 2.0; // 默认分
        $sourceLabel = $q[\'source\'] ?? \'\';
        if ($sourceLabel !== \'\') {
            $explanation = trim($explanation . "\\n（来源：" . $sourceLabel . "）");
        }

        $questions[] = [
            \'subject\'        => $subject,
            \'question_type\'  => $typeKey,
            \'category\'       => $typeKey,
            \'education_level\'=> \'\',   // 由外层按当前选择的学段覆盖
            \'content\'        => $content,
            \'options\'        => !empty($options) ? $options : null,
            \'correct_answer\' => $answerText,
            \'explanation\'    => $explanation !== \'\' ? $explanation : null,
            \'difficulty\'     => $diff,
            \'points\'         => $points,
            \'is_html\'        => $isHtml ? 1 : 0,
        ];
    }
}
';
file_put_contents($f, $c . $append);
echo "PaperCutter-VL conversion added\n";
