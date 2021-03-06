<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'make:init'  => command\Init::class,  // 创建初始化类文件
        'make:lib'   => command\Lib::class,   // 创建基础库类文件
        'make:logic' => command\Logic::class, // 创建逻辑层类文件
    ],
];
