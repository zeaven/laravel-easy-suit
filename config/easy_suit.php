<?php

return [
    'postman' => [
        'token' => env('POSTMAN_API_TOKEN'),
    ],
    'auth' => [
        'sanctum' => [
            'enable' => true,
            'token_model' => \Zeaven\EasySuit\SanctumExtension\CachePersonalAccessToken::class,
        ],
        'jwt' => [
            'enable' => true,
            'guard' => 'jwt'
        ],
    ],
    'anno_log' => [
        'enable' => env('EASY_SUIT_ANNO_LOG', true),
        'handler' => null
    ],
    // 全局返回格式
    'global_response' => [
        'fields' => [
            'code' => 'code',
            'data' => 'data',
            'message' => 'msg',
            'error' => 'error'
        ],
        'include' => ['api/*'],
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
    'model' => [
        'simple_pagination' => true,
        'extension' => true
    ]
];
