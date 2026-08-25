<?php
$f = __DIR__ . '/public/api.php';
$c = file_get_contents($f);
$old = "    if (!tableHasColumn(\$db, 'questions', 'category')) {\n        \$db->exec(\"ALTER TABLE questions ADD COLUMN category TEXT NOT NULL DEFAULT 'single'\");\n    }\n    if (!tableHasColumn(\$db, 'materials', 'education_level')) {";
$new = "    if (!tableHasColumn(\$db, 'questions', 'category')) {\n        \$db->exec(\"ALTER TABLE questions ADD COLUMN category TEXT NOT NULL DEFAULT 'single'\");\n    }\n    if (!tableHasColumn(\$db, 'questions', 'is_html')) {\n        // 富 HTML 题干（含 base64 图片/表格等 PaperCutter-VL 输出）：1=是，0=否\n        \$db->exec(\"ALTER TABLE questions ADD COLUMN is_html INTEGER NOT NULL DEFAULT 0\");\n    }\n    if (!tableHasColumn(\$db, 'materials', 'education_level')) {";
if (strpos($c, $old) === false) { echo "OLD NOT FOUND\n"; exit(1); }
$c = str_replace($old, $new, $c);
file_put_contents($f, $c);
echo "DB migration added\n";

// ---- 导入时写入 is_html ----
$old2 = '$optionsJson = !empty($q[\'options\']) ? json_encode($q[\'options\'], JSON_UNESCAPED_UNICODE) : null;
                        dbQuery($db, "INSERT INTO questions (subject, question_type, category, education_level, content, options, correct_answer, explanation, difficulty, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$q[\'subject\'], $q[\'question_type\'], $q[\'category\'], $q[\'education_level\'], $q[\'content\'], $optionsJson, $q[\'correct_answer\'], $q[\'explanation\'] ?? null, $q[\'difficulty\'], $q[\'points\']]);';
$new2 = '$optionsJson = !empty($q[\'options\']) ? json_encode($q[\'options\'], JSON_UNESCAPED_UNICODE) : null;
                        $isHtml = !empty($q[\'is_html\']) ? 1 : 0;
                        dbQuery($db, "INSERT INTO questions (subject, question_type, category, education_level, content, options, correct_answer, explanation, difficulty, points, is_html) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [$q[\'subject\'], $q[\'question_type\'], $q[\'category\'], $q[\'education_level\'], $q[\'content\'], $optionsJson, $q[\'correct_answer\'], $q[\'explanation\'] ?? null, $q[\'difficulty\'], $q[\'points\'], $isHtml]);';
if (strpos($c, $old2) === false) { echo "OLD2 NOT FOUND\n"; exit(1); }
$c = str_replace($old2, $new2, $c);
file_put_contents($f, $c);
echo "INSERT updated\n";
