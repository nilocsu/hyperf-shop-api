<?php


namespace App\Service;


use Hyperf\DbConnection\Db;

class StatisticalService
{
    // 近3天,近7天,近15天,近30天
    private $nearly_three_days;
    private $nearly_seven_days;
    private $nearly_fifteen_days;
    private $nearly_thirty_days;

    // 近7天日期
    private $seven_time_start;
    private $seven_time_end;

    // 昨天日期
    private $yesterday_time_start;
    private $yesterday_time_end;

    // 今天日期
    private $today_time_start;
    private $today_time_end;

    public function __construct()
    {
        // 近7天日期
        $this->seven_time_start = strtotime(date('Y-m-d 00:00:00', strtotime('-7 day')));
        $this->seven_time_end   = time();

        // 昨天日期
        $this->yesterday_time_start = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
        $this->yesterday_time_end   = strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));

        // 今天日期
        $this->today_time_start = strtotime(date('Y-m-d 00:00:00'));
        $this->today_time_end   = time();

        // 近3天,近7天,近15天,近30天
        $nearly_all = [
            3  => 'nearly_three_days',
            7  => 'nearly_seven_days',
            15 => 'nearly_fifteen_days',
            30 => 'nearly_thirty_days',
        ];
        foreach ($nearly_all as $day => $name) {
            $date = [];
            $time = time();
            for ($i = 0; $i < $day; $i++) {
                $date[] = [
                    'start_time' => strtotime(date('Y-m-d 00:00:00', $time - $i * 3600 * 24)),
                    'end_time'   => strtotime(date('Y-m-d 23:59:59', $time - $i * 3600 * 24)),
                    'name'       => date('Y-m-d', $time - $i * 3600 * 24),
                ];
            }
            $this->{$name} = $date;
        }
    }

    /**
     *
     * 用户总数,今日,昨日,总数
     * @return array
     */
    public function userYesterdayTodayTotal()
    {

        // 总数
        $total_count = Db::table('user')->count();

        // 昨天
        $where           = [
            ['add_time', '>=', $this->yesterday_time_start],
            ['add_time', '<=', $this->yesterday_time_end],
        ];
        $yesterday_count = Db::table('user')->where($where)->count();

        // 今天
        $where       = [
            ['add_time', '>=', $this->today_time_start],
            ['add_time', '<=', $this->today_time_end],
        ];
        $today_count = Db::table('user')->where($where)->count();

        // 数据组装
        $result = [
            'total_count'     => $total_count,
            'yesterday_count' => $yesterday_count,
            'today_count'     => $today_count,
        ];
        return dataReturn('处理成功', 0, $result);
    }

    /**
     *
     * 订单总数,今日,昨日,总数
     * @return array
     */
    public function orderNumberYesterdayTodayTotal()
    {

        // 订单状态
        // （0待确认, 1已确认/待支付, 2已支付/待发货, 3已发货/待收货, 4已完成, 5已取消, 6已关闭）

        // 总数
        $where       = [
            ['status', '<=', 4],
        ];
        $total_count = Db::table('order')->where($where)->count();

        // 昨天
        $where           = [
            ['status', '<=', 4],
            ['add_time', '>=', $this->yesterday_time_start],
            ['add_time', '<=', $this->yesterday_time_end],
        ];
        $yesterday_count = Db::table('order')->where($where)->count();

        // 今天
        $where       = [
            ['status', '<=', 4],
            ['add_time', '>=', $this->today_time_start],
            ['add_time', '<=', $this->today_time_end],
        ];
        $today_count = Db::table('order')->where($where)->count();

        // 数据组装
        $result = [
            'total_count'     => $total_count,
            'yesterday_count' => $yesterday_count,
            'today_count'     => $today_count,
        ];
        return dataReturn('处理成功', 0, $result);
    }

    /**
     * 订单成交总量,今日,昨日,总数
     * @return array
     */
    public function orderCompleteYesterdayTodayTotal()
    {

        // 订单状态
        // （0待确认, 1已确认/待支付, 2已支付/待发货, 3已发货/待收货, 4已完成, 5已取消, 6已关闭）

        // 总数
        $where       = [
            ['status', '=', 4],
        ];
        $total_count = Db::table('order')->where($where)->count();

        // 昨天
        $where           = [
            ['status', '=', 4],
            ['add_time', '>=', $this->yesterday_time_start],
            ['add_time', '<=', $this->yesterday_time_end],
        ];
        $yesterday_count = Db::table('order')->where($where)->count();

        // 今天
        $where       = [
            ['status', '=', 4],
            ['add_time', '>=', $this->today_time_start],
            ['add_time', '<=', $this->today_time_end],
        ];
        $today_count = Db::table('order')->where($where)->count();

        // 数据组装
        $result = [
            'total_count'     => $total_count,
            'yesterday_count' => $yesterday_count,
            'today_count'     => $today_count,
        ];
        return dataReturn('处理成功', 0, $result);
    }

    /**
     *
     * 订单收入总计,今日,昨日,总数
     * @return array
     */
    public function orderCompleteMoneyYesterdayTodayTotal()
    {

        // 订单状态
        // （0待确认, 1已确认/待支付, 2已支付/待发货, 3已发货/待收货, 4已完成, 5已取消, 6已关闭）

        // 总数
        $where       = [
            ['status', '<=', 4],
        ];
        $total_count = Db::table('order')->where($where)->sum('total_price');

        // 昨天
        $where           = [
            ['status', '<=', 4],
            ['add_time', '>=', $this->yesterday_time_start],
            ['add_time', '<=', $this->yesterday_time_end],
        ];
        $yesterday_count = Db::table('order')->where($where)->sum('total_price');

        // 今天
        $where       = [
            ['status', '<=', 4],
            ['add_time', '>=', $this->today_time_start],
            ['add_time', '<=', $this->today_time_end],
        ];
        $today_count = Db::table('order')->where($where)->sum('total_price');

        // 数据组装
        $result = [
            'total_count'     => PriceNumberFormat($total_count),
            'yesterday_count' => PriceNumberFormat($yesterday_count),
            'today_count'     => PriceNumberFormat($today_count),
        ];
        return dataReturn('处理成功', 0, $result);
    }

    /**
     *
     * 订单交易趋势, 7天数据
     * @return array
     */
    public function orderTradingTrendSevenTodayTotal()
    {

        // 订单状态列表
        $order_status_list = [
            0 => ['id' => 0, 'name' => '待确认', 'checked' => true],
            1 => ['id' => 1, 'name' => '待付款'],
            2 => ['id' => 2, 'name' => '待发货'],
            3 => ['id' => 3, 'name' => '待收货'],
            4 => ['id' => 4, 'name' => '已完成'],
            5 => ['id' => 5, 'name' => '已取消'],
            6 => ['id' => 6, 'name' => '已关闭'],
        ];
        $status_arr        = array_column($order_status_list, 'id');

        // 循环获取统计数据
        $data      = [];
        $count_arr = [];
        $name_arr  = [];
        if (!empty($status_arr)) {
            foreach ($this->nearly_seven_days as $day) {
                // 当前日期名称
                $name_arr[] = $day['name'];

                // 根据支付名称获取数量
                foreach ($status_arr as $status) {
                    // 获取订单
                    $where                = [
                        ['status', '=', $status],
                        ['add_time', '>=', $day['start_time']],
                        ['add_time', '<=', $day['end_time']],
                    ];
                    $count_arr[$status][] = Db::table('order')->where($where)->count();
                }
            }
        }

        // 数据格式组装
        foreach ($status_arr as $status) {
            $data[] = [
                'name'  => $order_status_list[$status]['name'],
                'type'  => 'line',
                'tiled' => '总量',
                'data'  => empty($count_arr[$status]) ? [] : $count_arr[$status],
            ];
        }

        // 数据组装
        $result = [
            'title_arr' => array_column($order_status_list, 'name'),
            'name_arr'  => $name_arr,
            'data'      => $data,
        ];
        return dataReturn('处理成功', 0, $result);
    }

    /**
     *
     * 订单支付方式, 7天数据
     * @return array
     */
    public function orderPayTypeSevenTodayTotal()
    {

        // 获取支付方式名称
        $where        = [
            ['business_type', '=', 1],
        ];
        $pay_name_arr = Db::table('pay_log')->where($where)->groupBy('payment_name')->pluck('payment_name');


        // 循环获取统计数据
        $data      = [];
        $count_arr = [];
        $name_arr  = [];
        if (!empty($pay_name_arr)) {
            foreach ($this->nearly_seven_days as $day) {
                // 当前日期名称
                $name_arr[] = date('m-d', strtotime($day['name']));

                // 根据支付名称获取数量
                foreach ($pay_name_arr as $payment) {
                    // 获取订单
                    $where                 = [
                        ['payment_name', '=', $payment],
                        ['add_time', '>=', $day['start_time']],
                        ['add_time', '<=', $day['end_time']],
                    ];
                    $count_arr[$payment][] = Db::table('pay_log')->where($where)->count();
                }
            }
        }

        // 数据格式组装
        foreach ($pay_name_arr as $payment) {
            $data[] = [
                'name'      => $payment,
                'type'      => 'line',
                'stack'     => '总量',
                'areaStyle' => (object)[],
                'data'      => empty($count_arr[$payment]) ? [] : $count_arr[$payment],
            ];
        }

        // 数据组装
        $result = [
            'title_arr' => $pay_name_arr,
            'name_arr'  => $name_arr,
            'data'      => $data,
        ];
        return dataReturn('处理成功', 0, $result);
    }

    /**
     *
     * 热销商品, 7天数据
     * @return array
     */
    public function goodsHotSaleSevenTodayTotal()
    {
        // 获取订单id
        $where     = [
            ['status', '<=', 4],
            ['add_time', '>=', $this->seven_time_start],
            ['add_time', '<=', $this->seven_time_end],
        ];
        $order_ids = Db::table('order')->where($where)->pluck('id')->toArray();
        var_dump($order_ids);

        // 获取订单详情热销商品
        if (empty($order_ids)) {
            $data = [];
        } else {
            $data = Db::table('order_detail')->selectRaw('title AS name,sum(buy_number) AS value')->where('order_id', 'IN',
                $order_ids)->groupBy('goods_id')->orderByRaw('value desc')->limit(10)->select();
        }

        if (!empty($data)) {
            foreach ($data as &$v) {
                if (mb_strlen($v['name'], 'utf-8') > 12) {
                    $v['name'] = mb_substr($v['name'], 0, 12, 'utf-8') . '...';
                }
            }
        }

        // 数据组装
        $result = [
            'name_arr' => array_column($data, 'name'),
            'data'     => $data,
        ];
        return dataReturn('处理成功', 0, $result);
    }

}