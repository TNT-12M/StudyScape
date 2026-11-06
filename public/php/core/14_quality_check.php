<?php
/**
 * StudyScape 题库质量质检
 * 纯本地规则，零成本，不调任何 API
 * quality 三级: good / poor / trash
 *   good  — 干净，无 flag
 *   poor  — 有瑕疵但可接受（括号不配对、题干稍短）
 *   trash — 致命缺陷，应隔离（选项数≠4、答案空、公式丢）
 */

// ===== 单道题质检 =====
// 输入: $q = ['content'=>..., 'options'=>json_string, 'question_type'=>..., 'correct_answer'=>..., ...]
// 输出: ['quality'=>'good|poor|trash', 'flags'=>['options_not_4', 'formula_missing', ...], 'details'=>[...]]
function qualityScanQuestion(array $q): array {
    $flags = [];
    $details = [];
    $ctype = $q['question_type'] ?? '';
    $content = $q['content'] ?? '';
    $options = json_decode($q['options'] ?? '[]', true);
    $answer = trim($q['correct_answer'] ?? '');
    $hasImage = preg_match('/<img[^>]+src=[\"\x27](?!data:image\/)/i', $content) === 1;

    // === 规则 1: 选项数 ===
    if (in_array($ctype, ['single','multiple','judge','单选题','多选题','判断题'], true)) {
        if (!is_array($options)) {
            $flags[] = 'options_json_fail';
            $details[] = 'options JSON 解析失败';
        } else {
            $n = count($options);
            $expected = ($ctype === 'judge' || $ctype === '判断题') ? 2 : 4;
            if ($n !== $expected) {
                $flags[] = 'options_not_' . $expected . '_actual_' . $n;
                $details[] = "选项数≠$expected (实际 $n)";
            }
        }
    }

    // === 规则 2: 选项内容残缺 ===
    if (is_array($options) && !empty($options)) {
        foreach (array_values($options) as $i => $opt) {
            $s = (string)$opt;
            // OCR 残留标记
            if (preg_match('/\[SOI\]|\[EOI\]|\[IMAGE_/', $s)) {
                $flags[] = 'ocr_residual_option_' . ($i+1);
                $details[] = "选项" . ($i+1) . "含 OCR 残留标记";
            }
            // 极短选项 (去掉 A. B. 等前缀后 < 2 字)
            $clean = trim(preg_replace('/^[A-Da-d][.．、\s]*/u', '', $s));
            if ($clean !== '' && mb_strlen($clean) < 2) {
                $flags[] = 'option_' . ($i+1) . '_too_short';
                $details[] = "选项" . ($i+1) . "过短 (" . mb_strlen($clean) . "字)";
            }
            // 只剩闭合符
            if (preg_match('/^[\)）\],，。、;；]+$/u', $clean)) {
                $flags[] = 'option_' . ($i+1) . '_broken';
                $details[] = "选项" . ($i+1) . "残缺闭合符";
            }
        }
    }

    // === 规则 3: 括号不配对 ===
    foreach ([['(',')'],['（','）'],['[',']'],['《','》']] as $p) {
        $op=$p[0]; $cl=$p[1];
        if (substr_count($content,$op) !== substr_count($content,$cl)) {
            $flags[] = 'bracket_mismatch_' . $op . $cl;
            $details[] = "括号不配对: $op×" . substr_count($content,$op) . " vs $cl×" . substr_count($content,$cl);
        }
    }

    // === 规则 4: 疑似公式丢失 ===
    if (preg_match('/[a-zA-Z]\+[\x{4e00}-\x{9fff}]/u', $content)) {
        $flags[] = 'formula_missing';
        $details[] = '疑似公式丢失 (字母+中文 紧挨着)';
    }

    // === 规则 5: 题干过短 (纯文字题 < 15 字算问题; 有图片的话容忍度高一点) ===
    $cleanContent = trim(preg_replace('/\s+/u', ' ', strip_tags($content)));
    $len = mb_strlen($cleanContent);
    if ($len === 0 && !$hasImage) {
        $flags[] = 'content_empty';
        $details[] = '题干为空';
    } elseif ($len < 15 && !$hasImage) {
        $flags[] = 'content_too_short';
        $details[] = "题干过短 ($len 字, 无配图)";
    }

    // === 规则 6: 提到"如图"但无配图 ===
    if (preg_match('/如图|见图|如下图|右图|右图所示/u', $content) && !$hasImage) {
        $flags[] = 'image_missing';
        $details[] = '提到"如图"但无配图';
    }

    // === 规则 7: 答案为空 ===
    if (in_array($ctype, ['single','multiple','judge','单选题','多选题','判断题'], true) && $answer === '') {
        $flags[] = 'answer_empty';
        $details[] = "答案为空 ($ctype)";
    }

    // === 判定等级 ===
    $trashFlags = ['options_json_fail', 'formula_missing', 'answer_empty', 'image_missing', 'content_empty'];
    $isTrash = false;
    foreach ($flags as $f) {
        // options_not_X_actual_Y 也是致命 (选项数不对就没法做)
        if (str_starts_with($f, 'options_not_')) { $isTrash = true; break; }
        if (in_array($f, $trashFlags)) { $isTrash = true; break; }
    }

    $quality = 'good';
    if (!empty($flags)) {
        $quality = $isTrash ? 'trash' : 'poor';
    }

    return [
        'quality' => $quality,
        'flags'   => $flags,
        'details' => $details,
    ];
}

// ===== 批量扫描 DB 所有题目并更新 =====
function qualityBulkScan($db): array {
    $total = 0; $good = 0; $poor = 0; $trash = 0; $errors = 0;

    $rows = $db->query("SELECT id, content, options, question_type, correct_answer FROM questions")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $total++;
        try {
            $result = qualityScanQuestion($row);
            $flagsJson = json_encode($result['flags'], JSON_UNESCAPED_UNICODE);
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("UPDATE questions SET quality = ?, quality_flags = ?, quality_scanned_at = ? WHERE id = ?");
            $stmt->execute([$result['quality'], $flagsJson, $now, $row['id']]);
            switch ($result['quality']) {
                case 'good':  $good++;  break;
                case 'poor':  $poor++;  break;
                case 'trash': $trash++; break;
            }
        } catch (\Throwable $e) {
            $errors++;
        }
    }

    return compact('total','good','poor','trash','errors');
}

// ===== 把坏题移到 trash_questions =====
function qualityTrashQuestion($db, int $qid, int $adminId = 0, string $reason = ''): bool {
    $q = $db->query("SELECT * FROM questions WHERE id = $qid")->fetch(PDO::FETCH_ASSOC);
    if (!$q) return false;

    // 先算一下 quality_flags
    if (empty($q['quality_flags'])) {
        $scan = qualityScanQuestion($q);
        $q['quality_flags'] = json_encode($scan['flags'] ?? [], JSON_UNESCAPED_UNICODE);
    }

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO trash_questions
            (original_id, reason, quality_flags, subject, question_type, content, options, correct_answer, explanation, difficulty, points, education_level, original_created_at, trashed_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $qid, $reason ?: 'manual', $q['quality_flags'] ?? '',
            $q['subject'] ?? '', $q['question_type'] ?? '',
            $q['content'] ?? '', $q['options'] ?? '', $q['correct_answer'] ?? '', $q['explanation'] ?? '',
            $q['difficulty'] ?? 2, $q['points'] ?? 1.0, $q['education_level'] ?? 'junior',
            $q['created_at'] ?? null, $adminId
        ]);
        $db->exec("DELETE FROM questions WHERE id = $qid");
        $db->commit();
        return true;
    } catch (\Throwable $e) {
        $db->rollBack();
        return false;
    }
}

// ===== 从 trash_questions 恢复回 questions =====
function qualityRestoreQuestion($db, int $trashId, int $adminId = 0): bool {
    $t = $db->query("SELECT * FROM trash_questions WHERE id = $trashId")->fetch(PDO::FETCH_ASSOC);
    if (!$t) return false;

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("INSERT INTO questions
            (subject, question_type, content, options, correct_answer, explanation, difficulty, points, education_level, quality, quality_flags)
            VALUES (?,?,?,?,?,?,?,?,?, 'poor', ?)");
        $stmt->execute([
            $t['subject'] ?? '', $t['question_type'] ?? '',
            $t['content'] ?? '', $t['options'] ?? '', $t['correct_answer'] ?? '', $t['explanation'] ?? '',
            $t['difficulty'] ?? 2, $t['points'] ?? 1.0, $t['education_level'] ?? 'junior',
            $t['quality_flags'] ?? ''
        ]);
        $db->exec("DELETE FROM trash_questions WHERE id = $trashId");
        $db->commit();
        return true;
    } catch (\Throwable $e) {
        $db->rollBack();
        return false;
    }
}
