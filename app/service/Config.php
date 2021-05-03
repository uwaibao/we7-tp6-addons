<?php

declare(strict_types=1);

namespace app\service;

class Config extends \think\Service
{
    public $bind = [
        // EasyWeChat 功能封装
        'EasyWeChat' => \app\api\lib\EasyWeChat::class,
    ];

    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
        // 加载扩展配置
        \think\facade\Config::load('extra/pages', 'pages');
    }

    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        //
    }
}