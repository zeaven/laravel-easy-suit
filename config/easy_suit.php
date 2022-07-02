<?php

return [
    'postman' => [
        'token' => env('POSTMAN_API_TOKEN'),
    ],
    'anno_log' => [
        'enable' => env('EASY_SUIT_ANNO_LOG', true),
    ],
    // 全局返回格式
    'global_response' => [
        'fields' => [
            'code' => 'code',
            'data' => 'data',
            'message' => 'msg',
            'error' => 'error'
        ],
        'exclude' => [
            'horizon/*',
            'laravel-websockets/*',
            'broadcasting/*',
            '*/export/*',
            '*/pusher/auth',
            '*/pusher/auth',
            'web/*',
        ]
    ],
];
