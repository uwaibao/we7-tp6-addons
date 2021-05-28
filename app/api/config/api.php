<?php

return [
    'jwt' => [
        'iss'    => 'zhangsan',   // 签发者
        'aud'    => 'zhangsan',   // 接收者
        'exp'    => 864000,       // 过期时间,864000秒=10天
        'key'    => 'liang',      // 访问密钥
        'prefix' => 'jwt_',       // 缓存前缀
    ],
];
