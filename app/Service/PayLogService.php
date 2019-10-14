<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 支付日志服务层
 * @author colin.
 * date 19-6-27 下午12:24
 */
class PayLogService
{
    /**
     * 支付日志添加
     * @param   array $params [输入参数]
     * @param   [int]               $user_id        [用户id]
     * @param   [int]               $order_id       [业务订单id]
     * @param   [float]             $total_price    [业务订单实际金额]
     * @param   [string]            $trade_no       [支付平台交易号]
     * @param   [string]            $buyer_user     [支付平台用户帐号]
     * @param   [float]             $pay_price      [支付金额]
     * @param   [string]            $subject        [业务订单名称]
     * @param   [string]            $payment        [支付方式标记]
     * @param   [string]            $payment_name   [支付方式名称]
     * @param   [int]               $business_type  [业务类型（0默认, 1订单, 2充值, ...）]
     * @return  bool                           [成功true, 失败false]
     */
    public static function payLogInsert($params = [])
    {
        $data = [
            'user_id'       => isset($params['user_id']) ? intval($params['user_id']) : 0,
            'order_id'      => isset($params['order_id']) ? intval($params['order_id']) : 0,
            'total_price'   => isset($params['total_price']) ? PriceNumberFormat($params['total_price']) : 0.00,
            'trade_no'      => isset($params['trade_no']) ? $params['trade_no'] : '',
            'buyer_user'    => isset($params['buyer_user']) ? $params['buyer_user'] : '',
            'pay_price'     => isset($params['pay_price']) ? PriceNumberFormat($params['pay_price']) : 0.00,
            'subject'       => isset($params['subject']) ? $params['subject'] : '',
            'payment'       => isset($params['payment']) ? $params['payment'] : '',
            'payment_name'  => isset($params['payment_name']) ? $params['payment_name'] : '',
            'business_type' => isset($params['business_type']) ? intval($params['business_type']) : 0,
            'add_time'      => time(),
        ];
        return Db::table('pay_log')->insertGetId($data) > 0;
    }

    /**
     * 获取支付日志类型
     * @return array
     */
    public static function payLogTypeList()
    {
        $data = Db::table('pay_log')->selectRaw('payment AS id, payment_name AS name')->groupBy('payment')->get()->toArray();
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 后台管理员列表
     * @param array $params
     * @return array
     */
    public static function adminPayLogList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $m        = isset($params['m']) ? intval($params['m']) : 0;
        $n        = isset($params['n']) ? intval($params['n']) : 10;
        $field    = 'pay_log.*,user.username,user.nickname,user.mobile,user.gender';
        $order_by = empty($params['order_by']) ? 'pay_log.id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::table('pay_log')->join('user',
            'user.id', '=',
            'pay_log.user_id')->where($where)->selectRaw($field)->limit($n)->offset($m * $n)->orderBy($order_by)->get();
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * @param array $where
     * @return int
     */
    public static function adminPayLogTotal($where = [])
    {
        return Db::table('pay_log')->join('user',
            'user.id', '=', 'pay_log.user_id')->where($where)->count();
    }

    /**
     * 列表条件
     * @param array $params
     * @return array
     */
    public static function adminPayLogListWhere($params = [])
    {
        $where = [];

        // 关键字
        if (!empty($params['keywords'])) {
            $where[] = [
                'pay_log.trade_no|user.username|user.nickname|user.mobile',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] == 1) {
            // 等值
            if (isset($params['business_type']) && $params['business_type'] > -1) {
                $where[] = ['pay_log.business_type', '=', intval($params['business_type'])];
            }
            if (!empty($params['pay_type'])) {
                $where[] = ['pay_log.payment', '=', $params['pay_type']];
            }
            if (isset($params['gender']) && $params['gender'] > -1) {
                $where[] = ['user.gender', '=', intval($params['gender'])];
            }

            if (!empty($params['price_start'])) {
                $where[] = ['pay_log.pay_price', '>', PriceNumberFormat($params['price_start'])];
            }
            if (!empty($params['price_end'])) {
                $where[] = ['pay_log.pay_price', '<', PriceNumberFormat($params['price_end'])];
            }

            if (!empty($params['time_start'])) {
                $where[] = ['pay_log.add_time', '>', strtotime($params['time_start'])];
            }
            if (!empty($params['time_end'])) {
                $where[] = ['pay_log.add_time', '<', strtotime($params['time_end'])];
            }
        }

        return $where;
    }

    /**
     * @param array $params
     * @return array
     */
    public static function payLogDelete($params = [])
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
        if (Db::table('pay_log')->where(['id' => $params['id']])->delete()) {
            return dataReturn('删除成功');
        }

        return dataReturn('删除失败或资源不存在', -100);
    }

}