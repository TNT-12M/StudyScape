<?php
// =====================================================
// ============== 批量导入辅助函数 =====================
// =====================================================
// 兼容 import_exam.json 结构：
//   {试卷名称, 第I卷选择题: {题目列表:[{题号,题干,选项:{A:..}, 答案}]},
//    第II卷非选择题:{题目列表:[{题号,题干,分值,小问:[{小问序号,问题,参考答案}]}]}}
// 也支持扁平数组：[{subject, question_type, content, options, correct_answer, explanation, ...}]
function importExtractQuestions(array $data, string $subject, array &$questions, string $educationLevel = ''): void {
    // 模式0a：PaperCutter-VL 包装格式（{match_key, paper_name, subject, education_level, questions: [...]}）
    if (isset($data['questions']) && is_array($data['questions'])) {
        // 从包装层提取科目（覆盖"综合"默认值）
        if (!empty($data['subject']) && ($subject === '' || $subject === '综合')) {
            $subject = trim($data['subject']);
        }
        // 从包装层提取学段 (覆盖空)
        if ($educationLevel === '' && !empty($data['education_level'])) {
            $educationLevel = trim($data['education_level']);
        }
        $data = $data['questions'];
    }
    // 模式0b：PaperCutter-VL 扁平格式（含 question_id/question_content/question_options/question_images 等）
    if (pcvl_isPaperCutterVL($data)) {
        $pcvlSubject = $subject !== '' ? $subject : pcvl_detectSubject($data);
        $pcvlLevel = $educationLevel !== '' ? $educationLevel : pcvl_detectLevel($data);
        pcvlConvertPaperCutterVL($data, $pcvlSubject, $questions, $pcvlLevel);
        return;
    }
    // 模式1：扁平数组（每条已是标准结构）
    if (isset($data[0]) && is_array($data[0]) && (isset($data[0]['content']) || isset($data[0]['题干']))) {
        foreach ($data as $q) {
            $questions[] = importNormalizeFlat($q, $subject);
        }
        return;
    }
    // 模式2：按"卷"分组（import_exam.json 标准结构）
    foreach ($data as $key => $section) {
        if (!is_array($section) || !isset($section['题目列表'])) continue;
        if (!is_string($key)) continue;
        $isChoice    = str_contains($key, '选择');
        $isNonChoice = str_contains($key, '非选择') || str_contains($key, '主观') || str_contains($key, '填空') || str_contains($key, '简答');
        $defaultPts  = $section['小题分值'] ?? 2;
        $list = $section['题目列表'];
        foreach ($list as $q) {
            if (isset($q['小问'])) {
                // 非选择题：每个小问拆为独立题目
                importBuildNonChoice($q, $subject, $questions);
            } elseif ($isChoice || isset($q['选项'])) {
                $questions[] = importBuildChoice($q, $subject, (float)$defaultPts);
            } elseif ($isNonChoice) {
                importBuildNonChoice($q, $subject, $questions);
            }
        }
    }
}

function importNormalizeFlat(array $q, string $subject): array {
    $type = $q['question_type'] ?? ($q['题型'] ?? 'single');
    $options = $q['options'] ?? ($q['选项'] ?? null);
    if (is_array($options) && !isset($options[0])) {
        // 关联数组 {A:..,B:..} → 索引数组 ['A. ..','B. ..']
        $arr = [];
        foreach ($options as $k => $v) { $arr[] = strtoupper($k) . '. ' . $v; }
        $options = $arr;
    }
    $answer = $q['correct_answer'] ?? ($q['answer'] ?? ($q['答案'] ?? ($q['参考答案'] ?? '')));
    if (is_array($options) && is_string($answer) && strlen($answer) > 0) {
        $letter = strtoupper(trim($answer));
        // 若只填了字母，扩展为 "A. 选项内容"
        if (strlen($letter) === 1 && preg_match('/^[A-Z]$/', $letter)) {
            foreach ($options as $opt) {
                if (str_starts_with($opt, $letter . '.')) { $answer = $opt; break; }
            }
        }
    }
    return [
        'subject'        => normalizeSubject($q['subject'] ?? $subject),
        'question_type'  => $type,
        'category'       => $q['category'] ?? $type,
        'education_level'=> $q['education_level'] ?? 'junior',
        'content'        => $q['content'] ?? ($q['题干'] ?? ''),
        'options'        => $options,
        'correct_answer' => $answer,
        'explanation'    => $q['explanation'] ?? ($q['resolve'] ?? ($q['解析'] ?? null)),
        'difficulty'     => importEstimateDifficulty($q['content'] ?? ($q['题干'] ?? '')),
        'points'         => (float)($q['points'] ?? 1.0)
    ];
}

function importBuildChoice(array $q, string $subject, float $defaultPts): array {
    $rawOptions = $q['选项'] ?? [];
    $letters    = array_keys($rawOptions);
    $count      = count($rawOptions);
    if ($count === 2)       $type = 'judge';
    elseif ($count === 4)  $type = 'single';
    else                   $type = 'multiple';
    $optionArray = [];
    foreach ($rawOptions as $letter => $text) {
        $optionArray[] = strtoupper($letter) . '. ' . $text;
    }
    $answerLetter = strtoupper($q['答案'] ?? '');
    $correctText  = $answerLetter;
    foreach ($rawOptions as $letter => $text) {
        if (strtoupper($letter) === $answerLetter) {
            $correctText = strtoupper($letter) . '. ' . $text;
            break;
        }
    }
    return [
        'subject'        => $subject,
        'question_type'  => $type,
        'category'       => $q['category'] ?? $type,
        'education_level'=> $q['education_level'] ?? 'junior',
        'content'        => $q['题干'] ?? '',
        'options'        => $optionArray,
        'correct_answer' => $correctText,
        'explanation'    => $q['解析'] ?? null,
        'difficulty'     => importEstimateDifficulty($q['题干'] ?? ''),
        'points'         => (float)($q['分值'] ?? $defaultPts)
    ];
}

function importBuildNonChoice(array $q, string $subject, array &$questions): void {
    $background   = $q['题干'] ?? '';
    $totalPoints  = (float)($q['分值'] ?? 5);
    $subQuestions = $q['小问'] ?? [];
    $count        = count($subQuestions);
    $perSub       = $count > 0 ? round($totalPoints / $count, 1) : $totalPoints;
    foreach ($subQuestions as $sub) {
        $qText = $sub['问题'] ?? '';
        $ref   = $sub['参考答案'] ?? '';
        $subNo = $sub['小问序号'] ?? '';
        $content = $background;
        if ($subNo !== '') $content .= "\n\n" . $subNo . ' ';
        $content .= $qText;
        $questions[] = [
            'subject'        => $subject,
            'question_type'  => 'fill',
            'category'       => 'fill',
            'education_level'=> $q['education_level'] ?? 'junior',
            'content'        => $content,
            'options'        => null,
            'correct_answer' => $ref,
            'explanation'    => $ref,
            'difficulty'     => importEstimateDifficulty($background . $qText),
            'points'         => $perSub
        ];
    }
    // 没有小问结构，整题作为一道填空
    if (empty($subQuestions)) {
        $ref = $q['参考答案'] ?? ($q['答案'] ?? '');
        $questions[] = [
            'subject'        => $subject,
            'question_type'  => 'fill',
            'content'        => $background,
            'options'        => null,
            'correct_answer' => $ref,
            'explanation'    => $ref,
            'difficulty'     => importEstimateDifficulty($background),
            'points'         => $totalPoints
        ];
    }
}

function importEstimateDifficulty(string $text): int {
    $len = mb_strlen($text);
    if ($len < 50)  return 1;
    if ($len < 150) return 2;
    if ($len < 300) return 3;
    if ($len < 500) return 4;
    return 5;
}


// =====================================================
// ======== P1-B 模块：公共帮助函数 ====================
// =====================================================

/**
 * 学生端加载 questions：统一处理 options JSON、去掉 correct_answer / explanation
 * 输入 $links: [[question_id, sort_order, points], ...]
 */
function examB_loadQuestionsForStudent($db, array $links): array {
    $ids = array_filter(array_column($links, 'question_id'));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $qrows = dbFetchAll($db, "SELECT * FROM questions WHERE id IN ($in)", $ids);
    $byId = [];
    foreach ($qrows as $q) {
        $q['options']    = $q['options'] ? json_decode($q['options'], true) : null;
        $q['difficulty'] = (int)$q['difficulty'];
        $q['points']     = (float)$q['points'];
        // 答题中：永不返回 correct_answer / explanation
        unset($q['correct_answer'], $q['explanation']);
        $byId[(int)$q['id']] = $q;
    }
    $result = [];
    foreach ($links as $link) {
        $qid = (int)($link['question_id'] ?? 0);
        if (!isset($byId[$qid])) continue;
        $item = $byId[$qid];
        if (isset($link['points'])) $item['paper_points'] = (float)$link['points'];
        $result[] = $item;
    }
    return $result;
}

/**
 * 学生端加载 questions：**带正确答案**（仅用于结果页）
 */
function examB_loadQuestionsWithAnswer($db, array $qids): array {
    $ids = array_values(array_filter(array_map('intval', $qids)));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));
    $qrows = dbFetchAll($db, "SELECT * FROM questions WHERE id IN ($in)", $ids);
    $byId = [];
    foreach ($qrows as $q) {
        $q['options']        = $q['options'] ? json_decode($q['options'], true) : null;
        $q['difficulty']     = (int)$q['difficulty'];
        $q['points']         = (float)$q['points'];
        $q['correct_answer'] = $q['correct_answer'] ?? '';
        $byId[(int)$q['id']] = $q;
    }
    $out = [];
    foreach ($ids as $id) { if (isset($byId[$id])) $out[] = $byId[$id]; }
    return $out;
}

function examB_sanitizePaper($paper): ?array {
    if (!$paper) return null;
    $paper['duration_minutes'] = (int)$paper['duration_minutes'];
    $paper['is_published'] = (int)$paper['is_published'];
    $paper['created_by'] = $paper['created_by'] ? (int)$paper['created_by'] : null;
    return $paper;
}

function examB_getAttemptRow($db, int $aid): ?array {
    $r = dbFetchOne($db, "SELECT * FROM exam_attempts WHERE id=?", [$aid]);
    if (!$r) return null;
    $r['duration_minutes'] = (int)$r['duration_minutes'];
    $r['paper_id'] = $r['paper_id'] ? (int)$r['paper_id'] : null;
    $r['self_score_total'] = $r['self_score_total'] !== null ? (float)$r['self_score_total'] : null;
    return $r;
}

/**
 * 返回本次作答允许出现的题目及其最高自评分值。
 * 这张白名单同时用于提交答案和自评，避免客户端伪造题号或分数。
 */
function examB_attemptQuestionPoints($db, array $attempt): array {
    if (!empty($attempt['paper_id'])) {
        $rows = dbFetchAll($db, "SELECT question_id, points FROM paper_questions WHERE paper_id=?", [(int)$attempt['paper_id']]);
        $points = [];
        foreach ($rows as $row) $points[(int)$row['question_id']] = max(0.0, (float)$row['points']);
        return $points;
    }
    $rows = dbFetchAll($db, "SELECT ea.question_id, COALESCE(q.points, 1.0) AS points FROM exam_answers ea LEFT JOIN questions q ON q.id=ea.question_id WHERE ea.attempt_id=?", [(int)$attempt['id']]);
    $points = [];
    foreach ($rows as $row) $points[(int)$row['question_id']] = max(0.0, (float)$row['points']);
    return $points;
}

/**
 * 构建提交后的结果页 payload（供 attempt_result / practice_result 调用）
 * $isExam: true=组卷（含试卷信息 + paper_points）；false=自由刷题
 */
function examB_buildResult($db, int $aid, int $uid, bool $isExam): array {
    $attempt = examB_getAttemptRow($db, $aid);
    $paper = null;
    $questionsOrdered = [];
    if ($attempt['paper_id']) {
        $paper = examB_sanitizePaper(dbFetchOne($db, "SELECT * FROM papers WHERE id=?", [(int)$attempt['paper_id']]));
        $links = dbFetchAll($db,
            "SELECT question_id, sort_order, points FROM paper_questions WHERE paper_id=? ORDER BY sort_order ASC, id ASC",
            [(int)$attempt['paper_id']]
        );
        $ids = array_column($links, 'question_id');
        $questionsById = [];
        foreach (examB_loadQuestionsWithAnswer($db, $ids) as $q) { $questionsById[(int)$q['id']] = $q; }
        foreach ($links as $link) {
            $qid = (int)$link['question_id'];
            if (!isset($questionsById[$qid])) continue;
            $item = $questionsById[$qid];
            $item['paper_points'] = (float)$link['points'];
            $item['sort_order'] = (int)$link['sort_order'];
            $questionsOrdered[] = $item;
        }
    } else {
        // practice：按 exam_answers 写入顺序
        $ans = dbFetchAll($db, "SELECT id, question_id FROM exam_answers WHERE attempt_id=? ORDER BY id ASC", [$aid]);
        $ids = array_column($ans, 'question_id');
        $questionsById = [];
        foreach (examB_loadQuestionsWithAnswer($db, $ids) as $q) { $questionsById[(int)$q['id']] = $q; }
        foreach ($ans as $row) {
            $qid = (int)$row['question_id'];
            if (isset($questionsById[$qid])) $questionsOrdered[] = $questionsById[$qid];
        }
    }

    // 读取作答 + 自评
    $ansRows = dbFetchAll($db, "SELECT question_id, student_answer, self_correct, self_score, judged_at FROM exam_answers WHERE attempt_id=?", [$aid]);
    $ansMap = [];
    foreach ($ansRows as $a) {
        $qid = (int)$a['question_id'];
        $a['self_correct'] = $a['self_correct'] !== null ? (int)$a['self_correct'] : null;
        $a['self_score']   = $a['self_score']   !== null ? (float)$a['self_score'] : null;
        $ansMap[$qid] = $a;
    }

    $maxPossible = 0.0;
    $answeredCount = 0;
    $graded = 0; // 已自评题数（self_correct 非空）
    $correctCount = 0;
    foreach ($questionsOrdered as &$q) {
        $qid = (int)$q['id'];
        $a = $ansMap[$qid] ?? null;
        $sa = $a['student_answer'] ?? null;
        // 多空 / 多选：如果是 JSON 数组字符串，返回数组（便于前端渲染）
        if (is_string($sa) && in_array($q['question_type'], ['multi_fill', 'fill', 'multi'], true)) {
            $dec = json_decode($sa, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) $sa = $dec;
        }
        $q['student_answer'] = $sa;
        $q['self_correct']   = $a['self_correct'] ?? null;
        $q['self_score']     = $a['self_score'] ?? null;
        $q['judged_at']      = $a['judged_at'] ?? null;
        $q['max_points']     = (float)($q['paper_points'] ?? $q['points'] ?? 1.0);
        $maxPossible += $q['max_points'];
        if ($sa !== null && $sa !== '' && $sa !== []) $answeredCount++;
        if ($q['self_correct'] !== null) {
            $graded++;
            if ($q['self_correct'] === 1) $correctCount++;
        }
    }

    return [
        'attempt'           => $attempt,
        'paper'             => $paper,
        'questions'         => $questionsOrdered,
        'summary'           => [
            'total_count'       => count($questionsOrdered),
            'answered_count'    => $answeredCount,
            'graded_count'      => $graded,
            'correct_count'     => $correctCount,
            'max_possible'      => $maxPossible,
            'self_score_total'  => $attempt['self_score_total'],
        ],
    ];
}

// =====================================================
// ============== PaperCutter-VL 格式识别与转换 ==============
// =====================================================

/**
 * 从整份 PaperCutter-VL 数据推断学段（junior/senior）
 * 优先级：显式字段 → 来源文本 → 题干文本 → 默认 junior
 */
function pcvl_detectLevel(array $data): string {
    // 1️⃣ 显式字段优先
    foreach ($data as $q) {
        if (!is_array($q)) continue;
        foreach (['education_level', 'educationLevel', 'level', '学段', 'grade'] as $key) {
            if (!empty($q[$key]) && is_string($q[$key])) {
                $v = strtolower(trim($q[$key]));
                if (str_contains($v, 'senior') || str_contains($v, '高中')) return 'senior';
                if (str_contains($v, 'junior') || str_contains($v, '初中')) return 'junior';
            }
        }
    }
    // 2️⃣ 来源文本推断（例: "2025年安徽省初中学业水平考试 道德与法治"）
    $sourceBlob = '';
    $contentBlob = '';
    foreach ($data as $q) {
        if (!is_array($q)) continue;
        $sourceBlob .= ' ' . ($q['source'] ?? '') . ' ' . ($q['source_province'] ?? '') . ' ' . ($q['source_year'] ?? '');
        $contentBlob .= ' ' . ($q['question_content'] ?? '');
    }
    $allBlob = $sourceBlob . $contentBlob;
    if (preg_match('/(高[一二三]|高中|senior\s*(?:high|middle)?|高考|学业水平等级性考试)/iu', $allBlob)) return 'senior';
    if (preg_match('/(初[一二三]|初中|junior\s*(?:high|middle)?|学业水平考试)/iu', $allBlob)) return 'junior';
    // 3️⃣ 默认初中
    return 'junior';
}

/**
 * 判断是否为 PaperCutter-VL 输出格式
 * 特征：顶层数组每条均为对象，含 question_content / question_options / question_type 等字段
 */
function pcvl_isPaperCutterVL(array $data): bool {
    if (!is_array($data) || isset($data[0]) && !is_array($data[0])) return false;
    if (!isset($data[0]) || !is_array($data[0])) return false;
    $keys = array_keys($data[0]);
    $required = ['question_content', 'question_type'];
    $hit = 0;
    foreach ($required as $r) {
        if (in_array($r, $keys, true)) $hit++;
    }
    // 至少命中 2 个 PaperCutter 特有字段即判定为真
    if ($hit < 2) return false;
    // 且必须具备 PaperCutter 独特字段之一
    $paperCutterOnly = ['question_id', 'question_images', 'analysis_images', 'source_province', 'source_year'];
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
        if (!empty($q['subject']) && is_string($q['subject'])) {
            $s = trim($q['subject']);
            if ($s !== '') return $s;
        }
    }
    // 从 source 里猜（例如 "2025年安徽省初中学业水平考试 道德与法治（开卷）"）
    $subjectMap = [
        '道德与法治' => '道德与法治', '政治' => '政治',
        '历史' => '历史', '地理' => '地理',
        '语文' => '语文', '数学' => '数学', '英语' => '英语',
        '物理' => '物理', '化学' => '化学', '生物' => '生物',
        '信息技术' => '信息技术',
    ];
    foreach ($data as $q) {
        $src = $q['source'] ?? '';
        $cnt = $q['question_content'] ?? '';
        $blob = $src . $cnt;
        foreach ($subjectMap as $k => $v) {
            if ($k !== '政治' && mb_strpos($blob, $k) !== false) return $v;
        }
    }
    return '综合';
}

/**
 * PaperCutter-VL 格式 → 系统扁平数组
 * 主要处理：题型识别、选项（去除 A./B. 前缀）、题干 HTML 富化（base64 图片嵌入、表格内嵌）
 */
function pcvlConvertPaperCutterVL(array $data, string $subject, array &$questions, string $educationLevel = ''): void {
    $typeMap = [
        '单选题' => 'single', '单选题（四选一）' => 'single',
        '多选题' => 'multiple', '多选题（不定项）' => 'multiple',
        '判断题' => 'judge', '对错题' => 'judge',
        '填空题' => 'fill', '多空填空题' => 'multi_fill',
        '简答题' => 'short', '问答题' => 'short', '论述题' => 'short',
        '选择题' => 'single',
    ];

    foreach ($data as $q) {
        if (!is_array($q)) continue;
        $rawType = $q['question_type'] ?? '';
        $typeKey = $typeMap[$rawType] ?? 'single';

        // 选项处理
        $rawOpts = $q['question_options'] ?? [];
        $options = [];
        if (is_array($rawOpts) && !empty($rawOpts)) {
            foreach ($rawOpts as $opt) {
                if (!is_string($opt)) continue;
                // 去掉 "A. " / "A、" / "A)" / "A)" 这类前缀，保留内容
                $clean = preg_replace('/^\s*[A-Z][.、)）:：\s]+/', '', $opt);
                // 如果只剩了一个字母（比如 "A"），就把原串留下
                if ($clean === '' && preg_match('/^\s*([A-Z])\s*$', $opt, $m)) {
                    $options[] = $opt;
                } else {
                    $options[] = $clean !== '' ? $clean : $opt;
                }
            }
        }

        // 根据选项数推断类型（若只有 "选择题" 这种含糊标签）
        if ($rawType === '选择题') {
            $cnt = count($options);
            if ($cnt === 2) $typeKey = 'judge';
            elseif ($cnt >= 4) $typeKey = 'single';
        }

        // 答案处理（优先使用 answer 字段，兼容 correct_answer 字段）
        $answerText = $q['answer'] ?? ($q['correct_answer'] ?? '');
        $explanation = $q['resolve'] ?? ($q['explanation'] ?? '');

        // 题干富化：把 question_images、analysis_images 里的 base64 补到 HTML 中
        $content = $q['question_content'] ?? '';
        $tables = $q['question_tables'] ?? [];
        $qImages = $q['question_images'] ?? [];
        $aImages = $q['analysis_images'] ?? [];
        $isHtml = false;

        // 若 content 本身已含 HTML（PaperCutter-VL 常常把 base64 <img> 直接塞在 content 里），直接保留
        if (preg_match('/<[a-zA-Z][^>]*>/', $content)) {
            $isHtml = true;
        }
        // 若选项中含有 HTML 标签，也标记为富文本
        if (!empty($options)) {
            foreach ($options as $opt) {
                if (is_string($opt) && preg_match('/<[a-zA-Z][^>]*>/', $opt)) {
                    $isHtml = true;
                    break;
                }
            }
        }
        // 把附加表格追加到 content
        if (is_array($tables) && !empty($tables)) {
            foreach ($tables as $t) {
                if (is_string($t) && trim($t) !== '') {
                    $content .= "\n\n" . $t;
                    $isHtml = true;
                }
            }
        }
        // 图片去重：记录已出现过的 base64 摘要（前 64 字符），避免同一张图重复插入
        $seenImgHash = [];

        // 把题目图片追加到题干 content；解析图片追加到 explanation
        $appendImgs = function (array $imgs, string &$target, bool &$htmlFlag) use (&$seenImgHash) {
            foreach ($imgs as $b64) {
                if (!is_string($b64) || trim($b64) === '') continue;
                // 归一化：去掉 data: 前缀，只取 base64 部分做去重
                $pure = preg_replace('/^data:image\/[a-z]+;base64,/i', '', $b64);
                $pure = ltrim($pure);
                // 用前 80 字符做指纹去重（足够区分不同图，又不会太慢）
                $fingerprint = substr($pure, 0, 80);
                if ($fingerprint === '' || isset($seenImgHash[$fingerprint])) continue;
                $seenImgHash[$fingerprint] = true;

                // 检查 target 中是否已内嵌了这张图（PaperCutter-VL 有时 content 里已有 <img src="data:..."> 又在 images 数组里重复列）
                if (strpos($target, $fingerprint) !== false) continue;

                $src = 'data:image/jpeg;base64,' . $pure;
                $target .= "\n\n<div style=\"text-align:center\"><img src=\"" . htmlspecialchars($src, ENT_QUOTES) . "\" alt=\"图片\" style=\"max-width:100%;height:auto;\"></div>";
                $htmlFlag = true;
            }
        };
        // 题目图片 → 题干
        $appendImgs($qImages, $content, $isHtml);
        // 解析图片 → 解析（不是题干！）
        $appendImgs($aImages, $explanation, $isHtml);

        // 推断难度
        $diff = intval($q['difficulty'] ?? 0);
        if ($diff <= 0) $diff = importEstimateDifficulty($content);

        // 题干太长时用 content-box 展示全文（不再只截断 200 字）
        $points = 2.0; // 默认分
        $sourceLabel = $q['source'] ?? '';
        if ($sourceLabel !== '') {
            $explanation = trim($explanation . "\n（来源：" . $sourceLabel . "）");
        }

        $questions[] = [
            'subject'        => normalizeSubject($subject),
            'question_type'  => $typeKey,
            'category'       => $typeKey,
            'education_level'=> $educationLevel,   // ★ 用批次/外层传入的学段, 不硬编码空
            'content'        => $content,
            'options'        => !empty($options) ? $options : null,
            'correct_answer' => $answerText,
            'explanation'    => $explanation !== '' ? $explanation : null,
            'difficulty'     => $diff,
            'points'         => $points,
            'is_html'        => $isHtml ? 1 : 0,
            '_pcvl_qid'      => $q['question_id'] ?? 0,  // 保留原始题号用于答案匹配
        ];
    }
}
