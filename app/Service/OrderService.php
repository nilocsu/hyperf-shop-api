<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * @author colin.
 * date 19-6-27 下午7:18
 */
class OrderService
{
    const   common_order_user_status  = [
        0 => ['id' => 0, 'name' => '待确认', 'checked' => true],
        1 => ['id' => 1, 'name' => '待付款'],
        2 => ['id' => 2, 'name' => '待发货'],
        3 => ['id' => 3, 'name' => '待收货'],
        4 => ['id' => 4, 'name' => '已完成'],
        5 => ['id' => 5, 'name' => '已取消'],
        6 => ['id' => 6, 'name' => '已关闭'],
    ];
    const   common_order_admin_status = [
        0 => ['id' => 0, 'name' => '待确认', 'checked' => true],
        1 => ['id' => 1, 'name' => '已确认/待支付'],
        2 => ['id' => 2, 'name' => '已支付/待发货'],
        3 => ['id' => 3, 'name' => '已发货/待收货'],
        4 => ['id' => 4, 'name' => '已完成'],
        5 => ['id' => 5, 'name' => '已取消'],
        6 => ['id' => 6, 'name' => '已关闭'],
    ];
    const   under_line_list           = ['CashPayment', 'DeliveryPayment'];

    /**
     * @var BuyService
     */
    private $buyService;
    /**
     * @var IntegralService
     */
    private $integralService;

    /**
     * @var RegionService
     */
    private $regionService;
    /**
     * @var ExpressService
     */
    private $expressService;
    /**
     * @var PayLogService
     */
    private $payLogService;

    /**
     * @var MessageService
     */
    private $messageService;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(
        BuyService $buyService,
        IntegralService $integralService,
        RegionService $regionService,
        ExpressService $expressService,
        PayLogService $payLogService,
        MessageService $messageService,
        UserService $userService
    ) {
        $this->buyService      = $buyService;
        $this->integralService = $integralService;
        $this->regionService   = $regionService;
        $this->expressService  = $expressService;
        $this->payLogService   = $payLogService;
        $this->messageService  = $messageService;
        $this->userService  = $userService;
    }

    /**
     * 订单支付
     * @param array $params
     * @return array|bool|string
     */
    public static function pay($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '订单id有误',
            ],
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

        // 获取订单信息
        $where = ['id' => intval($params['id']), 'user_id' => $params['user']['id']];
        $order = Db::table('order')->where($where)->first();
        if (empty($order)) {
            return dataReturn('资源不存在或已被删除', -1);
        }
        if ($order['status'] != 1) {
            $status_text = self::common_order_user_status[$order['status']]['name'];
            return dataReturn('状态不可操作[' . $status_text . ']', -1);
        }

        // todo 支付方式
        $payment_id = empty($params['payment_id']) ? $order['payment_id'] : intval($params['payment_id']);
        $payment    = PaymentService::paymentList(['where' => ['id' => $payment_id]]);
        if (empty($payment[0])) {
            return dataReturn('支付方式有误', -1);
        }

        // 更新订单支付方式
        if (!empty($params['payment_id']) && $params['payment_id'] != $order['payment_id']) {
            Db::table('order')->where(['id' => $order['id']])->update([
                'payment_id' => $payment_id,
                'upd_time'   => time(),
            ]);
        }

        // 金额为0直接支付成功
        if ($order['total_price'] <= 0.00) {
            // 非线上支付处理
            $params['user']['user_name_view'] = '用户-' . $params['user']['user_name_view'];
            $pay_result                       = self::orderPaymentUnderLine([
                'order'   => $order,
                'payment' => $payment[0],
                'user'    => $params['user'],
                'subject' => $params,
            ]);
            if ($pay_result['code'] == 0) {
                return dataReturn('支付成功', 0, ['data' => responseUrl('index/order/respond', ['appoint_status' => 0])]);
            }
            return $pay_result;
        }

        // todo 支付对接
        $payType = 'alipay';
        if ($payType == 'alipay') {

        }
    }

    /**
     * 管理员订单支付
     * @param array $params
     * @return array
     */
    public static function adminPay($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '订单id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'user',
                'error_msg'    => '管理员信息有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 获取订单信息
        $where = ['id' => intval($params['id'])];
        $order = Db::table('order')->where($where)->first()->toArray();
        if (empty($order)) {
            return dataReturn('资源不存在或已被删除', -1);
        }
        if ($order['status'] != 1) {
            $status_text = self::common_order_admin_status[$order['status']]['name'];
            return dataReturn('状态不可操作[' . $status_text . ']', -1);
        }

        // 支付方式
        $payment_id = empty($params['payment_id']) ? $order['payment_id'] : intval($params['payment_id']);
        $payment    = PaymentService::paymentList(['where' => ['id' => $payment_id]]);
        if (empty($payment[0])) {
            return dataReturn('支付方式有误', -1);
        }

        // 非线上支付处理
        return self::orderPaymentUnderLine([
            'order'   => $order,
            'payment' => $payment[0],
            'user'    => $params['user'],
            'subject' => $params,
        ]);
    }

    /**
     *
     * [OrderPaymentUnderLine 线下支付处理]
     * @param array $params
     * @return array
     */
    private static function orderPaymentUnderLine($params = [])
    {
        if (!empty($params['order']) && !empty($params['payment']) && !empty($params['user'])) {
            if (in_array($params['payment']['payment'],
                    self::under_line_list) || $params['order']['total_price'] <= 0.00) {
                // 支付处理
                $pay_params = [
                    'order'   => $params['order'],
                    'payment' => $params['payment'],
                    'pay'     => [
                        'trade_no'   => '',
                        'subject'    => isset($params['params']['subject']) ? $params['params']['subject'] : '订单支付',
                        'buyer_user' => $params['user']['user_name_view'],
                        'pay_price'  => $params['order']['total_price'],
                    ],
                ];
                return self::OrderPayHandle($pay_params);
            } else {
                return dataReturn('仅线下支付方式处理', -1);
            }
        }
        return dataReturn('无需处理', 0);
    }

    /**
     * 支付同步处理
     * @param array $params
     * @return array|bool|string
     */
    public static function respond($params = [])
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

        // 支付方式
        $payment_name = defined('PAYMENT_TYPE') ? PAYMENT_TYPE : (isset($params['paymentName']) ? $params['paymentName'] : '');
        if (empty($payment_name)) {
            return dataReturn('支付方式标记异常', -1);
        }
        $payment = PaymentService::PaymentList(['where' => ['payment' => $payment_name]]);
        if (empty($payment[0])) {
            return dataReturn('支付方式有误', -1);
        }

        // todo 支付数据校验
//        $pay_name = 'payment\\'.$payment_name;
//        $ret = (new $pay_name($payment[0]['config']))->Respond(array_merge($_GET, $_POST));
//        if(isset($ret['code']) && $ret['code'] == 0)
//        {
//            if(empty($ret['data']['out_trade_no']))
//            {
//                return dataReturn('单号有误', -1);
//            }
//            // 获取订单信息
//            $where = ['order_no'=>$ret['data']['out_trade_no'], 'is_delete_time'=>0, 'user_is_delete_time'=>0];
//            $order = Db::table('order')->where($where)->first();
//
//            // 非线上支付处理
//            self::OrderPaymentUnderLine([
//                'order'     => $order,
//                'payment'   => $payment[0],
//                'user'      => $params['user'],
//                'params'    => $params,
//            ]);
//        }
//        return $ret;
        return false;
    }

    /**
     * todo 支付异步处理
     * @param array $params
     * @return array
     */
    public static function notify($params = [])
    {
//        // 支付方式
//        $payment = PaymentService::paymentList(['where'=>['payment'=>PAYMENT_TYPE]]);
//        if(empty($payment[0]))
//        {
//            return dataReturn('支付方式有误', -1);
//        }
//
//        // 支付数据校验
//        $pay_name = 'payment\\'.PAYMENT_TYPE;
//        $ret = (new $pay_name($payment[0]['config']))->Respond(array_merge($_GET, $_POST));
//        if(!isset($ret['code']) || $ret['code'] != 0)
//        {
//            return $ret;
//        }
//
//        // 获取订单信息
//        $where = ['order_no'=>$ret['data']['out_trade_no'], 'is_delete_time'=>0, 'user_is_delete_time'=>0];
//        $order = Db::table('order')->where($where)->first();
//
//        // 支付处理
//        $pay_params = [
//            'order'     => $order,
//            'payment'   => $payment[0],
//            'pay'       => [
//                'trade_no'      => $ret['data']['trade_no'],
//                'subject'       => $ret['data']['subject'],
//                'buyer_user'    => $ret['data']['buyer_user'],
//                'pay_price'     => $ret['data']['pay_price'],
//            ],
//        ];
//        return self::OrderPayHandle($pay_params);
    }

    /**
     *
     * [OrderPayHandle 订单支付处理]
     * @param array $params
     * @return array|bool|string
     */
    public function orderPayHandle($params = [])
    {
        // 订单信息
        if (empty($params['order'])) {
            return dataReturn('资源不存在或已被删除', -1);
        }
        if ($params['order']['status'] > 1) {
            $status_text = self::common_order_user_status[$params['order']['status']]['name'];
            return dataReturn('状态不可操作[' . $status_text . ']', 0);
        }

        // 支付方式
        if (empty($params['payment'])) {
            return dataReturn('支付方式有误', -1);
        }

        // 支付参数
        $pay_price = isset($params['pay']['pay_price']) ? $params['pay']['pay_price'] : 0;

        // 写入支付日志
        $pay_log_data = [
            'user_id'       => $params['order']['user_id'],
            'order_id'      => $params['order']['id'],
            'total_price'   => $params['order']['total_price'],
            'trade_no'      => isset($params['pay']['trade_no']) ? $params['pay']['trade_no'] : '',
            'buyer_user'    => isset($params['pay']['buyer_user']) ? $params['pay']['buyer_user'] : '',
            'pay_price'     => $pay_price,
            'subject'       => isset($params['pay']['subject']) ? $params['pay']['subject'] : '订单支付',
            'payment'       => $params['payment']['payment'],
            'payment_name'  => $params['payment']['name'],
            'business_type' => 1,
        ];
        PayLogService::PayLogInsert($pay_log_data);

        // 开启事务
        Db::beginTransaction();

        // 消息通知
        $detail = '订单支付成功，金额' . PriceBeautify($params['order']['total_price']) . '元';
        $this->messageService->messageAdd($params['order']['user_id'], '订单支付', $detail, 1, $params['order']['id']);


        // 更新订单状态
        $upd_data = [
            'status'     => 2,
            'pay_status' => 1,
            'pay_price'  => $pay_price,
            'payment_id' => $params['payment']['id'],
            'pay_time'   => time(),
            'upd_time'   => time(),
        ];
        if (Db::table('order')->where(['id' => $params['order']['id']])->update($upd_data)) {
            // 添加状态日志
            if (self::OrderHistoryAdd($params['order']['id'], 2, $params['order']['status'], '支付', 0, '系统')) {
                // 库存扣除

                $ret = $this->buyService->orderInventoryDeduct([
                    'order_id'   => $params['order']['id'],
                    'order_data' => $upd_data,
                ]);
                if ($ret['code'] != 0) {
                    // 事务回滚
                    Db::rollback();
                    return dataReturn($ret['msg'], -10);
                }

                // 提交事务
                Db::commit();


                return dataReturn('支付成功', 0);
            }
        }

        // 事务回滚
        Db::rollback();

        // 处理失败
        return dataReturn('处理失败', -100);
    }

    /**
     * 订单列表条件
     * @param array $params
     * @return array
     */
    public static function orderListWhere($params = [])
    {
        // 用户类型
        $user_type = isset($params['user_type']) ? $params['user_type'] : 'user';

        // 条件初始化
        $where = [
            ['is_delete_time', '=', 0],
        ];

        // id
        if (!empty($params['id'])) {
            $where[] = ['id', '=', intval($params['id'])];
        }

        // 用户类型
        if (isset($params['user_type']) && $params['user_type'] == 'user') {
            $where[] = ['user_is_delete_time', '=', 0];

            // 用户id
            if (!empty($params['user'])) {
                $where[] = ['user_id', '=', $params['user']['id']];
            }
        }

        if (!empty($params['keywords'])) {
            $where[] = ['order_no|receive_tel|receive_name', 'like', '%' . $params['keywords'] . '%'];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] == 1) {
            // 等值
            if (isset($params['payment_id']) && $params['payment_id'] > -1) {
                $where[] = ['payment_id', '=', intval($params['payment_id'])];
            }
            if (isset($params['express_id']) && $params['express_id'] > -1) {
                $where[] = ['express_id', '=', intval($params['express_id'])];
            }
            if (isset($params['pay_status']) && $params['pay_status'] > -1) {
                $where[] = ['pay_status', '=', intval($params['pay_status'])];
            }
            if (!empty($params['client_type'])) {
                $where[] = ['client_type', '=', $params['client_type']];
            }
            if (isset($params['status']) && $params['status'] != -1) {
                // 多个状态,字符串以半角逗号分割
                if (!is_array($params['status'])) {
                    $params['status'] = explode(',', $params['status']);
                }
                $where[] = ['status', 'in', $params['status']];
            }

            // 评价状态
            if (isset($params['is_comments']) && $params['is_comments'] > -1) {
                $comments_field = ($user_type == 'user') ? 'user_is_comments' : 'is_comments';
                if ($params['is_comments'] == 0) {
                    $where[] = [$comments_field, '=', 0];
                } else {
                    $where[] = [$comments_field, '>', 0];
                }
            }

            // 时间
            if (!empty($params['time_start'])) {
                $where[] = ['add_time', '>', strtotime($params['time_start'])];
            }
            if (!empty($params['time_end'])) {
                $where[] = ['add_time', '<', strtotime($params['time_end'])];
            }

            // 价格
            if (!empty($params['price_start'])) {
                $where[] = ['price', '>', floatval($params['price_start'])];
            }
            if (!empty($params['price_end'])) {
                $where[] = ['price', '<', floatval($params['price_end'])];
            }
        }
        return $where;
    }

    /**
     * 订单总数
     * @param array $where
     * @return int
     */
    public  function orderTotal($where = [])
    {
        return (int)Db::table('order')->where($where)->count();
    }

    /**
     * 订单列表
     * @param array $params
     * @return array
     */
    public function orderList($params = [])
    {
        $where             = empty($params['where']) ? [] : $params['where'];
        $m                 = isset($params['m']) ? intval($params['m']) : 0;
        $n                 = isset($params['n']) ? intval($params['n']) : 10;
        $order_by          = empty($params['order_by']) ? 'id desc' : $params['order_by'];
        $is_items          = isset($params['is_items']) ? intval($params['is_items']) : 1;
        $is_excel_export   = isset($params['is_excel_export']) ? intval($params['is_excel_export']) : 0;
        $is_orderaftersale = isset($params['is_orderaftersale']) ? intval($params['is_orderaftersale']) : 0;

        // 获取订单
        $data = Db::table('order')->where($where)->limit($n)->offset($n * $m)->orderBy($order_by)->get();
        if (!empty($data)) {
            $order_status_list    = self::common_order_user_status;
            $order_pay_status     = lang('common_order_pay_status');
            $common_platform_type = lang('common_platform_type');
            foreach ($data as &$v) {
                // 用户信息
                if (isset($v['user_id'])) {
                    if (isset($params['is_public']) && $params['is_public'] == 0) {
                        $v['user'] = $this->userService->getUserViewInfo($v['user_id']);
                        
                    }
                }

                // 客户端
                $v['client_type_name'] = isset($common_platform_type[$v['client_type']]) ? $common_platform_type[$v['client_type']]['name'] : '';

                // 状态
                $v['status_name'] = $order_status_list[$v['status']]['name'];

                // 支付状态
                $v['pay_status_name'] = $order_pay_status[$v['pay_status']]['name'];

                // 快递公司
                $v['express_name'] = ExpressService::ExpressName($v['express_id']);

                // 支付方式
                $v['payment_name'] = ($v['status'] <= 1) ? null : PaymentService::OrderPaymentName($v['id']);

                // 收件人地址
                $v['receive_province_name'] = RegionService::regionName($v['receive_province']);
                $v['receive_city_name']     = RegionService::regionName($v['receive_city']);
                $v['receive_county_name']   = RegionService::regionName($v['receive_county']);

                // 创建时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = date('Y-m-d', $v['add_time']);
                $v['add_time']      = date('Y-m-d H:i:s', $v['add_time']);

                // 更新时间
                $v['upd_time'] = date('Y-m-d H:i:s', $v['upd_time']);

                // 确认时间
                $v['confirm_time'] = empty($v['confirm_time']) ? null : date('Y-m-d H:i:s', $v['confirm_time']);

                // 支付时间
                $v['pay_time'] = empty($v['pay_time']) ? null : date('Y-m-d H:i:s', $v['pay_time']);

                // 发货时间
                $v['delivery_time'] = empty($v['delivery_time']) ? null : date('Y-m-d H:i:s', $v['delivery_time']);

                // 收货时间
                $v['collect_time'] = empty($v['collect_time']) ? null : date('Y-m-d H:i:s', $v['collect_time']);

                // 取消时间
                $v['cancel_time'] = empty($v['cancel_time']) ? null : date('Y-m-d H:i:s', $v['cancel_time']);

                // 关闭时间
                $v['close_time'] = empty($v['close_time']) ? null : date('Y-m-d H:i:s', $v['close_time']);

                // 评论时间
                $v['user_is_comments_time'] = ($v['user_is_comments'] == 0) ? null : date('Y-m-d H:i:s',
                    $v['user_is_comments']);

                // 空字段数据处理
                if (empty($v['express_number'])) {
                    $v['express_number'] = null;
                }
                if (empty($v['user_note'])) {
                    $v['user_note'] = null;
                }

                // 扩展数据
                $v['extension_data'] = empty($v['extension_data']) ? null : json_decode($v['extension_data'], true);

                // 订单详情
                if ($is_items == 1) {
                    $items              = Db::table('order_detail')->where(['order_id' => $v['id']])->get();
                    $excel_export_items = '';
                    if (!empty($items)) {
                        foreach ($items as &$vs) {
                            // 商品信息
                            $vs['images']      = ResourcesService::attachmentPathViewHandle($vs['images']);
                            $vs['goods_url']   = responseUrl('index/goods/index', ['id' => $vs['goods_id']]);
                            $vs['total_price'] = $vs['buy_number'] * $vs['price'];

                            // 规格
                            if (!empty($vs['spec'])) {
                                $vs['spec']      = json_decode($vs['spec'], true);
                                $vs['spec_text'] = implode('，', array_map(function ($spec) {
                                    return $spec['type'] . ':' . $spec['value'];
                                }, $vs['spec']));
                            } else {
                                $vs['spec']      = null;
                                $vs['spec_text'] = null;
                            }

                            // 是否excel导出
                            if ($is_excel_export == 1) {
                                $excel_export_items .= '名称：' . $vs['title'] . "\n";
                                $excel_export_items .= '图片：' . $vs['images'] . "\n";
                                $excel_export_items .= '地址：' . $vs['goods_url'] . "\n";
                                $excel_export_items .= '原价：' . $vs['original_price'] . "\n";
                                $excel_export_items .= '销售价：' . $vs['price'] . "\n";
                                $excel_export_items .= '总价：' . $vs['total_price'] . "\n";
                                $excel_export_items .= '型号：' . $vs['model'] . "\n";
                                $excel_export_items .= '规格：' . $vs['spec_text'] . "\n";
                                $excel_export_items .= '重量：' . $vs['spec_weight'] . "\n";
                                $excel_export_items .= '编码：' . $vs['spec_coding'] . "\n";
                                $excel_export_items .= '条形码：' . $vs['spec_barcode'] . "\n";
                                $excel_export_items .= '购买数量：' . $vs['buy_number'] . "\n";
                                $excel_export_items .= "\n";
                            }

                            // 是否获取最新一条售后信息
                            if ($is_orderaftersale == 1) {
                                $vs['order_aftersale'] = Db::table('order_aftersale')->where(['order_detail_id' => $vs['id']])->orderBy('id', 'desc')->get();
                            }
                        }
                    }
                    $v['items']              = $items;
                    $v['items_count']        = count($items);
                    $v['excel_export_items'] = $excel_export_items;

                    // 描述
                    $v['describe'] = '共' . $v['buy_number_count'] . '件 合计:￥' . $v['total_price'] . '元';
                }

                // todo 订单处理后
            }
        }

        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 订单日志添加
     * @param $order_id
     * @param $new_status
     * @param $original_status
     * @param string $msg
     * @param int $creator
     * @param string $creator_name
     * @return bool
     */
    public static function orderHistoryAdd(
        $order_id,
        $new_status,
        $original_status,
        $msg = '',
        $creator = 0,
        $creator_name = ''
    ) {
        // 状态描述
        $order_status_list    = self::common_order_user_status;
        $original_status_name = $order_status_list[$original_status]['name'];
        $new_status_name      = $order_status_list[$new_status]['name'];
        $msg                  .= '[' . $original_status_name . '-' . $new_status_name . ']';

        // 添加
        $data = [
            'order_id'        => intval($order_id),
            'new_status'      => intval($new_status),
            'original_status' => intval($original_status),
            'msg'             => htmlentities($msg),
            'creator'         => intval($creator),
            'creator_name'    => htmlentities($creator_name),
            'add_time'        => time(),
        ];

        // 日志添加
        if (Db::table('order_status_history')->insertGetId($data) > 0) {
            // todo 订单状态改变添加日志

            return true;
        }
        return false;
    }

    /**
     * 订单取消
     * @param array $params
     * @return array
     */
    public function orderCancel($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '订单id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'user_id',
                'error_msg'    => '用户id有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 获取订单信息
        $where = [
            'id'                  => intval($params['id']),
            'user_id'             => $params['user_id'],
            'is_delete_time'      => 0,
            'user_is_delete_time' => 0,
        ];
        $order = Db::table('order')->where($where)->select(['id','status','user_id'])->first();
        if (empty($order)) {
            return dataReturn('资源不存在或已被删除', -1);
        }
        if (!in_array($order['status'], [0, 1])) {
            $status_text = self::common_order_user_status[$order['status']]['name'];
            return dataReturn('状态不可操作[' . $status_text . ']', -1);
        }

        // 开启事务
        Db::beginTransaction();
        $upd_data = [
            'status'      => 5,
            'cancel_time' => time(),
            'upd_time'    => time(),
        ];
        if (Db::table('order')->where($where)->update($upd_data)) {
            // 库存回滚
            $ret = $this->buyService->orderInventoryRollback(['order_id' => $order['id'], 'order_data' => $upd_data]);
            if ($ret['code'] != 0) {
                // 事务回滚
                Db::rollback();
                return dataReturn($ret['msg'], -10);
            }

            // 用户消息
            $this->messageService->messageAdd($order['user_id'], '订单取消', '订单取消成功', 1, $order['id']);

            // 订单状态日志
            $creator      = isset($params['creator']) ? intval($params['creator']) : 0;
            $creator_name = isset($params['creator_name']) ? htmlentities($params['creator_name']) : '';
            self::OrderHistoryAdd($order['id'], $upd_data['status'], $order['status'], '取消', $creator, $creator_name);

            // 提交事务
            Db::commit();
            return dataReturn('取消成功', 0);
        }

        // 事务回滚
        Db::rollback();
        return dataReturn('取消失败', -1);
    }

    /**
     * 订单发货
     * @param array $params
     * @return array
     */
    public function orderDelivery($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '订单id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'user_id',
                'error_msg'    => '用户id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'express_id',
                'error_msg'    => '快递id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'express_number',
                'error_msg'    => '快递单号有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 获取订单信息
        $where = [
            'id'                  => intval($params['id']),
            'user_id'             => $params['user_id'],
            'is_delete_time'      => 0,
            'user_is_delete_time' => 0,
        ];
        $order = Db::table('order')->where($where)->select(['id','status','user_id'])->first();
        if (empty($order)) {
            return dataReturn('资源不存在或已被删除', -1);
        }
        if (!in_array($order['status'], [2])) {
            $status_text = self::common_order_user_status[$order['status']]['name'];
            return dataReturn('状态不可操作[' . $status_text . ']', -1);
        }

        // 开启事务
        Db::beginTransaction();
        $upd_data = [
            'status'         => 3,
            'express_id'     => intval($params['express_id']),
            'express_number' => isset($params['express_number']) ? $params['express_number'] : '',
            'delivery_time'  => time(),
            'upd_time'       => time(),
        ];
        if (Db::table('order')->where($where)->update($upd_data)) {
            // 库存扣除
            $ret = $this->buyService->orderInventoryDeduct(['order_id' => $order['id'], 'order_data' => $upd_data]);
            if ($ret['code'] != 0) {
                // 事务回滚
                Db::rollback();
                return dataReturn($ret['msg'], -10);
            }

            // 用户消息
            $this->messageService->messageAdd($order['user_id'], '订单发货', '订单已发货', 1, $order['id']);

            // 订单状态日志
            $creator      = isset($params['creator']) ? intval($params['creator']) : 0;
            $creator_name = isset($params['creator_name']) ? htmlentities($params['creator_name']) : '';
            self::OrderHistoryAdd($order['id'], $upd_data['status'], $order['status'], '收货', $creator, $creator_name);

            // 提交事务
            Db::commit();
            return dataReturn('发货成功', 0);
        }

        // 事务回滚
        Db::rollback();
        return dataReturn('发货失败', -1);
    }

    /**
     * 订单收货
     * @param array $params
     * @return array
     */
    public function orderCollect($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '订单id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'user_id',
                'error_msg'    => '用户id有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 获取订单信息
        $where = [
            'id'                  => intval($params['id']),
            'user_id'             => $params['user_id'],
            'is_delete_time'      => 0,
            'user_is_delete_time' => 0,
        ];
        $order = Db::table('order')->where($where)->select(['id','status','user_id'])->first();
        if (empty($order)) {
            return dataReturn('资源不存在或已被删除', -1);
        }
        if (!in_array($order['status'], [3])) {
            $status_text = self::common_order_user_status[$order['status']]['name'];
            return dataReturn('状态不可操作[' . $status_text . ']', -1);
        }

        // 开启事务
        Db::beginTransaction();

        // 更新订单状态
        $upd_data = [
            'status'       => 4,
            'collect_time' => time(),
            'upd_time'     => time(),
        ];
        if (Db::table('order')->where($where)->update($upd_data)) {
            // 订单商品积分赠送
            $ret =  $this->integralService->orderGoodsIntegralGiving(['order_id'=>$order['id']]);
            if ($ret['code'] != 0) {
                // 事务回滚
                Db::rollback();
                return dataReturn($ret['msg'], -10);
            }

            // 订单商品销量增加
            $ret = self::GoodsSalesCountInc(['order_id' => $order['id']]);
            if ($ret['code'] != 0) {
                // 事务回滚
                Db::rollback();
                return dataReturn($ret['msg'], -10);
            }

            // 用户消息
            $this->messageService->messageAdd($order['user_id'], '订单收货', '订单收货成功', 1, $order['id']);

            // 订单状态日志
            $creator      = isset($params['creator']) ? intval($params['creator']) : 0;
            $creator_name = isset($params['creator_name']) ? htmlentities($params['creator_name']) : '';
            self::OrderHistoryAdd($order['id'], $upd_data['status'], $order['status'], '收货', $creator, $creator_name);

            // 提交事务
            Db::commit();
            return dataReturn('收货成功', 0);
        }

        // 事务回滚
        Db::rollback();
        return dataReturn('收货失败', -1);
    }

    /**
     * 订单确认
     * @param array $params
     * @return array
     */
    public function orderConfirm($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '订单id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'user_id',
                'error_msg'    => '用户id有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 获取订单信息
        $where = [
            'id'                  => intval($params['id']),
            'user_id'             => $params['user_id'],
            'is_delete_time'      => 0,
            'user_is_delete_time' => 0,
        ];
        $order = Db::table('order')->where($where)->select(['id','status','user_id'])->first();
        if (empty($order)) {
            return dataReturn('资源不存在或已被删除', -1);
        }
        if (!in_array($order['status'], [0])) {
            $status_text = self::common_order_admin_status[$order['status']]['name'];
            return dataReturn('状态不可操作[' . $status_text . ']', -1);
        }

        // 开启事务
        Db::beginTransaction();

        // 更新订单状态
        $upd_data = [
            'status'       => 1,
            'confirm_time' => time(),
            'upd_time'     => time(),
        ];
        if (Db::table('order')->where($where)->update($upd_data)) {
            // 库存扣除
            $ret = $this->buyService->orderInventoryDeduct(['order_id' => $params['id'], 'order_data' => $upd_data]);
            if ($ret['code'] != 0) {
                // 事务回滚
                Db::rollback();
                return dataReturn($ret['msg'], -10);
            }

            // 用户消息
           $this->messageService->messageAdd($order['user_id'], '订单确认', '订单确认成功', 1, $order['id']);

            // 订单状态日志
            $creator      = isset($params['creator']) ? intval($params['creator']) : 0;
            $creator_name = isset($params['creator_name']) ? htmlentities($params['creator_name']) : '';
            self::OrderHistoryAdd($order['id'], $upd_data['status'], $order['status'], '确认', $creator, $creator_name);

            // 事务提交
            Db::commit();
            return dataReturn('确认成功', 0);
        }

        // 事务回滚
        Db::rollback();
        return dataReturn('确认失败', -1);
    }

    /**
     * 订单删除
     * @param array $params
     * @return array
     */
    public function orderDelete($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '订单id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'user_id',
                'error_msg'    => '用户id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'user_type',
                'error_msg'    => '用户类型有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 用户类型
        switch ($params['user_type']) {
            case 'admin' :
                $delete_field = 'is_delete_time';
                break;

            case 'user' :
                $delete_field = 'user_is_delete_time';
                break;
        }
        if (empty($delete_field)) {
            return dataReturn('用户类型有误[' . $params['user_type'] . ']', -2);
        }

        // 获取订单信息
        $where = [
            'id'                  => intval($params['id']),
            'user_id'             => $params['user_id'],
            'is_delete_time'      => 0,
            'user_is_delete_time' => 0,
        ];
        $order = Db::table('order')->where($where)->select(['id','status','user_id'])->first();
        if (empty($order)) {
            return dataReturn('资源不存在或已被删除', -1);
        }
        if (!in_array($order['status'], [4, 5, 6])) {
            $status_text = self::common_order_user_status[$order['status']]['name'];
            return dataReturn('状态不可操作[' . $status_text . ']', -1);
        }

        $data = [
            $delete_field => time(),
            'upd_time'    => time(),
        ];
        if (Db::table('order')->where($where)->update($data)) {
            // 用户消息
            $this->messageService->messageAdd($order['user_id'], '订单删除', '订单删除成功', 1, $order['id']);

            return dataReturn('删除成功', 0);
        }
        return dataReturn('删除失败或资源不存在', -1);
    }

    /**
     * 订单每个环节状态总数
     * @param array $params
     * @return array
     */
    public  function orderStatusStepTotal($params = [])
    {
        // 状态数据封装
        $result            = [];
        $order_status_list = self::common_order_user_status;
        foreach ($order_status_list as $v) {
            $result[] = [
                'name'   => $v['name'],
                'status' => $v['id'],
                'count'  => 0,
            ];
        }

        // 用户类型
        $user_type = isset($params['user_type']) ? $params['user_type'] : '';

        // 条件
        $where                   = [];
        $where['is_delete_time'] = 0;

        // 用户类型
        switch ($user_type) {
            case 'user' :
                $where['user_is_delete_time'] = 0;
                break;
        }

        // 用户条件
        if ($user_type == 'user') {
            if (!empty($params['user'])) {
                $where['user_id'] = $params['user']['id'];
            } else {
                return dataReturn('用户信息有误', 0, $result);
            }
        }

        $data  = Db::table('order')->where($where)->select(['COUNT(DISTINCT id) AS count', 'status'])->groupBy(['status'])->get();

        // 数据处理
        if (!empty($data)) {
            foreach ($result as &$v) {
                foreach ($data as $vs) {
                    if ($v['status'] == $vs['status']) {
                        $v['count'] = $vs['count'];
                        continue;
                    }
                }
            }
        }

        // 待评价状态站位100
        if (isset($params['is_comments']) && $params['is_comments'] == 1) {
            switch ($user_type) {
                case 'user' :
                    $where['user_is_comments'] = 0;
                    break;
                case 'admin' :
                    $where['is_comments'] = 0;
                    break;
                default :
                    $where['user_is_comments'] = 0;
                    $where['is_comments']      = 0;
            }
            $where['status'] = 4;
            $result[]        = [
                'name'   => '待评价',
                'status' => 100,
                'count'  => (int)Db::table('order')->where($where)->count(),
            ];
        }

        return dataReturn('处理成功', 0, $result);
    }

    /**
     * 订单商品销量添加
     * @param array $params
     * @return array
     */
    public  function goodsSalesCountInc($params = [])
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

        // 获取订单商品
        $order_detail = Db::table('order_detail')->select(['goods_id','buy_number'])->where(['order_id' => $params['order_id']])->get();
        if (!empty($order_detail)) {
            foreach ($order_detail as $v) {
                if (!Db::table('Goods')->where(['id' => $v['goods_id']])->increment('sales_count', $v['buy_number'])) {
                    return dataReturn('订单商品销量增加失败[' . $params['order_id'] . '-' . $v['goods_id'] . ']', -10);
                }
            }
            return dataReturn('操作成功', 0);
        } else {
            return dataReturn('订单有误，没有找到相关商品', -100);
        }
    }

    /**
     * 支付状态校验
     * @param array $params
     * @return array
     */
    public function orderPayCheck($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'order_no',
                'error_msg'    => '订单号有误',
            ],
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

        // 获取订单状态
        $where = ['order_no' => $params['order_no'], 'user_id' => $params['user']['id']];
        $order = Db::table('order')->where($where)->select(['id','pay_status'])->first();
        if (empty($order)) {
            return dataReturn('订单不存在', -400);
        }
        if ($order['pay_status'] == 1) {
            return dataReturn('支付成功', 0, ['url' => responseUrl('index/order/detail', ['id' => $order['id']])]);
        }
        return dataReturn('支付中', -300);
    }

}