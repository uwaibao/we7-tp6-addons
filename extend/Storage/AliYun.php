<?php

namespace Storage;

use OSS\OssClient;
use OSS\Core\OssException;

/**
 * 阿里云对象存储 OSS
 * 
 * composer require aliyuncs/oss-sdk-php
 */
class AliYun extends Base
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
     * 文件上传到阿里云对象存储 OSS
     */
    public function upload($name, string $type = 'image')
    {
        // 配置参数
        $bucket    = $this->config['bucket'];
        $domain    = $this->config['domain'];
        $endpoint  = $this->config['endpoint'];
        $accessKey = $this->config['accessKey'];
        $secretKey = $this->config['secretKey'];
        // 判断是否有文件上传
        $file = $this->isUpload($name);
        if (!$file instanceof \think\file\UploadedFile) return $file;
        // 上传验证
        if ($type == 'image') {
            try {
                validate(\app\validate\Upload::class)->check(['image' => $file]);
            } catch (\think\exception\ValidateException $e) {
                return $this->fail($e->getMessage());
            }
        }
        // 将文件上传到阿里云
        try {
            // 实例化对象
            $ossClient = new OssClient($accessKey, $secretKey, $endpoint);
            // 文件在存储空间中的存放位置
            $path = $this->buildSaveName($file);
            //执行上传: (bucket名称, 上传的目录, 临时文件路径)
            $result = $ossClient->uploadFile($bucket, $path, $file->getRealPath());
            // 配置了自有域名使用则自有域名
            // 否则使用阿里云OSS提供的默认域名
            if (empty($domain)) {
                $url = $result['info']['url'];
            } else {
                $url = $domain . '/' . $path;
            }
        } catch (OssException $e) {
            return $this->fail($e->getMessage());
        }
        return $this->msg($url);
    }
}