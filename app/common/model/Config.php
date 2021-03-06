<?php

declare(strict_types=1);

namespace app\common\model;

/**
 * 系统配置模型
 */
class Config extends MicroEngine
{
    // 设置json类型字段
    protected $json = ['value'];
    // 设置JSON数据返回数组
    protected $jsonAssoc = true;

    // +------------------------------------------------
    // | 查询范围
    // +------------------------------------------------

    /**
     * 根据key字段查询数据
     */
    public function scopeKey($query, $value)
    {
        $query->where('key', $value);
    }

    // +------------------------------------------------
    // | 模型事件
    // +------------------------------------------------

    /**
     * 新增前
     */
    public static function onBeforeInsert($model)
    {
        // 调用父类方法 追加uniacid
        parent::onBeforeInsert($model);
        $model->value = encode($model->value);
    }

    /**
     * 更新前
     */
    public static function onBeforeUpdate($model)
    {
        $model->value = encode($model->value);
    }
}
