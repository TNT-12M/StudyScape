<?php
/**
 * StudyScape 题库质量质检
 * 纯本地规则，零成本，不调任何 API
 * quality 三级: good / poor / trash
 *   good  — 干净，无 flag
 *   poor  — 有瑕疵但可接受（括号不配对、题干稍短）
 *   trash — 致命缺陷，应隔离（选项数≠4、答案空、公式丢）
 *
 * 注意: 这个文件用 SQLite3 API (不是 PDO)
 *   - SQLite3Result: fetchArray(SQLITE3_ASSOC), 没有 fetchAll()
 *   - SQLite3:       exec('BEGIN TRANSACTION') / exec('COMMIT') / exec('ROLLBACK'), 没有 beginTransaction()/commit()/rollBack()
 *   - SQLite3->prepare() 有, 但返回 SQLite3Stmt (->bindValue() + ->execute(), 不是 execute([...]))
 */

// ===== 单道题质检 =====
function qualityScanQuestion(array $q): array {
    $flags = [];
    $details = [];
    $ctype = $q['question_type'] ?? '';
    $content = $q['content'] ?? '';
    $options = json_decode($q['options'] ?? '[]', true);
    $answer = trim($q['correct_answer'] ?? '');
    $hasImage = preg_match('/<img[^>]+src=["\x27](?!data:image\/)/i', $content) === 1;

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
            if (preg_match('/\[SOI\]|\[EOI\]|\[IMAGE_/', $s)) {
                $flags[] = 'ocr_residual_option_' . ($i+1);
                $details[] = "选项" . ($i+1) . "含 OCR 残留标记";
            }
            $clean = trim(preg_replace('/^[A-Da-d][.．、\s]*/u', '', $s));
            if ($clean !== '' && mb_strlen($clean) < 2) {
                $flags[] = 'option_' . ($i+1) . '_too_short';
                $details[] = "选项" . ($i+1) . "过短 (" . mb_strlen($clean) . "字)";
            }
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

    // === 规则 5: 题干过短 ===
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

// ===== 批量扫描 DB 所有题目并更新 (SQLite3 写法) =====
function qualityBulkScan($db): array {
    $total = 0; $good = 0; $poor = 0; $trash = 0; $errors = 0;

    // SQLite3 没有 fetchAll, 用 while + fetchArray
    $res = $db->query("SELECT id, content, options, question_type, correct_answer FROM qdb.questions");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $total++;
        try {
            $result = qualityScanQuestion($row);
            $flagsJson = SQLite3::escapeString(json_encode($result['flags'], JSON_UNESCAPED_UNICODE));
            $now = SQLite3::escapeString(date('Y-m-d H:i:s'));
            $qid = (int)$row['id'];
            $quality = SQLite3::escapeString($result['quality']);
            // 直接拼 SQL, SQLite3->prepare()->bindValue 太繁琐
            $db->exec("UPDATE qdb.questions SET quality='$quality', quality_flags='$flagsJson', quality_scanned_at='$now' WHERE id=$qid");
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

// ===== 把坏题移到 trash_questions (SQLite3 事务写法) =====
function qualityTrashQuestion($db, int $qid, int $adminId = 0, string $reason = ''): bool {
    $res = $db->query("SELECT * FROM qdb.questions WHERE id = $qid");
    $q = $res ? $res->fetchArray(SQLITE3_ASSOC) : false;
    if (!$q) return false;

    if (empty($q['quality_flags'])) {
        $scan = qualityScanQuestion($q);
        $q['quality_flags'] = json_encode($scan['flags'] ?? [], JSON_UNESCAPED_UNICODE);
    }

    // SQLite3 事务: exec('BEGIN/COMMIT/ROLLBACK')
    $db->exec('BEGIN TRANSACTION');
    try {
        $qid_s = (int)$qid;
        $reason_s = SQLite3::escapeString($reason ?: 'manual');
        $qf = SQLite3::escapeString($q['quality_flags'] ?? '');
        $sub = SQLite3::escapeString($q['subject'] ?? '');
        $qt  = SQLite3::escapeString($q['question_type'] ?? '');
        $ct  = SQLite3::escapeString($q['content'] ?? '');
        $op  = SQLite3::escapeString($q['options'] ?? '');
        $ca  = SQLite3::escapeString($q['correct_answer'] ?? '');
        $ex  = SQLite3::escapeString($q['explanation'] ?? '');
        $diff = (int)($q['difficulty'] ?? 2);
        $pts  = (float)($q['points'] ?? 1.0);
        $el   = SQLite3::escapeString($q['education_level'] ?? 'junior');
        $ca2  = $q['created_at'] ? "'" . SQLite3::escapeString($q['created_at']) . "'" : 'NULL';
        $aid  = (int)$adminId;

        $db->exec("INSERT INTO trash_questions
            (original_id, reason, quality_flags, subject, question_type, content, options, correct_answer, explanation, difficulty, points, education_level, original_created_at, trashed_by)
            VALUES ($qid_s, '$reason_s', '$qf', '$sub', '$qt', '$ct', '$op', '$ca', '$ex', $diff, $pts, '$el', $ca2, $aid)");
        $db->exec("DELETE FROM qdb.questions WHERE id = $qid_s");
        $db->exec('COMMIT');
        return true;
    } catch (\Throwable $e) {
        $db->exec('ROLLBACK');
        return false;
    }
}

// ===== 从 trash_questions 恢复回 questions (SQLite3 写法) =====
function qualityRestoreQuestion($db, int $trashId, int $adminId = 0): bool {
    $res = $db->query("SELECT * FROM trash_questions WHERE id = $trashId");
    $t = $res ? $res->fetchArray(SQLITE3_ASSOC) : false;
    if (!$t) return false;

    $db->exec('BEGIN TRANSACTION');
    try {
        $sub = SQLite3::escapeString($t['subject'] ?? '');
        $qt  = SQLite3::escapeString($t['question_type'] ?? '');
        $ct  = SQLite3::escapeString($t['content'] ?? '');
        $op  = SQLite3::escapeString($t['options'] ?? '');
        $ca  = SQLite3::escapeString($t['correct_answer'] ?? '');
        $ex  = SQLite3::escapeString($t['explanation'] ?? '');
        $diff = (int)($t['difficulty'] ?? 2);
        $pts  = (float)($t['points'] ?? 1.0);
        $el   = SQLite3::escapeString($t['education_level'] ?? 'junior');
        $qf   = SQLite3::escapeString($t['quality_flags'] ?? '');
        $trid = (int)$trashId;

        $db->exec("INSERT INTO qdb.questions
            (subject, question_type, content, options, correct_answer, explanation, difficulty, points, education_level, quality, quality_flags)
            VALUES ('$sub', '$qt', '$ct', '$op', '$ca', '$ex', $diff, $pts, '$el', 'poor', '$qf')");
        $db->exec("DELETE FROM trash_questions WHERE id = $trid");
        $db->exec('COMMIT');
        return true;
    } catch (\Throwable $e) {
        $db->exec('ROLLBACK');
        return false;
    }
}
