<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 积分服务层
 * @author colin.
 * date 19-6-26 下午8:23
 */
class IntegralService
{

    const common_integral_log_type_list = [
        0 => ['id' => 0, 'name' => '减少', 'checked' => true],
        1 => ['id' => 1, 'name' => '增加'],
    ];

    /**
     * 用户积分日志添加
     * @param $user_id
     * @param $original_integral
     * @param $new_integral
     * @param string $msg
     * @param int $type
     * @param int $operation_id
     * @return bool
     */
    public function userIntegralLogAdd(
        $user_id,
        $original_integral,
        $new_integral,
        $msg = '',
        $type = 0,
        $operation_id = 0
    ) {
        $data = [
            'user_id'           => intval($user_id),
            'original_integral' => intval($original_integral),
            'new_integral'      => intval($new_integral),
            'msg'               => $msg,
            'type'              => intval($type),
            'operation_id'      => intval($operation_id),
            'add_time'          => time(),
        ];
        if (Db::table('user_integral_log')->insertGetId($data) > 0) {
            $type_msg       = IntegralService::common_integral_log_type_list[$type]['name'];
            $integral       = ($data['type'] == 0) ? $data['original_integral'] - $data['new_integral'] : $data['new_integral'] - $data['original_integral'];
            $detail         = $msg . '积分' . $type_msg . $integral;
            $massageService = make(MessageService::class);
            $massageService->messageAdd($user_id, '积分变动', $detail);

            // todo 用户登录数据更新防止数据存储session不同步展示
//            if (in_array(APPLICATION_CLIENT_TYPE, ['pc', 'h5'])) {
//                UserService::UserLoginRecord($user_id);
//            }

            return true;
        }
        return false;
    }

    /**
     * 前端积分列表条件
     * @param array $params
     * @return array
     */
    public function userIntegralLogListWhere($params = [])
    {
        // 条件初始化
        $where = [];

        // 用户id
        if (!empty($params['user'])) {
            $where[] = ['user_id', '=', $params['user']['id']];
        }

        if (!empty($params['keywords'])) {
            $where[] = ['msg', 'like', '%' . $params['keywords'] . '%'];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] == 1) {
            if (isset($params['type']) && $params['type'] > -1) {
                $where[] = ['type', '=', intval($params['type'])];
            }

            // 时间
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
     * 用户积分日志总数
     * @param array $where
     * @return int
     */
    public function userIntegralLogTotal($where = [])
    {
        return Db::table('user_integral_log')->where($where)->count();
    }

    /**
     * 积分日志列表
     * @param array $params
     * @return array
     */
    public function userIntegralLogList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $m        = isset($params['m']) ? intval($params['m']) : 0;
        $n        = isset($params['n']) ? intval($params['n']) : 10;
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::table('user_integral_log')->where($where)->limit($n)->offset($n * $m)->orderByRaw($order_by)->get()->toArray();
        if (!empty($data)) {
            foreach ($data as &$v) {

                // 时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = date('Y-m-d', $v['add_time']);
            }
        }
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 订单商品积分赠送
     * @param array $params
     * @return array
     */
    public function orderGoodsIntegralGiving($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'order_id',
                'error_msg'    => '订单id有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 订单
        $order = Db::table('Order')->selectRaw('id,user_id,status')->find(intval($params['order_id']));
        if (empty($order)) {
            return dataReturn('订单不存在或已删除，中止操作', 0);
        }
        if (!in_array($order['status'], [4])) {
            return dataReturn('当前订单状态不允许操作[' . $params['order_id'] . '-' . $order['status'] . ']', 0);
        }

        // 获取用户信息
        $user = Db::table('User')->find(intval($order['user_id']), ['id']);
        if (empty($user)) {
            return dataReturn('用户不存在或已删除，中止操作', 0);
        }

        // 获取订单商品
        $goods_all = Db::table('OrderDetail')->where(['order_id' => $params['order_id']])->pluck('goods_id')->toArray();
        if (!empty($goods_all)) {
            foreach ($goods_all as $goods_id) {
                $give_integral = Db::table('Goods')->where(['id' => $goods_id])->value('give_integral');
                if (!empty($give_integral)) {
                    // 用户积分添加
                    $user_integral = Db::table('User')->where(['id' => $user['id']])->value('integral');
                    if (!Db::table('User')->where(['id' => $user['id']])->increment('integral', $give_integral)) {
                        return dataReturn('用户积分赠送失败[' . $params['order_id'] . '-' . $goods_id . ']', -10);
                    }

                    // 积分日志
                    $this->userIntegralLogAdd($user['id'], $user_integral, $user_integral + $give_integral,
                        '订单商品完成赠送', 1);
                }
            }
            return dataReturn('操作成功', 0);
        }
        return dataReturn('没有需要操作的数据', 0);
    }

    /**
     * 后台管理员列表
     * @param array $params
     * @return array
     */
    public function adminIntegralList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $m        = isset($params['m']) ? intval($params['m']) : 0;
        $n        = isset($params['n']) ? intval($params['n']) : 10;
        $field    = 'user_integral_log.*,user.username,user.nickname,user.mobile,user.gender';
        $order_by = empty($params['order_by']) ? 'user_integral_log.id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::table('user_integral_log')->join('user',
            'user.id', '=',
            'user_integral_log.user_id')->where($where)->selectRaw($field)->limit($n)->offset($n * $m)->orderByRaw($order_by)->get()->toArray();
        if (!empty($data)) {
//            $common_integral_log_type_list = lang('common_integral_log_type_list');
//            $common_gender_list            = lang('common_gender_list');
            foreach ($data as &$v) {
                // 操作类型
//                $v['type_text'] = $common_integral_log_type_list[$v['type']]['name'];
//
//                // 性别
//                $v['gender_text'] = $common_gender_list[$v['gender']]['name'];

                // 时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = date('Y-m-d', $v['add_time']);
            }
        }
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 后台积分总数
     * @param array $where
     * @return int
     */
    public function adminIntegralTotal($where = [])
    {
        return Db::table('user_integral_log')->join('user',
            'user.id', '=', 'user_integral_log.user_id')->where($where)->count();
    }

    /**
     * 后台积分列表条件
     * @param array $params
     * @return array
     */
    public function adminIntegralListWhere($params = [])
    {
        $where = [];

        // 关键字
        if (!empty($params['keywords'])) {
            $where[] = [
                'user_integral_log.msg|user.username|user.nickname|user.mobile',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] == 1) {
            // 等值
            if (isset($params['type']) && $params['type'] > -1) {
                $where[] = ['user_integral_log.type', '=', intval($params['type'])];
            }
            if (isset($params['gender']) && $params['gender'] > -1) {
                $where[] = ['user.gender', '=', intval($params['gender'])];
            }

            if (!empty($params['time_start'])) {
                $where[] = ['user_integral_log.add_time', '>', strtotime($params['time_start'])];
            }
            if (!empty($params['time_end'])) {
                $where[] = ['user_integral_log.add_time', '<', strtotime($params['time_end'])];
            }
        }

        return $where;
    }

}