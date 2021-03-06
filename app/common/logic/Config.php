<?php

declare(strict_types=1);

namespace app\common\logic;

use app\common\model\Config as ConfigModel;

/**
 * 系统配置
 */
class Config
{
    // +------------------------------------------------
    // | 获取配置
    // +------------------------------------------------

    /**
     * 获取所有配置
     * 
     * 用于让前端开发者查看每个配置项的作用
     */
    public static function getConfigAll()
    {
        $data = ConfigModel::field('type,key,note')->select();
        return $data->toArray();
    }

    /**
     * 根据配置键key查询配置项
     *
     * @param string $key 键名
     */
    public static function getValueByKey(string $key)
    {
        $data = ConfigModel::key($key)->findOrEmpty();
        if ($data->isEmpty()) fault('系统配置丢失');
        return $data->value;
    }

    /**
     * 根据配置键key查询配置
     * 返回配置说明和配置键值
     *
     * @param string $key
     */
    public static function getConfigByKey(string $key)
    {
        $data = ConfigModel::key($key)->findOrEmpty();
        if ($data->isEmpty()) fault('系统配置丢失');
        return [$data->note, $data->value];
    }

    /**
     * 根据多个配置键key获取多个配置
     *
     * @param array $keys 配置键数组
     */
    public static function getBatchByKeys(array $keys): array
    {
        $data = ConfigModel::whereIn('key', $keys)->select();
        return $data->column('value', 'key');
    }

    /**
     * 根据配置组名称type查询配置项
     *
     * @param string $type 配置组名称
     */
    public static function getBatchByType(string $type): array
    {
        $data = ConfigModel::type($type)->select();
        if ($data->isEmpty()) fault('该配置组不存在');
        return $data->column('value', 'key');
    }
}
