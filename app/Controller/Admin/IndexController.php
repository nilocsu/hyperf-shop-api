<?php


namespace App\Controller\Admin;


use App\Service\StatisticalService;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;

/**
 * @author colin.
 * date 19-7-2 下午3:00
 * @Controller(prefix="/admin")
 */
class IndexController
{
    /**
     * @var StatisticalService
     */
    protected $statisticalService;

    public function __construct(StatisticalService $statisticalService)
    {
        $this->statisticalService = $statisticalService;
    }

    /**
     * @GetMapping(path="index")
     * @return array
     */
    public function index()
    {

        $mysql_ver = Db::select('SELECT VERSION() AS `ver`');
        $environment      = [
            'server_ver' => php_sapi_name(),
            'php_ver'    => PHP_VERSION,
            'mysql_ver'  => isset($mysql_ver[0]->ver) ? $mysql_ver[0]->ver : '',
            'os_ver'     => PHP_OS,
        ];
        $user_num = $this->statisticalService->userYesterdayTodayTotal()['data'];

        // 订单总数
        $order_number = $this->statisticalService->orderNumberYesterdayTodayTotal()['data'];

        // 订单成交总量
        $order_complete_number = $this->statisticalService->orderCompleteYesterdayTodayTotal()['data'];

        // 订单收入总计
        $order_complete_money = $this->statisticalService->orderCompleteMoneyYesterdayTodayTotal()['data'];
        // 近7日订单交易走势
        $order_trading_trend = $this->statisticalService->orderTradingTrendSevenTodayTotal()['data'];

        // 近7日订单支付方式
        $order_type_number = $this->statisticalService->orderPayTypeSevenTodayTotal()['data'];
        // 近7日热销商品
        $goods_hot_sale = $this->statisticalService->goodsHotSaleSevenTodayTotal()['data'];
        return compact('environment', 'user_num', 'order_number', 'order_complete_money', 'order_complete_number',
            'order_trading_trend', 'order_type_number', 'goods_hot_sale');
    }
}