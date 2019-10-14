<?php


namespace App\Service;

use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * 搜索服务层
 * @author colin.
 * date 19-7-2 下午4:53
 */
class SearchService
{
    /**
     * @var GoodsService
     */
    protected $goodService;

    public function __construct(GoodsService $goodsService)
    {
        $this->goodService = $goodsService;
    }

    /**
     * 根据分类id获取下级列表
     * @param   [array]          $params [输入参数]
     * @return array
     */
    public function goodsCategoryList($params = [])
    {
        return $this->goodService->goodsCategoryList(['pid' => $params['category_id']]);
    }

    /**
     * 获取商品价格筛选列表
     *
     * @param    [array]          $params [输入参数]
     * @return array
     */
    public function screeningPriceList($params = [])
    {
        $field = empty($params['field']) ? '*' : $params['field'];
        return Db::table('screening_price')->selectRaw($field)->where(['is_enable' => 1])->orderByRaw('sort asc')->get();
    }

    /**
     * 获取商品列表
     * @param array $params
     * @return array
     */
    public function goodsList($params = [])
    {
        $result = [
            'page_total' => 0,
            'total'      => 0,
            'data'       => [],
        ];
        $where  = [
            ['g.is_delete_time', '=', 0],
            ['g.is_shelves', '=', 1],
        ];

        // 关键字
        if (!empty($params['keywords'])) {
            $where[] = ['g.title|g.seo_title|g.seo_keywords|g.seo_keywords', 'like', '%' . $params['keywords'] . '%'];
        }

        // 品牌
        if (!empty($params['brand_id'])) {
            $where[] = ['g.brand_id', '=', intval($params['brand_id'])];
        }

        // 分类id
        if (!empty($params['category_id'])) {
            $category_ids   = $this->goodService->GoodsCategoryItemsIds([$params['category_id']], 1);
            $category_ids[] = $params['category_id'];
            $where[]        = ['gci.category_id', 'in', $category_ids];
        }

        // 筛选价格
        if (!empty($params['screening_price_id'])) {
            $price = Db::table('screening_price')->selectRaw('min_price,max_price')->where([
                'is_enable' => 1,
                'id'        => intval($params['screening_price_id']),
            ])->first();
            if (!empty($price)) {
                $params['min_price'] = $price['min_price'];
                $params['max_price'] = $price['max_price'];
            }
        }
        if (!empty($params['min_price'])) {
            $where[] = ['g.min_price', 'EGT', $params['min_price']];
        }
        if (!empty($params['max_price'])) {
            $where[] = ['g.min_price', 'LT', $params['max_price']];
        }

        // 获取商品总数
        $result['total'] = $this->goodService->CategoryGoodsTotal($where);

        // 获取商品列表
        if ($result['total'] > 0) {
            // 排序
            if (!empty($params['order_by_field']) && !empty($params['order_by_type']) && $params['order_by_field'] != 'default') {
                $order_by = 'g.' . $params['order_by_field'] . ' ' . $params['order_by_type'];
            } else {
                $order_by = 'g.access_count desc, g.sales_count desc';
            }
            $request = make(RequestInterface::class);
            // 分页计算
            $page                 = intval($request->input('page', 1));
            $n                    = 10;
            $m                    = intval(($page - 1) * $n);
            $goods                = $this->goodService->CategoryGoodsList([
                'where'    => $where,
                'm'        => $m,
                'n'        => $n,
                'order_by' => $order_by,
            ]);
            $result['data']       = $goods['data'];
            $result['page_total'] = ceil($result['total'] / $n);
        }
        return dataReturn('处理成功', 0, $result);
    }

    /**
     * [SearchAdd 搜索记录添加]
     * @param    [array]          $params [输入参数]
     */
    public function searchAdd($params = [])
    {
        // 筛选价格
        $screening_price = '';
        if (!empty($params['screening_price_id'])) {
            $price = Db::table('screening_price')->selectRaw('min_price,max_price')->where([
                'is_enable' => 1,
                'id'        => intval($params['screening_price_id']),
            ])->first();
        } else {
            $price = [
                'min_price' => !empty($params['min_price']) ? $params['min_price'] : 0,
                'max_price' => !empty($params['max_price']) ? $params['max_price'] : 0,
            ];
        }
        if (!empty($price)) {
            $screening_price = $price['min_price'] . '-' . $price['max_price'];
        }

        // 添加日志
        $data = [
            'user_id'         => isset($params['user_id']) ? intval($params['user_id']) : 0,
            'brand_id'        => isset($params['brand_id']) ? intval($params['brand_id']) : 0,
            'category_id'     => isset($params['category_id']) ? intval($params['category_id']) : 0,
            'keywords'        => empty($params['keywords']) ? '' : $params['keywords'],
            'order_by_field'  => empty($params['order_by_field']) ? '' : $params['order_by_field'],
            'order_by_type'   => empty($params['order_by_type']) ? '' : $params['order_by_type'],
            'screening_price' => $screening_price,
            'ymd'             => date('Ymd'),
            'add_time'        => time(),
        ];
        Db::table('search_history')->insert($data);
    }

    /**
     * [SearchKeywordsList 获取热门关键字列表
     * @return array
     */
    public function searchKeywordsList()
    {
        $where = [
            ['keywords', '<>', ''],
        ];
        return Db::table('search_history')->where($where)->groupBy('keywords')->limit(10)->pluck('keywords')->toArray();
    }

}