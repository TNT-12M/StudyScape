<?php
$db = new SQLite3(__DIR__ . '/exam.db');
$row = $db->querySingle("SELECT id, filename, education_level FROM materials WHERE id=5", true);
echo json_encode($row, JSON_UNESCAPED_UNICODE);
