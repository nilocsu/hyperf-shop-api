<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * @author colin.
 * date 19-6-26 下午8:24
 */
class MessageService
{
    /**
     * @param $user_id
     * @param $title
     * @param $detail
     * @param int $business_type
     * @param int $business_id
     * @param int $type
     * @return bool
     */
    public function messageAdd($user_id, $title, $detail, $business_type = 0, $business_id = 0, $type = 0)
    {
        $data = [
            'title'         => $title,
            'detail'        => $detail,
            'user_id'       => intval($user_id),
            'business_type' => intval($business_type),
            'business_id'   => intval($business_id),
            'type'          => intval($type),
            'is_read'       => 0,
            'add_time'      => time(),
        ];
        return Db::table('message')->insertGetId($data) > 0;
    }

    /**
     * @param array $params
     * @return array
     */
    public function messageListWhere($params = [])
    {
        // 条件初始化
        $where = [
            ['is_delete_time', '=', 0],
        ];

        // id
        if (!empty($params['id'])) {
            $where[] = ['id', '=', $params['id']];
        }

        // 用户id
        if (!empty($params['user'])) {
            $where[] = ['user_id', '=', $params['user']['id']];
        }

        // 关键字
        if (!empty($params['keywords'])) {
            $where[] = ['title|detail', 'like', '%' . $params['keywords'] . '%'];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] == 1) {
            // 等值
            if (isset($params['business_type']) && $params['business_type'] > -1) {
                $where[] = ['business_type', '=', intval($params['business_type'])];
            }
            if (isset($params['type']) && $params['type'] > -1) {
                $where[] = ['type', '=', intval($params['type'])];
            }
            if (isset($params['is_read']) && $params['is_read'] > -1) {
                $where[] = ['is_read', '=', intval($params['is_read'])];
            }

            if (!empty($params['time_start'])) {
                $where[] = ['add_time', '>', strtotime($params['time_start'])];
            }
            if (!empty($params['time_end'])) {
                $where[] = ['add_time', '<', strtotime($params['time_end'])];
            }
        }

        return $where;
    }

    /**
     * 消息总数
     * @param array $where
     * @return int
     */
    public function messageTotal($where = [])
    {
        return (int)Db::table('message')->where($where)->count();
    }

    /**
     * 用户消息总数
     * @param array $params
     * @return int
     */
    public function userMessageTotal($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'user',
                'error_msg'    => '用户信息有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return 0;
        }
        return self::MessageTotal(self::MessageListWhere($params));
    }

    /**
     * 列表
     * @param array $params
     * @return array
     */
    public function messageList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $m        = isset($params['m']) ? intval($params['m']) : 0;
        $n        = isset($params['n']) ? intval($params['n']) : 10;
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::table('message')->where($where)->limit($n)->offset($n * $m)->orderByRaw($order_by)->get();
//        if(!empty($data))
//        {
//            $common_business_type_list = lang('common_business_type_list');
//            $common_is_read_list = lang('common_is_read_list');
//            $common_message_type_list = lang('common_message_type_list');
//            foreach($data as &$v)
//            {
//                // 消息类型
//                $v['type_name'] = $common_message_type_list[$v['type']]['name'];
//
//                // 是否已读
//                $v['is_read_name'] = $common_is_read_list[$v['is_read']]['name'];
//
//                // 业务类型
//                $v['business_type_name'] = $common_business_type_list[$v['business_type']]['name'];
//
//                // 时间
//                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
//                $v['add_time_date'] = date('Y-m-d', $v['add_time']);
//            }
//        }
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 消息更新未已读
     * @param array $params
     * @return array
     */
    public function messageRead($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'user',
                'error_msg'    => '用户信息有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 更新用户未读消息为已读
        $where = ['user_id' => $params['user']['id'], 'is_read' => 0];
        $ret   = Db::table('message')->where($where)->update(['is_read' => 1]);
        return dataReturn('处理成功', 0, $ret);
    }

    /**
     * 后台管理员列表
     * @param array $params
     * @return array
     */
    public function adminMessageList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $m        = isset($params['m']) ? intval($params['m']) : 0;
        $n        = isset($params['n']) ? intval($params['n']) : 10;
        $field    = 'message.*,user.username,user.nickname,user.mobile,user.gender';
        $order_by = empty($params['order_by']) ? 'message.id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::table('message')->join('user', 'user.id', '=',
            'message.user_id')->where($where)->selectRaw($field)->limit($n)->offset($m * $n)->orderBy($order_by)->get();
//        if(!empty($data))
//        {
//            $common_business_type_list = lang('common_business_type_list');
//            $common_is_read_list = lang('common_is_read_list');
//            $common_message_type_list = lang('common_message_type_list');
//            $common_gender_list = lang('common_gender_list');
//            foreach($data as &$v)
//            {
//                // 消息类型
//                $v['type_name'] = $common_message_type_list[$v['type']]['name'];
//
//                // 是否已读
//                $v['is_read_name'] = $common_is_read_list[$v['is_read']]['name'];
//
//                // 业务类型
//                $v['business_type_name'] = $common_business_type_list[$v['business_type']]['name'];
//
//                // 用户是否已删除
//                $v['user_is_delete_time_name'] = ($v['user_is_delete_time'] == 0) ? '否' : '是';
//
//                // 性别
//                $v['gender_text'] = $common_gender_list[$v['gender']]['name'];
//
//                // 时间
//                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
//                $v['add_time_date'] = date('Y-m-d', $v['add_time']);
//            }
//        }
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 后台消息总数
     * @param array $where
     * @return int
     */
    public function adminMessageTotal($where = [])
    {
        return Db::table('message')->join('user', 'user.id', '=',
            'message.user_id')->where($where)->count();
    }

    /**
     *      * 后台消息列表条件
     * @param array $params
     * @return array
     */
    public function adminMessageListWhere($params = [])
    {
        $where = [
            ['message.is_delete_time', '=', 0],
        ];

        // 关键字
        if (!empty($params['keywords'])) {
            $where[] = [
                'message.title|message.detail|user.username|user.nickname|user.mobile',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] == 1) {
            // 等值
            if (isset($params['business_type']) && $params['business_type'] > -1) {
                $where[] = ['message.business_type', '=', intval($params['business_type'])];
            }
            if (isset($params['type']) && $params['type'] > -1) {
                $where[] = ['message.type', '=', intval($params['type'])];
            }
            if (isset($params['is_read']) && $params['is_read'] > -1) {
                $where[] = ['message.is_read', '=', intval($params['is_read'])];
            }
            if (isset($params['gender']) && $params['gender'] > -1) {
                $where[] = ['user.gender', '=', intval($params['gender'])];
            }
            if (isset($params['user_is_delete_time']) && $params['user_is_delete_time'] > -1) {
                if (intval($params['user_is_delete_time']) == 0) {
                    $where[] = ['message.user_is_delete_time', '=', 0];
                } else {
                    $where[] = ['message.user_is_delete_time', '>', 0];
                }
            }

            if (!empty($params['time_start'])) {
                $where[] = ['message.add_time', '>', strtotime($params['time_start'])];
            }
            if (!empty($params['time_end'])) {
                $where[] = ['message.add_time', '<', strtotime($params['time_end'])];
            }
        }

        return $where;
    }

    /**
     * @param array $params
     * @return array
     */
    public function messageDelete($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '操作id有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 删除操作
        if (Db::table('message')->where(['id' => $params['id']])->delete()) {
            return dataReturn('删除成功');
        }

        return dataReturn('删除失败或资源不存在', -100);
    }
}