<?php

declare(strict_types=1);

namespace app\admin\logic;

use app\common\model\User as ModelUser;

/**
 * 用户相关
 */
class User
{
    /**
     * 获取用户列表
     */
    public static function getLists(array $where, int $page, int $limit)
    {
        $where = where_filter($keys, $where);
        $model = ModelUser::withSearch($keys, $where);
        $count = $model->count();
        $list  = $model->page($page, $limit)->select()->hidden([
            'session_key', 'update_time', 'delete_time'
        ]);
        return compact('count', 'list');
    }
}
