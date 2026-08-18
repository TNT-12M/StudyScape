<?php
/**
 * 临时脚本：将被哈希化的用户密码还原为明文
 * 运行后请立即删除此文件
 */
$dbFile = __DIR__ . '/exam.db';
$db = new SQLite3($dbFile);
$db->enableExceptions(true);

// 密码映射表（从原始数据中获取）
$passwords = [
    1 => 'joey862',
    2 => '1',
    3 => 'lian120208',
    4 => '17',      // 创始人
    8 => 'miea111202',
    9 => '17',      // 用户名17
];

echo "当前用户列表：\n";
$users = $db->query("SELECT id, username, password FROM users");
while ($u = $users->fetchArray(SQLITE3_ASSOC)) {
    $isHashed = strpos($u['password'], '$2y$') === 0;
    echo sprintf("ID=%d, username=%s, password=%s %s\n",
        $u['id'], $u['username'],
        $isHashed ? '[已哈希]' : $u['password'],
        $isHashed ? '(需要还原)' : '(明文正常)'
    );
}

echo "\n开始还原密码...\n";
$restored = 0;
foreach ($passwords as $id => $plain) {
    $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bindValue(1, $plain);
    $stmt->bindValue(2, $id);
    $stmt->execute();
    echo "ID=$id 密码已还原为: $plain\n";
    $restored++;
}

echo "\n还原完成！共还原 $restored 个用户。\n";

// 验证
echo "\n验证还原结果：\n";
$users = $db->query("SELECT id, username, password FROM users");
while ($u = $users->fetchArray(SQLITE3_ASSOC)) {
    $isHashed = strpos($u['password'], '$2y$') === 0;
    echo sprintf("ID=%d, username=%s, password=%s %s\n",
        $u['id'], $u['username'],
        $isHashed ? '[仍为哈希！]' : $u['password'],
        $isHashed ? '***警告***' : ''
    );
}
