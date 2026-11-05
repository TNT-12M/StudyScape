<?php
// 狼人杀联机版 - 全局配置
return [
    'db' => '/www/lrs-data/lrs.sqlite',
    'data_dir' => '/www/lrs-data',
    'ai' => [
        'enabled' => true,
        'endpoint' => 'http://127.0.0.1:8089/v1/chat/completions',
        'model' => 'qwen2.5-0.5b',
        'timeout' => 25,
        'max_tokens' => 110,
        'temperature' => 0.85,
        'lock' => '/www/lrs-data/ai.lock',
        'php' => '/www/server/php/82/bin/php',
    ],
    'timers' => [
        'night' => 35,
        'discuss' => 65,
        'discuss_bot' => 50,
        'vote' => 45,
        'lastword' => 35,
        'hunter' => 30,
    ],
    'max_rooms' => 60,
];
