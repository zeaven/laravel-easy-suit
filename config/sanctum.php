<?php

return [
    'expiration' => 20160,  // 两周过期时间
    'refresh_ttl' => 60,    // 一个小时刷新一次token
    'refresh_grace_ttl' => 5, // 刷新token的灰色时间，防止同一token并发多个请求刷新多次
    'remove_refresh_expire_token' => true,  // 是否移除刷新后的token
];
