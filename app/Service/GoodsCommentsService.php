<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 商品评论服务层
 * @author colin.
 * date 19-6-26 下午6:38
 */
class GoodsCommentsService
{

    // 用户端 - 订单管理
    const  common_order_user_status               = [
        0 => ['id' => 0, 'name' => '待确认', 'checked' => true],
        1 => ['id' => 1, 'name' => '待付款'],
        2 => ['id' => 2, 'name' => '待发货'],
        3 => ['id' => 3, 'name' => '待收货'],
        4 => ['id' => 4, 'name' => '已完成'],
        5 => ['id' => 5, 'name' => '已取消'],
        6 => ['id' => 6, 'name' => '已关闭'],
    ];
    const  common_is_text_list                    = [
        0 => ['id' => 0, 'name' => '否', 'checked' => true],
        1 => ['id' => 1, 'name' => '是'],
    ];
    const  common_goods_comments_rating_list      = [
        0 => ['name' => '未评分', 'badge' => ''],
        1 => ['name' => '1分', 'badge' => 'am-badge-danger'],
        2 => ['name' => '2分', 'badge' => 'am-badge-warning'],
        3 => ['name' => '3分', 'badge' => 'am-badge-secondary'],
        4 => ['name' => '4分', 'badge' => 'am-badge-primary'],
        5 => ['name' => '5分', 'badge' => 'am-badge-success'],
    ];
    const  common_goods_rating_business_type_list = [
        'order' => '订单',
    ];

    const common_order_after_sale_refund_list = [
        0 => ['value' => 0, 'name' => '原路退回'],
        1 => ['value' => 1, 'name' => '退至钱包'],
        2 => ['value' => 2, 'name' => '手动处理'],
    ];

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var GoodsService
     */
    private $goodsService;

    public function __construct(UserService $userService, GoodsService $goodsService)
    {
        $this->userService  = $userService;
        $this->goodsService = $goodsService;
    }

    /**
     * 订单评论
     * @param array $params
     * @return array
     */
    public function comments($params = [])
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
                'key_name'     => 'business_type',
                'error_msg'    => '业务类型标记不能为空',
            ],
            [
                'checked_type' => 'isset',
                'key_name'     => 'goods_id',
                'error_msg'    => '商品id有误',
            ],
            [
                'checked_type' => 'is_array',
                'key_name'     => 'goods_id',
                'error_msg'    => '商品数据格式有误',
            ],
            [
                'checked_type' => 'isset',
                'key_name'     => 'rating',
                'error_msg'    => '评级有误',
            ],
            [
                'checked_type' => 'is_array',
                'key_name'     => 'rating',
                'error_msg'    => '评级数据格式有误',
            ],
            [
                'checked_type' => 'isset',
                'key_name'     => 'content',
                'error_msg'    => '评论内容有误',
            ],
            [
                'checked_type' => 'is_array',
                'key_name'     => 'content',
                'error_msg'    => '评论内容数据格式有误',
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
        $order_id = intval($params['id']);
        $where    = [
            'id'                  => $order_id,
            'user_id'             => $params['user']['id'],
            'is_delete_time'      => 0,
            'user_is_delete_time' => 0,
        ];
        $order    = Db::table('order')->where($where)->selectRaw('id,status,shop_id,user_is_comments')->first();
        if (empty($order)) {
            return dataReturn('资源不存在或已被删除', -1);
        }
        if ($order['status'] != 4) {
            $status_text = self::common_order_user_status[$order['status']]['name'];
            return dataReturn('状态不可操作[' . $status_text . ']', -1);
        }
        if ($order['user_is_comments'] != 0) {
            return dataReturn('该订单你已进行过评论', -10);
        }

        // 处理数据
        Db::beginTransaction();
        foreach ($params['goods_id'] as $k => $goods_id) {
            $data = [
                'user_id'       => $params['user']['id'],
                'shop_id'       => $order['shop_id'],
                'order_id'      => $order_id,
                'goods_id'      => $goods_id,
                'business_type' => $params['business_type'],
                'content'       => isset($params['content'][$k]) ? htmlspecialchars(trim($params['content'][$k])) : '',
                'rating'        => isset($params['rating'][$k]) ? intval($params['rating'][$k]) : 0,
                'is_anonymous'  => isset($params['is_anonymous']) ? min(1, intval($params['is_anonymous'])) : 0,
                'add_time'      => time(),
            ];
            if (Db::table('goods_comments')->insertGetId($data) <= 0) {
                Db::rollback();
                return dataReturn('评论内容添加失败', -100);
            }
        }

        // 订单评论状态更新
        if (!Db::table('order')->where($where)->update(['user_is_comments' => time(), 'upd_time' => time()])) {
            Db::rollback();
            return dataReturn('订单更新失败', -101);
        }

        Db::commit();
        return dataReturn('评论成功', 0);
    }

    /**
     * 获取商品评论列表
     * @param array $params
     * @return array
     */
    public function goodsCommentsList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $m        = isset($params['m']) ? intval($params['m']) : 0;
        $n        = isset($params['n']) ? intval($params['n']) : 10;
        $order_by = empty($params['order_by']) ? 'id desc' : $params['order_by'];

        // 获取数据列表
        $data = Db::table('goods_comments')->where($where)->limit($n)->offset($m * $n)->orderByRaw($order_by)->get()->toArray();
        if (!empty($data)) {
            $common_is_text_list                    = self::common_is_text_list;
            $common_goods_comments_rating_list      = self::common_goods_comments_rating_list;
            $common_goods_rating_business_type_list = self::common_goods_rating_business_type_list;
            foreach ($data as &$v) {
                // 用户信息
                $user = $this->userService->getUserViewInfo($v['user_id']);
                if (!isset($params['is_public']) || $params['is_public'] == 1) {
                    $v['user'] = [
                        'avatar'         => $user['avatar'],
                        'user_name_view' => ($v['is_anonymous'] == 1) ? '匿名' : substr($user['user_name_view'], 0,
                                3) . '***' . substr($user['user_name_view'], -3),
                    ];
                } else {
                    $v['user'] = $user;
                }

                // 获取商品信息
                $goods_params = [
                    'where' => [
                        'id'             => $v['goods_id'],
                        'is_delete_time' => 0,
                    ],
                    'field' => 'id,title,images,price,min_price',
                ];
                $ret          = $this->goodsService->goodsList($goods_params);
                $v['goods']   = isset($ret['data'][0]) ? $ret['data'][0] : [];

                // 业务类型
                $v['business_type_text'] = array_key_exists($v['business_type'],
                    $common_goods_rating_business_type_list) ? $common_goods_rating_business_type_list[$v['business_type']] : null;
                $msg                     = null;
                switch ($v['business_type']) {
                    // 订单
                    case 'order' :
                        $msg = $this->businessTypeOrderSpec($v['order_id'], $v['goods_id']);
                }
                $v['msg'] = empty($msg) ? null : $msg;

                // 评分
                $v['rating_text'] = $common_goods_comments_rating_list[$v['rating']]['name'];

                // 是否
                $v['is_reply_text']     = isset($common_is_text_list[$v['is_reply']]) ? $common_is_text_list[$v['is_reply']]['name'] : '';
                $v['is_anonymous_text'] = isset($common_is_text_list[$v['is_anonymous']]) ? $common_is_text_list[$v['is_anonymous']]['name'] : '';
                $v['is_show_text']      = isset($common_is_text_list[$v['is_show']]) ? $common_is_text_list[$v['is_show']]['name'] : '';

                // 回复时间
                $v['reply_time_time'] = empty($v['reply_time']) ? null : date('Y-m-d H:i:s', $v['reply_time']);
                $v['reply_time_date'] = empty($v['reply_time']) ? null : date('Y-m-d', $v['reply_time']);

                // 评论时间
                $v['add_time_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['add_time_date'] = date('Y-m-d', $v['add_time']);

                // 更新时间
                $v['upd_time_time'] = empty($v['upd_time']) ? null : date('Y-m-d H:i:s', $v['upd_time']);
                $v['upd_time_date'] = empty($v['upd_time']) ? null : date('Y-m-d', $v['upd_time']);
            }
        }
        //print_r($data);
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 订单规格字符串处理
     * @param $order_id
     * @param $goods_id
     * @return null|string
     */
    private function businessTypeOrderSpec($order_id, $goods_id)
    {
        $string = null;
        $spec   = Db::table('order_detail')->where(['order_id' => $order_id, 'goods_id' => $goods_id])->value('spec');
        if (!empty($spec)) {
            $spec = json_decode($spec, true);
            if (is_array($spec) && !empty($spec)) {
                foreach ($spec as $k => $v) {
                    if ($k > 0) {
                        $string .= ' | ';
                    }
                    $string .= $v['type'] . '：' . $v['value'];
                }
            }
        }
        return $string;
    }

    /**
     * 商品评论总数
     * @param array $where
     * @return int
     */
    public function goodsCommentsTotal($where = [])
    {
        return Db::table('goods_comments')->where($where)->count();
    }

    /**
     * 商品评论列表条件
     * @param array $params
     * @return array
     */
    public function goodsCommentsListWhere($params = [])
    {
        $where = [];

        // 用户id
        if (!empty($params['user'])) {
            $where[] = ['user_id', '=', $params['user']['id']];
        }

        // 关键字根据用户筛选,商品标题
        if (!empty($params['keywords'])) {
            if (empty($params['user'])) {
                $user_ids = Db::table('user')->where('username|nickname|mobile|email', '=',
                    $params['keywords'])->pluck('id');
                if (!empty($user_ids)) {
                    $where[] = ['user_id', 'in', $user_ids];
                } else {
                    // 无数据条件，走商品
                    $goods_ids = Db::table('goods')->where('title', 'like',
                        '%' . $params['keywords'] . '%')->pluck('id');
                    if (!empty($goods_ids)) {
                        $where[] = ['goods_id', 'in', $goods_ids];
                    } else {
                        $where[] = ['id', '=', 0];
                    }
                }
            }
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] == 1) {
            // 等值
            if (isset($params['is_show']) && $params['is_show'] > -1) {
                $where[] = ['is_show', '=', intval($params['is_show'])];
            }
            if (isset($params['is_anonymous']) && $params['is_anonymous'] > -1) {
                $where[] = ['is_anonymous', '=', intval($params['is_anonymous'])];
            }
            if (isset($params['is_reply']) && $params['is_reply'] > -1) {
                $where[] = ['is_reply', '=', intval($params['is_reply'])];
            }
            if (!empty($params['business_type'])) {
                $where[] = ['business_type', '=', $params['business_type']];
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
     * 评论保存
     * @param array $params
     * @return array
     */
    public function goodsCommentsSave($params = [])
    {
        // 参数校验
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '操作id有误',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'business_type',
                'checked_data' => array_keys(GoodsCommentsService::common_order_after_sale_refund_list),
                'error_msg'    => '请选择业务类型',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'content',
                'checked_data' => '6,230',
                'error_msg'    => '评论内容 6~230 个字符之间',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'reply',
                'checked_data' => '230',
                'error_msg'    => '回复内容最多 230 个字符',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'rating',
                'checked_data' => array_keys(GoodsCommentsService::common_goods_comments_rating_list),
                'error_msg'    => '请选择评分',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 开始操作
        $data = [
            'content'       => $params['content'],
            'reply'         => $params['reply'],
            'business_type' => $params['business_type'],
            'rating'        => intval($params['rating']),
            'reply_time'    => empty($params['reply_time']) ? 0 : strtotime($params['reply_time']),
            'is_reply'      => isset($params['is_reply']) ? intval($params['is_reply']) : 0,
            'is_show'       => isset($params['is_show']) ? intval($params['is_show']) : 0,
            'is_anonymous'  => isset($params['is_anonymous']) ? intval($params['is_anonymous']) : 0,
            'upd_time'      => time(),
        ];

        // 更新
        if (Db::table('goods_comments')->where(['id' => intval($params['id'])])->update($data)) {
            return dataReturn('编辑成功', 0);
        }
        return dataReturn('编辑失败或数据不存在', -100);
    }

    /**
     * @param array $params
     * @return array
     */
    public function goodsCommentsDelete($params = [])
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

        // 开始删除
        if (Db::table('goods_comments')->where(['id' => intval($params['id'])])->delete()) {
            return dataReturn('删除成功', 0);
        }
        return dataReturn('删除失败或数据不存在', -100);
    }

    /**
     * @param array $params
     * @return array
     */
    public function goodsCommentsReply($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '操作id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'reply',
                'error_msg'    => '回复内容不能为空',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'reply',
                'checked_data' => '1,230',
                'error_msg'    => '回复内容格式 1~230 个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 评论是否存在
        $comments_id = Db::table('goods_comments')->selectRaw('id')->find(intval($params['id']));
        if (empty($comments_id)) {
            return dataReturn('资源不存在或已被删除', -2);
        }
        // 更新问答
        $data = [
            'reply'      => $params['reply'],
            'is_reply'   => 1,
            'reply_time' => time(),
            'upd_time'   => time(),
        ];
        if (Db::table('goods_comments')->where(['id' => $comments_id])->update($data)) {
            return dataReturn('操作成功');
        }
        return dataReturn('操作失败', -100);
    }

    /**
     * @param array $params
     * @return array
     */
    public function goodsCommentsStatusUpdate($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '操作id有误',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'state',
                'checked_data' => [0, 1],
                'error_msg'    => '状态有误',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'field',
                'checked_data' => ['is_anonymous', 'is_show', 'is_reply'],
                'error_msg'    => '操作字段有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 数据更新
        $data = [
            $params['field'] => intval($params['state']),
            'upd_time'       => time(),
        ];
        if (Db::table('goods_comments')->where(['id' => intval($params['id'])])->update($data)) {
            return dataReturn('编辑成功');
        }
        return dataReturn('编辑失败或数据未改变', -100);
    }

    /**
     * 商品动态评分
     * @param $goods_id
     * @return array
     */
    public function goodsCommentsScore($goods_id)
    {
        // 默认
        $rating_list = [
            1 => ['rating' => 1, 'name' => '1分', 'count' => 0, 'portion' => 0],
            2 => ['rating' => 2, 'name' => '2分', 'count' => 0, 'portion' => 0],
            3 => ['rating' => 3, 'name' => '3分', 'count' => 0, 'portion' => 0],
            4 => ['rating' => 4, 'name' => '4分', 'count' => 0, 'portion' => 0],
            5 => ['rating' => 5, 'name' => '5分', 'count' => 0, 'portion' => 0],
        ];
        $where       = [
            ['goods_id', '=', $goods_id],
            ['rating', '>', 0],
        ];
        $data        = Db::table('goods_comments')->where($where)->groupBy('rating')->selectRaw('count(*) as count, rating',
            'rating')->get()->toArray();
        if (!empty($data)) {
            $sum = array_sum($data);
            foreach ($data as $rating => $count) {
                if ($rating > 0 && $rating <= 5) {
                    $rating_list[$rating]['count']   = $count;
                    $rating_list[$rating]['portion'] = round($count / $sum, 2) * 100;
                }
            }
        }

        sort($rating_list);
        $result = [
            'avg'    => PriceNumberFormat(Db::table('goods_comments')->where($where)->avg('rating'), 1),
            'rating' => $rating_list,
        ];
        return dataReturn('操作成功', 0, $result);
    }

}