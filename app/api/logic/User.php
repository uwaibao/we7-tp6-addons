<?php

declare(strict_types=1);

namespace app\api\logic;

// 模型
use app\common\model\User as UserModel;

// 基础类库
use app\api\lib\JwtAuth;
use app\api\lib\User as LibUser;
use app\common\lib\easywechat\MiniProgram;
use app\common\logic\User as LogicUser;

/**
 * 小程序接口 用户相关逻辑
 */
class User extends LogicUser
{
    /**
     * 个人中心
     *
     * @param integer $uid 用户id
     */
    public static function getMine(int $uid)
    {
        $user = UserModel::field('nickName,avatarUrl')->findOrEmpty($uid);
        $user->isEmpty() && fault('用户不存在');
        return $user->toArray();
    }

    // +----------------------------------------------------------------------
    // | wx.login 小程序登录
    // +----------------------------------------------------------------------

    /**
     * 小程序登录逻辑
     *
     * @param string $code
     */
    public static function login(string $code)
    {
        // 根据 jsCode 获取用户 session 信息
        $res = app(MiniProgram::class)->login($code);
        // 用户信息获取失败,则抛出错误
        isset($res['errcode']) && fault($res['errmsg'], $res['errcode']);
        // 根据openid查询用户信息
        $user = UserModel::where('openid', $res['openid'])->findOrEmpty();
        // 启动事务
        $user->startTrans();
        try {
            // 创建、更新用户
            $user->save($res);
            $user->commit();
        } catch (\Exception $e) {
            $user->rollback();
            fault('登录失败');
        }
        // 登录成功将用户信息和token返回给前端
        $data  = [
            'userinfo' => $user->toArray(),
            'token'    => self::getToken(intval($user->id)),
        ];
        return $data;
    }

    // +----------------------------------------------------------------------
    // | 开发环境 模拟登陆
    // +----------------------------------------------------------------------

    /**
     * 模拟登陆
     *
     * @param integer $uid 用户id
     */
    public static function simulate(int $uid)
    {
        $user = UserModel::findOrEmpty($uid);
        $user->isEmpty() && fault('用户不存在');
        return [
            'user'  => $user,
            'token' => self::getToken(intval($uid)),
        ];
    }

    /**
     * 根据用户id生成token
     *
     * @param  integer $uid   用户id
     * @return string  $token JWT加密字符串
     */
    private static function getToken(int $uid)
    {
        // 附加数据
        $build = ['uid' => $uid];
        // 生成token
        $token = app(JwtAuth::class)->encode($build);
        // 将token存入缓存
        app(JwtAuth::class)->cache($uid, $token);
        // 返回加密token
        return $token;
    }

    // +----------------------------------------------------------------------
    // | wx.getUserProfile 获取用户昵称、头像等信息
    // +----------------------------------------------------------------------

    /**
     * 更新用户信息逻辑
     *
     * @param int   $id   用户id
     * @param array $data 更新的数据
     */
    public static function update(int $id, array $data)
    {
        $user = UserModel::findOrEmpty($id);
        $user->isEmpty() && fault('用户不存在');
        $user->startTrans();
        try {
            if (isset($data['avatarUrl'])) {
                // 用户头像本地化处理
                LibUser::avatarLocal($user->openid, $data['avatarUrl']);
            }
            $user->save($data);
            $user->commit();
        } catch (\Exception $e) {
            $user->rollback();
            fault('更新失败');
        }
        return $user->toArray();
    }

    // +----------------------------------------------------------------------
    // | wx.getUserProfile 获取用户昵称、头像等信息
    // +----------------------------------------------------------------------

    /**
     * 获取手机号(消息解密)
     *
     * @param string $iv
     * @param string $encryptedData
     */
    public static function decryptPhoneNumber(int $id, string $iv, string $encryptedData)
    {
        // 获取用户会话密钥
        $session = self::getSessionKeyById($id);
        // 根据会话密钥和加密信息解密数据获取手机号
        $result = app(MiniProgram::class)->decryptData($session, $iv, $encryptedData);
        if (isset($result['phoneNumber'])) {
            return $result['phoneNumber'];
        } else {
            fault('解密失败');
        }
    }
}
