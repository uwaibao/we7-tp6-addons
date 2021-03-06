<?php

// +---------------------------------------------------------
// | 文件存放在腾讯云 COS
// +---------------------------------------------------------

namespace app\common\lib\storage;

use Qcloud\Cos\Client;

/**
 * 腾讯云对象存储 COS
 * 
 * @author  liang 23426945@qq.com
 * @package composer require qcloud/cos-sdk-v5
 */
class Tencent extends Base
{
    /**
     * 构造方法
     *
     * @param array $config 配置信息
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * 将文件上传到腾讯云对象存储 COS
     */
    public function upload(string $name, string $scene)
    {
        // 配置参数
        // 存储桶名称 格式：BucketName-APPID
        $bucket    = $this->config['bucket'];
        // 自定义CDN加速域名
        $domain    = $this->config['domain'];
        // 存储桶地域 ap-beijing
        $region    = $this->config['region'];
        // 云 API 密钥 SecretId
        $secretId = $this->config['accessKey'];
        // 云 API 密钥 SecretKey
        $secretKey = $this->config['secretKey'];
        // 判断是否有文件上传
        $file = $this->checkUpload($name, $scene);
        if (!is_array($file)) {
            // 单文件上传
            if (!$file instanceof \think\file\UploadedFile) return $file;
        }
        $cosClient = new Client(
            [
                'region' => $region,
                'schema' => 'http', //协议头部，默认为http
                'credentials' => [
                    'secretId'  => $secretId,
                    'secretKey' => $secretKey
                ]
            ]
        );
        // 执行文件上传
        try {
            if (is_array($file)) {
                $uploads = [];
                foreach ($file['success'] as $value) {
                    // 文件在存储空间中的存放位置
                    // 此处的 key 为对象键，对象键是对象在存储桶中的唯一标识
                    $key = $this->buildSaveName($value);
                    // 本地文件绝对路径
                    $srcPath = $value->getRealPath();
                    $resource = fopen($srcPath, 'rb');
                    if ($resource) {
                        $result = $cosClient->Upload($bucket, $key, $resource);
                    }
                    if (empty($domain)) {
                        $url = 'https://' . $result['Location'];
                    } else {
                        $url = $domain . '/' . $result['Key'];
                    }
                    $uploads[] = $url;
                }
                return $this->data($uploads, $file['error']);
            } else {
                // 文件在存储空间中的存放位置
                // 此处的 key 为对象键，对象键是对象在存储桶中的唯一标识
                $key = $this->buildSaveName($file);
                // 本地文件绝对路径
                $srcPath = $file->getRealPath();
                $resource = fopen($srcPath, 'rb');
                if ($resource) {
                    $result = $cosClient->Upload($bucket, $key, $resource);
                }
                if (empty($domain)) {
                    $url = 'https://' . $result['Location'];
                } else {
                    $url = $domain . '/' . $result['Key'];
                }
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
        return $this->msg($url);
    }
}
