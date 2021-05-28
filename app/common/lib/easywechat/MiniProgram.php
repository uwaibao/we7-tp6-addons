<?php

// +---------------------------------------------------------------
// | EasyWeChat 小程序相关功能封装
// +---------------------------------------------------------------
// | Author: liang <23426945@qq.com>
// +---------------------------------------------------------------

declare(strict_types=1);

namespace app\common\lib\easywechat;

// EasyWeChat
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Image;
// 逻辑层
use app\common\logic\Alone;
// 其他
use think\facade\Filesystem;
use liang\helper\MicroEngine;

class MiniProgram
{
    public $app;

    /**
     * 构造方法生成相关操作实例
     */
    public function __construct()
    {
        // 获取小程序APPID和开发者密钥
        $miniProgram = Alone::getMiniProgramConfig();
        $config = [
            'app_id' => $miniProgram['appid'],
            'secret' => $miniProgram['secret'],
            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                // 'file' => __DIR__ . '/wechat.log',
            ],
        ];
        $this->app = Factory::miniProgram($config);
    }

    // +-------------------------------------------------------------------------------------
    // | 小程序登录
    // +-------------------------------------------------------------------------------------
    // | https://www.easywechat.com/docs/5.x/mini-program/auth
    // +-------------------------------------------------------------------------------------

    /**
     * 根据 jsCode 获取用户 session 信息
     *
     * @param string $code
     */
    public function login(string $code)
    {
        // 返回示例
        // $res = [
        //     'session_key' => '5qpyH6pz1Xjuyg3rZ0KD8A==',
        //     'openid'      => 'oNiWa5CB9VFKgr-TqssteGoieibY',
        // ];
        // $res = [
        //     'errcode' => '40029',
        //     'errmsg'  => 'invalid code, hints: [ req_id: 3Ibcf2LnRa-lgVExa ]',
        // ];
        return $this->app->auth->session($code);
    }

    // +-------------------------------------------------------------------------------------
    // | 消息解密
    // +-------------------------------------------------------------------------------------
    // | https://easywechat.com/docs/4.x/mini-program/decrypt
    // +-------------------------------------------------------------------------------------

    /**
     * 获取电话等功能, 信息是加密的, 需要解密
     *
     * @param string $session
     * @param string $iv
     * @param string $encryptedData
     */
    public function decryptData(string $session, string $iv, string $encryptedData)
    {
        return $this->app->encryptor->decryptData($session, $iv, $encryptedData);
    }

    // +-----------------------------------------------------------------------------
    // | 小程序码
    // +-----------------------------------------------------------------------------
    // | https://www.easywechat.com/docs/4.x/mini-program/app_code
    // +-----------------------------------------------------------------------------

    /**
     * 生成小程序码
     *
     * @param string $page         页面路径
     * @param string|array $scene  场景(额外参数)
     * @param string $type         小程序码类型(目录名)
     */
    public function miniCode(string $page, $scene, string $type = '')
    {
        // 小程序码存放目录
        $path = $this->getStoragePath($type);
        // 数组数据转为查询字符串
        if (is_array($scene)) $scene = $this->queryString($scene);
        // 小程序码已存在就不再重复生成
        if (file_exists($path . '/' . $scene . '.jpg')) {
            $filename = $scene . '.jpg';
            return $this->getCodeUrl($filename, $type);
        }
        // 小程序码不存在时就生成
        try {
            $response = $this->app->app_code->getUnlimit($scene, [
                'page'  => $page,
            ]);
            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                // 保存小程序码到本地服务器
                $filename = $response->save($path, $scene);
                // 返回可访问的小程序码URL地址
                return $this->getCodeUrl(strval($filename), $type);
            } else {
                fault($response['errmsg'], $response['errcode']);
            }
        } catch (\Exception $e) {
            fault($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 获取小程序码存放目录
     *
     * @param string $type
     */
    private function getStoragePath(string $type)
    {
        return Filesystem::getDiskConfig('w7', 'root') . '/miniCode' . ($type ? '/' . $type : '');
    }

    /**
     * 返回小程序码URL地址
     *
     * @param string $filename
     * @param string $type
     */
    private function getCodeUrl(string $filename, string $type)
    {
        $type =  $type ? ltrim($type, '/') . '/' : '';
        return Filesystem::getDiskConfig('w7', 'url') . 'miniCode/' . $type . $filename;
    }

    /**
     * 数组数据转为查询字符串
     *
     * @param array $data
     */
    private function queryString(array $data)
    {
        $link = '';
        foreach ($data as $key => $value) {
            $link .= $key . '-' . $value . '!';
        }
        return rtrim($link, '!');
    }

    // +-----------------------------------------------------------------------------
    // | 客服消息
    // +-----------------------------------------------------------------------------
    // | https://www.easywechat.com/docs/4.x/official-account/messages
    // +-----------------------------------------------------------------------------

    /**
     * 消息推送接入验证
     * 开发者服务器接收消息推送
     */
    public function checkSignature(string $token)
    {
        $nonce     = $_GET["nonce"] ?? '';
        $signature = $_GET["signature"] ?? '';
        $timestamp = $_GET["timestamp"] ?? '';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode('', $tmpArr);
        $tmpStr = trim(sha1($tmpStr));
        if (empty($token)) die('未设置消息推送token令牌');
        if (empty($signature) || empty($tmpStr) || empty($nonce)) die('非法请求！！！');
        if ($tmpStr != $signature) die('签名验证错误');
        isset($_GET['echostr']) ? die($_GET['echostr']) : '';
    }

    /**
     * 获取客服消息会话接口地址
     */
    public static function replyApi()
    {
        $route = '/message/reply';
        $domain  = request()->domain();
        // 判断当前环境
        if (MicroEngine::isMicroEngine()) {
            // 微信模块消息推送地址
            $uniacid = MicroEngine::getUniacid();
            $module  = MicroEngine::getModuleName();
            return "{$domain}/app/index.php?i={$uniacid}&c=entry&m={$module}&a=wxapp&do=api&s={$route}";
        } else {
            // 独立版消息推送地址
            return "{$domain}/api.php{$route}";
        }
    }

    /**
     * 回复文本消息
     *
     * @param string $openid 接收者openid
     * @param string $text   回复的文本内容
     */
    public function replyText(string $openid, string $text)
    {
        $content = new Text($text);
        $this->app->customer_service->message($content)->to($openid)->send();
    }

    /**
     * 回复图片消息
     *
     * @param string $openid 接收者openid
     * @param string $image  图片绝对路径
     */
    public function replyImg(string $openid, string $image)
    {
        // 上传临时素材
        $result = $this->miniProgram->media->uploadImage($image);
        $content = new Image($result['media_id']);
        $this->app->customer_service->message($content)->to($openid)->send();
    }

    // +-----------------------------------------------------------------------------
    // | 订阅消息
    // +-----------------------------------------------------------------------------
    // | https://www.easywechat.com/docs/4.x/mini-program/subscribe_message
    // +-----------------------------------------------------------------------------

    /**
     * 发送订阅消息
     *
     * @param string $tplId     订阅消息模板 ID
     * @param string $openid    接收者 openid
     * @param string $page      跳转路径，仅限本小程序内的页面
     * @param array  $data      模板内容
     * @param string $state     跳转类型 formal 正式版 trial 体验版 developer 开发版
     */
    public function subscribeMessage(string $tplId, string $openid, string $page, array $data, string $state = 'developer')
    {
        $data = [
            'template_id'       => $tplId,    // 订阅消息模板id
            'touser'            => $openid,   // 接收者（用户）的 openid
            'page'              => $page,     // 模板卡片跳转路径，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
            'data'              => $data,     // 模板内容
            'miniprogram_state' => $state,    // formal 正式版 trial 体验版 developer 开发版
        ];
        // 返回数组
        return $this->app->subscribe_message->send($data);
    }
}
