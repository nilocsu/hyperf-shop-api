<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Service;

use Hyperf\DbConnection\Db;

class GoodsService
{
    /**
     * @var BrandService
     */
    private $brandService;

    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;
    }

    //end __construct()

    /**
     * 根据id获取一条商品分类.
     *
     * @param array $params
     * @return mixed
     */
    public function goodsCategoryRow($params = [])
    {
        if (empty($params['id'])) {
            return null;
        }

        $field = empty($params['field']) ? 'id,pid,icon,name,vice_name,describe,bg_color,big_images,sort,is_home_recommended' : $params['field'];
        $data  = $this->goodsCategoryDataDealWith([
            Db::table('goods_category')->selectRaw($field)->where([
                'is_enable' => 1,
                'id'        => (int)($params['id']),
            ])->first()->toArray(),
        ]);

        return empty($data[0]) ? null : $data[0];
    }

    //end goodsCategoryRow()

    /**
     * 获取所有分类.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function goodsCategoryAll($params = [])
    {
        // 从缓存获取
        // $key = config('shopxo.cache_goods_category_key');
        // $data = cache($key);
        // if(!empty($data))
        // {
        // return $data;
        // }
        // 获取分类
        $params['where'] = [
            'pid'       => 0,
            'is_enable' => 1,
        ];

        return $this->goodsCategory($params);
        // 存储缓存
        // cache($key, $data);
    }


    /**
     * 获取分类.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function goodsCategory($params = [])
    {
        // 获取分类
        $where = empty($params['where']) ? [
            'pid'       => 0,
            'is_enable' => 1,
        ] : $params['where'];
        $data  = $this->goodsCategoryList($where);
        if (!empty($data)) {
            foreach ($data as $v) {
                $where['pid'] = $v->id;
                $v->items     = $this->goodsCategoryList($where);
                if (!empty($v->items)) {
                    // 一次性查出所有二级下的三级、再做归类、避免sql连接超多
                    $where['pid'] = array_column($v['items'], 'id');
                    $items        = $this->goodsCategoryList($where);
                    if (!empty($items)) {
                        foreach ($v['items'] as $vs) {
                            foreach ($items as $vss) {
                                if ($vs['id'] === $vss['pid']) {
                                    $vs['items'][] = $vss;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }


    /**
     * 根据pid获取商品分类列表.
     *
     * @param array $where
     *
     * @return mixed
     */
    public function goodsCategoryList($where = [])
    {
        $where['is_enable'] = 1;
        $field              = 'id,pid,icon,name,vice_name,describe,bg_color,big_images,sort,is_home_recommended,seo_title,seo_keywords,seo_desc';
        $data               = Db::table('goods_category')->selectRaw($field)->where($where)->orderByRaw('sort asc')->get()->toArray();

        return $this->goodsCategoryDataDealWith($data);
    }

    //end goodsCategoryList()

    /**
     * 获取首页楼层数据.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function homeFloorList($params = [])
    {
        // 商品大分类
        $params['where'] = [
            'pid'                 => 0,
            'is_home_recommended' => 1,
            'is_enable'           => 1,
        ];
        $goods_category  = $this->goodsCategory($params);
        if (!empty($goods_category)) {
            foreach ($goods_category as $v) {
                $category_ids = $this->goodsCategoryItemsIds([$v['id']], 1);
                $goods        = $this->categorygoodsList([
                    'where' => [
                        'gci.category_id'     => $category_ids,
                        'is_home_recommended' => 1,
                    ],
                    'm'     => 0,
                    'n'     => 8,
                    'field' => 'g.id,g.title,g.title_color,g.images,g.home_recommended_images,g.original_price,g.price,g.min_price,g.max_price,g.inventory,g.buy_min_number,g.buy_max_number',
                ]);
                $v['goods']   = $goods['data'];
            }
        }

        return $goods_category;
    }

    //end homeFloorList()

    /**
     * 获取商品分类下的所有分类id.
     *
     * @param array $ids
     * @param null $is_enable
     *
     * @return array
     */
    public function goodsCategoryItemsIds($ids = [], $is_enable = null)
    {
        $where = ['pid' => $ids];
        if ($is_enable !== null) {
            $where['is_enable'] = $is_enable;
        }

        $data = Db::table('goods_category')->where($where)->pluck('id')->toArray();
        if (!empty($data)) {
            $temp = $this->goodsCategoryItemsIds($data, $is_enable);
            if (!empty($temp)) {
                $data = array_merge($data, $temp);
            }
        }

        return $data;
    }


    /**
     * 获取分类与商品关联总数.
     *
     * @param array $where
     *
     * @return int
     */
    public function categoryGoodsTotal($where = [])
    {
        return (int)Db::table('goods')->join('goods_category_join', 'goods.id', '=',
            'goods_category_join.goods_id')->where($where)->count('DISTINCT goods.id');
    }


    /**
     * 获取分类与商品关联列表.
     *
     * @param array $params
     *
     * @return array
     */
    public function categoryGoodsList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $field    = empty($params['field']) ? 'g.*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'g.id desc' : trim($params['order_by']);

        $m    = isset($params['m']) ? (int)($params['m']) : 0;
        $n    = isset($params['n']) ? (int)($params['n']) : 10;
        $data = Db::table('goods')->join('goods_category_join',
            'goods.id=goods_category_join.goods_id')->selectRaw($field)->where($where)->groupBy('goods.id')->orderByRaw($order_by)->limit($n)->offset($n * $m)->get()->toArray();

        return $this->goodsDataHandle($params, $data);
    }

    /**
     * 商品数据处理.
     *
     * @param $params
     * @param $data
     *
     * @return array
     */
    public function goodsDataHandle($params, $data)
    {
        if (!empty($data)) {
            // 其它额外处理
            $is_photo       = (isset($params['is_photo']) && $params['is_photo'] === true) ? true : false;
            $is_spec        = (isset($params['is_spec']) && $params['is_spec'] === true) ? true : false;
            $is_content_app = (isset($params['is_content_app']) && $params['is_content_app'] === true) ? true : false;
            $is_category    = (isset($params['is_category']) && $params['is_category'] === true) ? true : false;

            // 开始处理数据
            foreach ($data as &$v) {
                // todo 商品处理前


                // 商品封面图片
                if (isset($v['images'])) {
                    $v['images_old'] = $v['images'];
                    $v['images']     = ResourcesService::AttachmentPathViewHandle($v['images']);
                }

                // 视频
                if (isset($v['video'])) {
                    $v['video_old'] = $v['video'];
                    $v['video']     = ResourcesService::AttachmentPathViewHandle($v['video']);
                }

                // 商品首页推荐图片，不存在则使用商品封面图片
                if (isset($v['home_recommended_images'])) {
                    if (empty($v['home_recommended_images'])) {
                        if (isset($v['images'])) {
                            $v['home_recommended_images'] = $v['images'];
                        } else {
                            if (!empty($v['id'])) {
                                $images                       = Db::table('goods')->where(['id' => $v['id']])->value('images');
                                $v['home_recommended_images'] = ResourcesService::AttachmentPathViewHandle($images);
                            }
                        }
                    } else {
                        $v['home_recommended_images_old'] = $v['home_recommended_images'];
                        $v['home_recommended_images']     = ResourcesService::AttachmentPathViewHandle($v['home_recommended_images']);
                    }
                }

                // PC内容处理
                if (isset($v['content_web'])) {
                    $v['content_web'] = ResourcesService::contentStaticReplace($v['content_web'], 'get');
                }

                // 产地
                if (isset($v['place_origin'])) {
                    $v['place_origin_name'] = empty($v['place_origin']) ? null : RegionService::RegionName($v['place_origin']);
                }

                // 品牌
                if (isset($v['brand_id'])) {
                    $v['brand_name'] = empty($v['brand_id']) ? null : $this->brandService->brandName($v->brand_id);
                }

                // 时间
                if (!empty($v['add_time'])) {
                    $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                }

                if (!empty($v['upd_time'])) {
                    $v['upd_time'] = date('Y-m-d H:i:s', $v['upd_time']);
                }

                // 是否需要分类名称
                if ($is_category && !empty($v['id'])) {
                    $v['category_ids']  = Db::table('goods_category_join')->where(['goods_id' => $v['id']])->pluck('category_id');
                    $category_name      = Db::table('goods_category')->where(['id' => $v['category_ids']])->pluck('name')->toArray();
                    $v['category_text'] = implode(',', $category_name);
                }

                // 获取相册
                if ($is_photo && !empty($v['id'])) {
                    $v['photo'] = Db::table('goods_photo')->where([
                        'goods_id' => $v['id'],
                        'is_show'  => 1,
                    ])->orderByRaw('sort asc')->get()->toArray();
                    if (!empty($v['photo'])) {
                        foreach ($v['photo'] as $vs) {
                            $vs['images_old'] = $vs['images'];
                            $vs['images']     = ResourcesService::AttachmentPathViewHandle($vs['images']);
                        }
                    }
                }

                // 获取规格
                if ($is_spec && !empty($v['id'])) {
                    $v['specifications'] = $this->goodsSpecifications(['goods_id' => $v['id']]);
                }

                // 获取app内容
                if ($is_content_app && !empty($v['id'])) {
                    $v['content_app'] = $this->goodsContentApp(['goods_id' => $v['id']]);
                }

                // 展示字段
                $v['show_field_original_price_text'] = '原价';
                $v['show_field_price_text']          = '销售价';
            }
        }
        return dataReturn('处理成功', 0, $data);
    }


    /**
     * 获取商品手机详情.
     *
     * @param array $params
     *
     * @return \Hyperf\Utils\Collection
     */
    public function goodsContentApp($params = [])
    {
        $data = Db::table('goods_content_app')->where(['goods_id' => $params['goods_id']])->selectRaw('id,images,content')->orderByRaw('sort asc')->get()->toArray();
        if (!empty($data)) {
            foreach ($data as $v) {
                $v['images_old']  = $v['images'];
                $v['images']      = ResourcesService::AttachmentPathViewHandle($v['images']);
                $v['content_old'] = $v['content'];
                $v['content']     = empty($v['content']) ? null : explode("\n", $v['content']);
            }
        }

        return $data;
    }

    /**
     * 获取商品属性.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsSpecifications($params = [])
    {
        // 条件
        $where = ['goods_id' => $params['goods_id']];

        // 规格类型
        $choose = Db::table('goods_spec_type')->where($where)->orderByRaw('id asc')->get()->toArray();
        if (!empty($choose)) {
            // 数据处理
            foreach ($choose as &$temp_type) {
                $temp_type_value = json_decode($temp_type['value'], true);
                foreach ($temp_type_value as $vs) {
                    $vs['images'] = ResourcesService::AttachmentPathViewHandle($vs['images']);
                }

                $temp_type['value']    = $temp_type_value;
                $temp_type['add_time'] = date('Y-m-d H:i:s');
            }

            // 只有一个规格的时候直接获取规格值的库存数
            if (\count($choose) === 1) {
                foreach ($choose[0]['value'] as &$temp_spec) {
                    $temp_spec_params = [
                        'id'   => $params['goods_id'],
                        'spec' => [
                            [
                                'type'  => $choose[0]['name'],
                                'value' => $temp_spec['name'],
                            ],
                        ],
                    ];
                    $temp             = $this->goodsSpecDetail($temp_spec_params);
                    if ($temp['code'] === 0) {
                        $temp_spec['is_only_level_one'] = 1;
                        $temp_spec['inventory']         = $temp['data']['inventory'];
                    }
                }
            }
        }//end if

        return ['choose' => $choose];
    }

    /**
     * 商品收藏.
     *
     * @param array $params
     *
     * @return array|bool|string
     */
    public function goodsFavor($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '商品id有误',
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

        // 查询用户状态是否正常
        $user = make(UserService::class);
        $ret  = $user->userStatusCheck('id', $params['user']['id']);
        if ($ret['code'] !== 0) {
            return $ret;
        }

        // 开始操作
        $data = [
            'goods_id' => (int)($params['id']),
            'user_id'  => $params['user']['id'],
        ];
        $temp = Db::table('goods_favor')->where($data)->first()->toArray();
        if (empty($temp)) {
            // 添加收藏
            $data['add_time'] = time();
            if (Db::table('goods_favor')->insertGetId($data) > 0) {
                return dataReturn(
                    '收藏成功',
                    0,
                    [
                        'text'   => '已收藏',
                        'status' => 1,
                        'count'  => $this->goodsFavorTotal(['goods_id' => $data['goods_id']]),
                    ]
                );
            }

            return dataReturn('收藏失败');
        }

        // 是否强制收藏
        if (isset($params['is_mandatory_favor']) && $params['is_mandatory_favor'] === 1) {
            return dataReturn(
                '收藏成功',
                0,
                [
                    'text'   => '已收藏',
                    'status' => 1,
                    'count'  => $this->goodsFavorTotal(['goods_id' => $data['goods_id']]),
                ]
            );
        }

        // 删除收藏
        if (Db::table('goods_favor')->where($data)->delete() > 0) {
            return dataReturn(
                '取消成功',
                0,
                [
                    'text'   => '收藏',
                    'status' => 0,
                    'count'  => $this->goodsFavorTotal(['goods_id' => $data['goods_id']]),
                ]
            );
        }

        return dataReturn('取消失败');
    }

    /**
     * 用户是否收藏了商品
     *
     * @param array $params
     *
     * @return array
     */
    public function isUserGoodsFavor($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'goods_id',
                'error_msg'    => '商品id有误',
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

        $data = [
            'goods_id' => (int)($params['goods_id']),
            'user_id'  => $params['user']['id'],
        ];
        $temp = Db::table('goods_favor')->where($data)->first()->toArray();

        return dataReturn('操作成功', 0, empty($temp) ? 0 : 1);
    }


    /**
     * 前端商品收藏列表条件.
     *
     * @param array $params
     *
     * @return array
     */
    public function userGoodsFavorListWhere($params = [])
    {
        $where = [
            [
                'g.is_delete_time',
                '=',
                0,
            ],
        ];

        // 用户id
        if (!empty($params['user'])) {
            $where[] = [
                'f.user_id',
                '=',
                $params['user']['id'],
            ];
        }

        if (!empty($params['keywords'])) {
            $where[] = [
                'g.title|g.seo_title|g.seo_keywords|g.seo_keywords',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        return $where;
    }


    /**
     * 商品收藏总数.
     *
     * @param array $where [条件]
     *
     * @return int
     */
    public function goodsFavorTotal($where = [])
    {
        return Db::table('goods_favor')->join('goods', 'goods.id', '=', 'goods_favor.goods_id')->where($where)->count();
    }

    //end goodsFavorTotal()

    /**
     * 商品收藏列表.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsFavorList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $m        = isset($params['m']) ? (int)($params['m']) : 0;
        $n        = isset($params['n']) ? (int)($params['n']) : 10;
        $order_by = empty($params['order_by']) ? 'f.id desc' : $params['order_by'];
        $field    = 'f.*, g.title, g.original_price, g.price, g.min_price, g.images';

        // 获取数据
        $data = Db::table('goods_favor')->join('goods', 'goods.id', '=',
            'goods_favor.goods_id')->selectRaw($field)->where($where)->limit($n)->offset($m * $n)->orderByRaw($order_by)->get()->toArray();
        if (!empty($data)) {
            foreach ($data as $v) {
                // 图片
                $v['images_old'] = $v['images'];
                $v['images']     = ResourcesService::AttachmentPathViewHandle($v['images']);

            }
        }

        return dataReturn('处理成功', 0, $data);
    }

    //end goodsFavorList()

    /**
     * 商品访问统计加1.
     *
     * @param array $params
     *
     * @return bool
     */
    public function goodsAccessCountInc($params = [])
    {
        if (!empty($params['goods_id'])) {
            return Db::table('goods')->where(['id' => (int)($params['goods_id'])])->increment('access_count');
        }

        return false;
    }

    /**
     * 商品浏览保存.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsBrowseSave($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'goods_id',
                'error_msg'    => '商品id有误',
            ],
            [
                'checked_type' => 'is_array',
                'key_name'     => 'user',
                'error_msg'    => '用户信息有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        $where = [
            'goods_id' => (int)($params['goods_id']),
            'user_id'  => $params['user']['id'],
        ];
        $temp  = Db::table('goods_browse')->where($where)->first()->toArray();

        $data = [
            'goods_id' => (int)($params['goods_id']),
            'user_id'  => $params['user']['id'],
            'upd_time' => time(),
        ];
        if (empty($temp)) {
            $data['add_time'] = time();
            $status           = Db::table('goods_browse')->insertGetId($data) > 0;
        } else {
            $status = Db::table('goods_browse')->where($where)->update($data) !== false;
        }

        if ($status) {
            return dataReturn('处理成功', 0);
        }

        return dataReturn('处理失败', -100);
    }


    /**
     * 前端商品浏览列表条件.
     *
     * @param array $params
     *
     * @return array
     */
    public function userGoodsBrowseListWhere($params = [])
    {
        $where = [
            [
                'goods.is_delete_time',
                '=',
                0,
            ],
        ];

        // 用户id
        if (!empty($params['user'])) {
            $where[] = [
                'goods_browse.user_id',
                '=',
                $params['user']['id'],
            ];
        }

        if (!empty($params['keywords'])) {
            $where[] = [
                'goods.title|goods.seo_title|goods.seo_keywords|goods.seo_keywords',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        return $where;
    }


    /**
     * 商品浏览总数.
     *
     * @param array $where
     *
     * @return int
     */
    public function goodsBrowseTotal($where = [])
    {
        return Db::table('goods_browse')->join('goods', 'goods.id', '=',
            'goods_browse.goods_id')->where($where)->count();
    }

    /**
     * 商品浏览列表.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsBrowseList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $m        = isset($params['m']) ? (int)($params['m']) : 0;
        $n        = isset($params['n']) ? (int)($params['n']) : 10;
        $order_by = empty($params['order_by']) ? 'b.id desc' : $params['order_by'];
        $field    = 'b.*, g.title, g.original_price, g.price, g.min_price, g.images';

        // 获取数据
        $data = Db::table('goods_browse')->join('goods', 'goods.id', '=',
            'goods_browse.goods_id')->selectRaw($field)->where($where)->limit($n)->offset($m * $n)->orderByRaw($order_by)->get()->toArray();
        if (!empty($data)) {
            foreach ($data as $v) {
                $v['images_old'] = $v['images'];
                $v['images']     = ResourcesService::AttachmentPathViewHandle($v['images']);
            }
        }

        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 商品浏览删除.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsBrowseDelete($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '删除数据id有误',
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

        // 删除
        $where = [
            'id'      => explode(',', $params['id']),
            'user_id' => $params['user']['id'],
        ];
        if (Db::table('goods_browse')->where($where)->delete()) {
            return dataReturn('删除成功', 0);
        }

        return dataReturn('删除失败或资源不存在', -100);
    }


    /**
     * 获取商品总数.
     *
     * @param array $where
     *
     * @return int
     */
    public function goodsTotal($where = [])
    {
        return (int)Db::table('goods')->where($where)->count();
    }


    /**
     * 获取商品列表.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $field    = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id desc' : trim($params['order_by']);

        $m    = isset($params['m']) ? (int)($params['m']) : 0;
        $n    = isset($params['n']) ? (int)($params['n']) : 10;
        $data = Db::table('goods')->selectRaw($field)->where($where)->orderByRaw($order_by)->limit($n)->offset($n * $m)->get()->toArray();

        return $this->goodsDataHandle($params, $data);
    }


    /**
     * 后台管理商品列表条件.
     *
     * @param array $params
     *
     * @return array
     */
    public function getAdminIndexWhere($params = [])
    {
        $where = [
            [
                'is_delete_time',
                '=',
                0,
            ],
        ];

        // 模糊
        if (!empty($params['keywords'])) {
            $where[] = [
                'title|model|seo_title|seo_keywords|seo_keywords',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] === 1) {
            // 等值
            if (isset($params['is_shelves']) && $params['is_shelves'] > -1) {
                $where[] = [
                    'is_shelves',
                    '=',
                    (int)($params['is_shelves']),
                ];
            }

            if (isset($params['is_home_recommended']) && $params['is_home_recommended'] > -1) {
                $where[] = [
                    'is_home_recommended',
                    '=',
                    (int)($params['is_home_recommended']),
                ];
            }

            // 时间
            if (!empty($params['time_start'])) {
                $where[] = [
                    'add_time',
                    '>',
                    strtotime($params['time_start']),
                ];
            }

            if (!empty($params['time_end'])) {
                $where[] = [
                    'add_time',
                    '<',
                    strtotime($params['time_end']),
                ];
            }
        }//end if

        return $where;
    }

    /**
     * 商品保存.
     *
     * @param array $params
     *
     * @return array|bool|string
     */
    public function goodsSave($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'length',
                'key_name'     => 'title',
                'checked_data' => '2,60',
                'error_msg'    => '标题名称格式 2~60 个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'simple_desc',
                'checked_data' => '60',
                'is_checked'   => 1,
                'error_msg'    => '商品简述格式 最多60个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'model',
                'checked_data' => '30',
                'is_checked'   => 1,
                'error_msg'    => '商品型号格式 最多30个字符',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'category_id',
                'error_msg'    => '请至少选择一个商品分类',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'inventory_unit',
                'checked_data' => '1,6',
                'error_msg'    => '库存单位格式 1~6 个字符',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'buy_min_number',
                'error_msg'    => '请填写有效的最低起购数量',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'seo_title',
                'checked_data' => '100',
                'is_checked'   => 1,
                'error_msg'    => 'SEO标题格式 最多100个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'seo_keywords',
                'checked_data' => '130',
                'is_checked'   => 1,
                'error_msg'    => 'SEO关键字格式 最多130个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'seo_desc',
                'checked_data' => '230',
                'is_checked'   => 1,
                'error_msg'    => 'SEO描述格式 最多230个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 规格
        $specifications = $this->GetFormgoodsSpecificationsParams($params);
        if ($specifications['code'] !== 0) {
            return $specifications;
        }

        // 相册
        $photo = $this->GetFormgoodsPhotoParams($params);
        if ($photo['code'] !== 0) {
            return $photo;
        }

        // 手机端详情
        $content_app = $this->GetFormgoodsContentAppParams($params);
        if ($content_app['code'] !== 0) {
            return $content_app;
        }

        // 其它附件
        $data_fields = [
            'home_recommended_images',
            'video',
        ];
        $attachment  = ResourcesService::AttachmentParams($params, $data_fields);
        if ($attachment['code'] !== 0) {
            return $attachment;
        }

        // 编辑器内容
        $content_web = empty($params['content_web']) ? '' : ResourcesService::contentStaticReplace(htmlspecialchars_decode($params['content_web']),
            'add');

        // 基础数据
        $data = [
            'title'                   => $params['title'],
            'title_color'             => empty($params['title_color']) ? '' : $params['title_color'],
            'simple_desc'             => $params['simple_desc'],
            'model'                   => $params['model'],
            'place_origin'            => isset($params['place_origin']) ? (int)($params['place_origin']) : 0,
            'inventory_unit'          => $params['inventory_unit'],
            'give_integral'           => (int)($params['give_integral']),
            'buy_min_number'          => max(1,
                isset($params['buy_min_number']) ? (int)($params['buy_min_number']) : 1),
            'buy_max_number'          => isset($params['buy_max_number']) ? (int)($params['buy_max_number']) : 0,
            'is_deduction_inventory'  => isset($params['is_deduction_inventory']) ? (int)($params['is_deduction_inventory']) : 0,
            'is_shelves'              => isset($params['is_shelves']) ? (int)($params['is_shelves']) : 0,
            'content_web'             => $content_web,
            'images'                  => isset($photo['data'][0]) ? $photo['data'][0] : '',
            'photo_count'             => \count($photo['data']),
            'is_home_recommended'     => isset($params['is_home_recommended']) ? (int)($params['is_home_recommended']) : 0,
            'home_recommended_images' => $attachment['data']['home_recommended_images'],
            'brand_id'                => isset($params['brand_id']) ? (int)($params['brand_id']) : 0,
            'video'                   => $attachment['data']['video'],
            'seo_title'               => empty($params['seo_title']) ? '' : $params['seo_title'],
            'seo_keywords'            => empty($params['seo_keywords']) ? '' : $params['seo_keywords'],
            'seo_desc'                => empty($params['seo_desc']) ? '' : $params['seo_desc'],
        ];

        // 启动事务
        Db::beginTransaction();

        // 添加/编辑
        if (empty($params['id'])) {
            $data['add_time'] = time();
            $goods_id         = Db::table('goods')->insertGetId($data);
        } else {
            // $goods            = Db::table('goods')->first($params['id']);
            $data['upd_time'] = time();
            if (Db::table('goods')->where(['id' => (int)($params['id'])])->update($data)) {
                $goods_id = $params['id'];
            }
        }

        // 是否成功
        if (isset($goods_id) && $goods_id > 0) {
            // 分类
            $ret = $this->goodsCategoryInsert(explode(',', $params['category_id']), $goods_id);
            if ($ret['code'] !== 0) {
                // 回滚事务
                Db::rollback();

                return $ret;
            }

            // 规格
            $ret = $this->goodsSpecificationsInsert($specifications['data'], $goods_id);
            if ($ret['code'] !== 0) {
                // 回滚事务
                Db::rollback();

                return $ret;
            }

            // 更新商品基础信息
            $ret = $this->goodsSaveBaseUpdate($goods_id);
            if ($ret['code'] !== 0) {
                // 回滚事务
                Db::rollback();

                return $ret;
            }

            // 相册
            $ret = $this->goodsPhotoInsert($photo['data'], $goods_id);
            if ($ret['code'] !== 0) {
                // 回滚事务
                Db::rollback();

                return $ret;
            }

            // 手机详情
            $ret = $this->goodsContentAppInsert($content_app['data'], $goods_id);
            if ($ret['code'] !== 0) {
                // 回滚事务
                Db::rollback();

                return $ret;
            }

            // 提交事务
            Db::commit();

            return dataReturn('操作成功', 0);
        }//end if

        // 回滚事务
        Db::rollback();

        return dataReturn('操作失败', -100);
    }

    /**
     * 商品删除.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsDelete($params = [])
    {
        // 参数是否有误
        if (empty($params['id'])) {
            return dataReturn('商品id有误', -1);
        }

        // 开启事务
        Db::beginTransaction();

        // 删除商品
        if (Db::table('goods')->delete((int)($params['id']))) {
            // 商品规格
            if (Db::table('goods_spec_type')->where(['goods_id' => (int)($params['id'])])->delete() === 0) {
                Db::rollback();

                return dataReturn('规格类型删除失败', -100);
            }

            if (Db::table('goods_spec_value')->where(['goods_id' => (int)($params['id'])])->delete() === 0) {
                Db::rollback();

                return dataReturn('规格值删除失败', -100);
            }

            if (Db::table('goods_spec_base')->where(['goods_id' => (int)($params['id'])])->delete() === 0) {
                Db::rollback();

                return dataReturn('规格基础删除失败', -100);
            }

            // 相册
            if (Db::table('goods_photo')->where(['goods_id' => (int)($params['id'])])->delete() === 0) {
                Db::rollback();

                return dataReturn('相册删除失败', -100);
            }

            // app内容
            if (Db::table('goods_content_app')->where(['goods_id' => (int)($params['id'])])->delete() === 0) {
                Db::rollback();

                return dataReturn('相册删除失败', -100);
            }

            // 提交事务
            Db::commit();

            return dataReturn('删除成功', 0);
        }//end if

        Db::rollback();

        return dataReturn('删除失败', -100);
    }

    /**
     * 商品状态更新.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsStatusUpdate($params = [])
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
                'key_name'     => 'field',
                'error_msg'    => '未指定操作字段',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'state',
                'checked_data' => [
                    0,
                    1,
                ],
                'error_msg'    => '状态有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 数据更新
        if (Db::table('goods')->where(['id' => (int)($params['id'])])->update([
            $params['field'] => (int)($params['state']),
            'upd_time'       => time(),
        ])) {
            return dataReturn('操作成功');
        }

        return dataReturn('操作失败', -100);
    }

    /**
     * 获取商品编辑规格
     *
     * @param $goods_id
     *
     * @return array
     */
    public function goodsEditSpecifications($goods_id)
    {
        $where = ['goods_id' => $goods_id];

        // 获取规格类型
        $type  = Db::table('goods_spec_type')->where($where)->orderByRaw('id asc')->selectRaw('id,name,value')->get()->toArray();
        $value = [];
        if (!empty($type)) {
            // 数据处理
            foreach ($type as &$temp_type) {
                $temp_type_value = json_decode($temp_type['value'], true);
                foreach ($temp_type_value as $vs) {
                    $vs['images_old'] = $vs['images'];
                    $vs['images']     = ResourcesService::AttachmentPathViewHandle($vs['images']);
                }

                $temp_type['value'] = $temp_type_value;
            }

            // 获取规格值
            $temp_value = Db::table('goods_spec_value')->where($where)->selectRaw('goods_spec_base_id,value')->orderByRaw('id asc')->get()->toArray();
            if (!empty($temp_value)) {
                foreach ($temp_value as $value_v) {
                    $key = '';
                    foreach ($type as $type_v) {
                        foreach ($type_v['value'] as $type_vs) {
                            if ($type_vs['name'] === $value_v['value']) {
                                $key = $type_v['id'];

                                break;
                            }
                        }
                    }

                    $value[$value_v['goods_spec_base_id']][] = [
                        'data_type' => 'spec',
                        'data'      => [
                            'key'   => $key,
                            'value' => $value_v['value'],
                        ],
                    ];
                }
            }//end if

            if (!empty($value)) {
                foreach ($value as $k => $v) {
                    $base = Db::table('goods_spec_base')->find($k);
                    // $base['weight'] = PriceBeautify($base['weight']);
                    $v[] = [
                        'data_type' => 'base',
                        'data'      => $base,
                    ];
                }
            }
        } else {
            $base = Db::table('goods_spec_base')->where($where)->first()->toArray();
            // $base['weight'] = PriceBeautify($base['weight']);
            $value[][] = [
                'data_type' => 'base',
                'data'      => $base,
            ];
        }//end if

        return [
            'type'  => $type,
            'value' => array_values($value),
        ];
    }

    /**
     * 商品规格信息
     * @param array $params
     * @return array
     */
    public function goodsSpecDetail($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '商品id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'spec',
                'is_checked'   => 1,
                'error_msg'    => '请选择规格',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 条件
        $where    = [
            'goods_id' => (int)($params['id']),
        ];

        // 有规格值
        if (!empty($params['spec'])) {
            $value = [];
            // 规格不为数组则为json字符串
            if (!\is_array($params['spec'])) {
                $params['spec'] = json_decode(htmlspecialchars_decode($params['spec']), true);
            }

            foreach ($params['spec'] as $v) {
                $value[] = $v['value'];
            }

            $where['value'] = $value;

            // 获取规格值基础值id
            $ids = Db::table('goods_spec_value')->where($where)->pluck('goods_spec_base_id')->toArray();
            if (!empty($ids)) {
                // 根据基础值id获取规格值列表
                $temp_data = Db::table('goods_spec_value')->where(['goods_spec_base_id' => $ids])->selectRaw('goods_spec_base_id,value')->get()->toArray();
                if (!empty($temp_data)) {
                    // 根据基础值id分组
                    $data = [];
                    foreach ($temp_data as $v) {
                        $data[$v['goods_spec_base_id']][] = $v;
                    }

                    // 从条件中匹配对应的规格值得到最终的基础值id
                    $base_id  = 0;
                    $spec_str = implode('', array_column($params['spec'], 'value'));
                    foreach ($data as $value_v) {
                        $temp_str = implode('', array_column($value_v, 'value'));
                        if ($temp_str === $spec_str) {
                            $base_id = $value_v[0]['goods_spec_base_id'];

                            break;
                        }
                    }

                    // 获取基础值数据
                    if (!empty($base_id)) {
                        $base = Db::table('goods_spec_base')->find($base_id)->toArray();
                    }
                }//end if
            }//end if
        } else {
            $base = Db::table('goods_spec_base')->where($where)->first()->toArray();
        }//end if

        // 是否有规格
        if (!empty($base)) {
            // 单位 .00 处理
            // $base['weight'] = PriceBeautify($base['weight']);
            // todo 商品处理前

            // 返回成功
            return dataReturn('操作成功', 0, $base);
        }//end if

        return dataReturn('没有相关规格', -100);
    }

    /**
     * 商品规格类型.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsSpecType($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '商品id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'spec',
                'error_msg'    => '请选择规格',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 条件
        $where    = [
            'goods_id' => (int)($params['id']),
        ];
        $value    = [];

        // 规格不为数组则为json字符串
        if (!\is_array($params['spec'])) {
            $params['spec'] = json_decode(htmlspecialchars_decode($params['spec']), true);
        }

        foreach ($params['spec'] as $v) {
            $value[] = $v['value'];
        }

        $where['value'] = $value;

        // 获取规格值基础值id
        $ids = Db::table('goods_spec_value')->where($where)->pluck('goods_spec_base_id');
        if (!empty($ids)) {
            // 根据基础值id获取规格值列表
            $temp_data = Db::table('goods_spec_value')->where(['goods_spec_base_id' => $ids])->selectRaw('goods_spec_base_id,value')->get()->toArray();
            if (!empty($temp_data)) {
                // 根据基础值id分组
                $data = [];
                foreach ($temp_data as $v) {
                    $data[$v['goods_spec_base_id']][] = $v;
                }

                // 获取当前操作元素索引
//                $last     = end($params['spec']);
                $index    = (\count($params['spec']) - 1);
                $spec_str = implode('', array_column($params['spec'], 'value'));
                $value    = [];
                foreach ($data as $v) {
                    $temp_str = implode('', array_column($v, 'value'));
                    if (isset($v[($index + 1)]) && stripos($temp_str, $spec_str) !== false) {
                        // 判断是否还有库存
                        $inventory = Db::table('goods_spec_base')->where(['id' => $v[($index + 1)]['goods_spec_base_id']])->value('inventory');
                        if ($inventory > 0) {
                            $value[$v[($index + 1)]['value']] = $v[($index + 1)]['value'];
                        }
                    }
                }

                return dataReturn('操作成功', 0, array_values($value));
            }//end if
        }//end if

        return dataReturn('没有相关规格类型', -100);
    }

    //end goodsSpecType()

    /**
     * 获取商品分类节点数据.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsCategoryNodeSon($params = [])
    {
        // id
        $id = isset($params['id']) ? (int)($params['id']) : 0;

        // 获取数据
        $field = 'id,pid,icon,name,sort,is_enable,bg_color,big_images,vice_name,describe,is_home_recommended,seo_title,seo_keywords,seo_desc';
        $data  = Db::table('goods_category')->selectRaw($field)->where(['pid' => $id])->orderByRaw('sort asc')->get()->toArray();
        if (!empty($data)) {
            foreach ($data as $v) {
                $v['is_son']         = (Db::table('goods_category')->where(['pid' => $v['id']])->count() > 0) ? 'ok' : 'no';
//                $v['ajax_url']       = MyUrl('admin/goodsCategory/getnodeson', ['id' => $v['id']]);
//                $v['delete_url']     = MyUrl('admin/goodsCategory/delete');
                $v['icon_url']       = ResourcesService::AttachmentPathViewHandle($v['icon']);
                $v['big_images_url'] = ResourcesService::AttachmentPathViewHandle($v['big_images']);
                $v['json']           = json_encode($v);
            }

            return dataReturn('操作成功', 0, $data);
        }

        return dataReturn('没有相关数据', -100);
    }

    /**
     * 商品分类保存.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsCategorySave($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'length',
                'key_name'     => 'name',
                'checked_data' => '2,16',
                'error_msg'    => '名称格式 2~16 个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'vice_name',
                'checked_data' => '60',
                'is_checked'   => 1,
                'error_msg'    => '副名称格式 最多30个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'describe',
                'checked_data' => '200',
                'is_checked'   => 1,
                'error_msg'    => '描述格式 最多200个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'seo_title',
                'checked_data' => '100',
                'is_checked'   => 1,
                'error_msg'    => 'SEO标题格式 最多100个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'seo_keywords',
                'checked_data' => '130',
                'is_checked'   => 1,
                'error_msg'    => 'SEO关键字格式 最多130个字符',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'seo_desc',
                'checked_data' => '230',
                'is_checked'   => 1,
                'error_msg'    => 'SEO描述格式 最多230个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 其它附件
        $data_fields = [
            'icon',
            'big_images',
        ];
        $attachment  = ResourcesService::AttachmentParams($params, $data_fields);
        if ($attachment['code'] !== 0) {
            return $attachment;
        }

        // 数据
        $data = [
            'name'                => $params['name'],
            'pid'                 => isset($params['pid']) ? (int)($params['pid']) : 0,
            'vice_name'           => isset($params['vice_name']) ? $params['vice_name'] : '',
            'describe'            => isset($params['describe']) ? $params['describe'] : '',
            'bg_color'            => isset($params['bg_color']) ? $params['bg_color'] : '',
            'is_home_recommended' => isset($params['is_home_recommended']) ? (int)($params['is_home_recommended']) : 0,
            'sort'                => isset($params['sort']) ? (int)($params['sort']) : 0,
            'is_enable'           => isset($params['is_enable']) ? (int)($params['is_enable']) : 0,
            'icon'                => $attachment['data']['icon'],
            'big_images'          => $attachment['data']['big_images'],
            'seo_title'           => empty($params['seo_title']) ? '' : $params['seo_title'],
            'seo_keywords'        => empty($params['seo_keywords']) ? '' : $params['seo_keywords'],
            'seo_desc'            => empty($params['seo_desc']) ? '' : $params['seo_desc'],
        ];

        // 父级id宇当前id不能相同
        if (!empty($params['id']) && $params['id'] === $data['pid']) {
            return dataReturn('父级不能与当前相同', -10);
        }

        // 添加/编辑
        $code = -100;
        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('goods_category')->insertGetId($data) > 0) {
                $code = 0;
                $msg  = '添加成功';
            } else {
                $msg = '添加失败';
            }
        } else {
            $data['upd_time'] = time();
            if (Db::table('goods_category')->where(['id' => (int)($params['id'])])->update($data)) {
                $code = 0;
                $msg  = '编辑成功';
            } else {
                $msg = '编辑失败';
            }
        }

        // 状态
        // if ($code == 0) {
        // 删除大分类缓存
        // cache(config('shopxo.cache_goods_category_key'), null);
        // }
        return dataReturn($msg, $code);
    }

    /**
     * 商品分类删除.
     *
     * @param array $params
     *
     * @return array
     */
    public function goodsCategoryDelete($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'id',
                'error_msg'    => '删除数据id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'admin',
                'error_msg'    => '用户信息有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 获取分类下所有分类id
        $ids   = $this->goodsCategoryItemsIds([$params['id']]);
        $ids[] = $params['id'];

        // 开始删除
        if (Db::table('goods_category')->where(['id' => $ids])->delete() === 1) {
            // 删除大分类缓存
            // cache(config('cache_goods_category_key'), null);
            return dataReturn('删除成功', 0);
        }

        return dataReturn('删除失败', -100);
    }

    /**
     * 商品分类数据处理.
     *
     * @param $data
     *
     * @return mixed
     */
    private function goodsCategoryDataDealWith($data)
    {
        if (!empty($data) && \is_array($data)) {
            foreach ($data as $v) {
                if (\is_array($v)) {
                    if (isset($v['icon'])) {
                        $v['icon'] = ResourcesService::attachmentPathViewHandle($v['icon']);
                    }

                    if (isset($v['big_images'])) {
                        $v['big_images_old'] = $v['big_images'];
                        $v['big_images']     = ResourcesService::attachmentPathViewHandle($v['big_images']);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * 商品保存基础信息更新.
     *
     * @param $goods_id
     *
     * @return array
     */
    private function goodsSaveBaseUpdate($goods_id)
    {
        $data = Db::table('goods_spec_base')->selectRaw('min(price) AS min_price, max(price) AS max_price, sum(inventory) AS inventory, min(original_price) AS min_original_price, max(original_price) AS max_original_price')->where(['goods_id' => $goods_id])->first()->toArray();
        if (empty($data)) {
            return dataReturn('没找到商品基础信息', -1);
        }

        // 销售价格 - 展示价格
        $data['price'] = (!empty($data['max_price']) && $data['min_price'] !== $data['max_price']) ? $data['min_price'] . '-' . $data['max_price'] : $data['min_price'];

        // 原价价格 - 展示价格
        $data['original_price'] = (!empty($data['max_original_price']) && $data['min_original_price'] !== $data['max_original_price']) ? $data['min_original_price'] . '-' . $data['max_original_price'] : $data['min_original_price'];

        // 更新商品表
        $data['upd_time'] = time();
        if (Db::table('goods')->where(['id' => $goods_id])->update($data)) {
            return dataReturn('操作成功', 0);
        }

        return dataReturn('操作失败', 0);
    }

    /**
     * 获取规格参数.
     *
     * @param array $params
     *
     * @return array
     */
    private function getFormGoodsSpecificationsParams($params = [])
    {
        $data   = [];
        $title  = [];
        $images = [];

        // 基础字段数据字段长度
        $base_count = 6;

        // 规格值
        foreach ($params as $k => $v) {
            if (substr($k, 0, 15) === 'specifications_') {
                $keys = explode('_', $k);
                if (\count($keys) > 1) {
                    if ($keys[1] !== 'name') {
                        foreach ($v as $ks => $vs) {
                            $data[$ks][] = $vs;
                        }
                    }
                }
            }
        }

        // 规格处理
        if (!empty($data[0])) {
            $count = (\count($data[0]) - $base_count);
            if ($count > 0) {
                // 列之间是否存在相同的值
                $column_value = [];
                foreach ($data as $data_value) {
                    foreach ($data_value as $temp_key => $temp_value) {
                        if ($temp_key < $count) {
                            $column_value[$temp_key][] = $temp_value;
                        }
                    }
                }

                if (!empty($column_value) && \count($column_value) > 1) {
                    $temp_column = [];
                    foreach ($column_value as $column_key => $column_val) {
                        foreach ($column_value as $column_keys => $column_vals) {
                            if ($column_key !== $column_keys) {
                                $temp        = array_intersect($column_val, $column_vals);
                                $temp_column = array_merge($temp_column, $temp);
                            }
                        }
                    }

                    if (!empty($temp_column)) {
                        return dataReturn('规格值列之间不能重复[' . implode(',', array_unique($temp_column)) . ']', -1);
                    }
                }

                // 规格名称
                $names_value = [];
                $names       = \array_slice($data[0], 0, $count);
                foreach ($names as $v) {
                    foreach ($params as $ks => $vs) {
                        if (substr($ks, 0, 21) === 'specifications_value_') {
                            if (\in_array($v, $vs, true)) {
                                $key = substr($ks, 21);
                                if (!empty($params['specifications_name_' . $key])) {
                                    $title[$params['specifications_name_' . $key]] = [
                                        'name'  => $params['specifications_name_' . $key],
                                        'value' => array_unique($vs),
                                    ];
                                    $names_value[]                                 = $params['specifications_name_' . $key];
                                }
                            }
                        }
                    }
                }

                // 规格名称列之间是否存在重复
                $unique_all       = array_unique($names_value);
                $repeat_names_all = array_diff_assoc($names_value, $unique_all);
                if (!empty($repeat_names_all)) {
                    return dataReturn('规格名称列之间不能重复[' . implode(',', $repeat_names_all) . ']', -1);
                }
            } else {
                if (empty($data[0][0]) || $data[0][0] <= 0) {
                    return dataReturn('请填写有效的规格销售价格', -1);
                }

                if (!isset($data[0][1]) || $data[0][1] < 0) {
                    return dataReturn('请填写规格库存', -1);
                }
            }//end if
        } else {
            return dataReturn('请填写规格', -1);
        }//end if

        // 规格图片
        if (!empty($params['spec_images_name']) && !empty($params['spec_images'])) {
            foreach ($params['spec_images_name'] as $k => $v) {
                if (!empty($params['spec_images'][$k])) {
                    $images[$v] = $params['spec_images'][$k];
                }
            }
        }

        return dataReturn('success', 0, ['data' => $data, 'title' => $title, 'images' => $images]);
    }

    /**
     * 获取商品相册.
     *
     * @param array $params
     *
     * @return array
     */
    private function getFormGoodsPhotoParams($params = [])
    {
        if (empty($params['photo'])) {
            return dataReturn('请上传相册', -1);
        }

        $result = [];
        if (!empty($params['photo']) && \is_array($params['photo'])) {
            foreach ($params['photo'] as $v) {
                $result[] = ResourcesService::AttachmentPathHandle($v);
            }
        }

        return dataReturn('success', 0, $result);
    }


    /**
     * 获取app内容.
     *
     * @param array $params
     *
     * @return array
     */
    private function getFormGoodsContentAppParams($params = [])
    {
        // 开始处理
        $result = [];
        $name   = 'content_app_';
        foreach ($params as $k => $v) {
            if (substr($k, 0, 12) === $name) {
                $key = explode('_', str_replace($name, '', $k));
                if (\count($key) === 2) {
                    $result[$key[1]][$key[0]] = $v;
                    if ($key[0] === 'images') {
                        $result[$key[1]][$key[0]] = ResourcesService::AttachmentPathHandle($v);
                    }
                }
            }
        }

        return dataReturn('success', 0, $result);
    }


    /**
     * 商品分类添加.
     *
     * @param $data
     * @param $goods_id
     *
     * @return array
     */
    private function goodsCategoryInsert($data, $goods_id)
    {
        Db::table('goods_category_join')->where(['goods_id' => $goods_id])->delete();
        if (!empty($data)) {
            foreach ($data as $category_id) {
                $temp_category = [
                    'goods_id'    => $goods_id,
                    'category_id' => $category_id,
                    'add_time'    => time(),
                ];
                if (Db::table('goods_category_join')->insertGetId($temp_category) <= 0) {
                    return dataReturn('商品分类添加失败', -1);
                }
            }
        }

        return dataReturn('添加成功', 0);
    }


    /**
     * 商品手机详情添加.
     *
     * @param $data
     * @param $goods_id
     *
     * @return array
     */
    private function goodsContentAppInsert($data, $goods_id)
    {
        Db::table('goods_content_app')->where(['goods_id' => $goods_id])->delete();
        if (!empty($data)) {
            foreach (array_values($data) as $k => $v) {
                $temp_content = [
                    'goods_id' => $goods_id,
                    'images'   => empty($v['images']) ? '' : $v['images'],
                    'content'  => $v['text'],
                    'sort'     => $k,
                    'add_time' => time(),
                ];
                if (Db::table('goods_content_app')->insertGetId($temp_content) <= 0) {
                    return dataReturn('手机详情添加失败', -1);
                }
            }
        }

        return dataReturn('添加成功', 0);
    }


    /**
     * 商品相册添加.
     *
     * @param $data
     * @param $goods_id
     *
     * @return array
     */
    private function goodsPhotoInsert($data, $goods_id)
    {
        Db::table('goods_photo')->where(['goods_id' => $goods_id])->delete();
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $temp_photo = [
                    'goods_id' => $goods_id,
                    'images'   => $v,
                    'is_show'  => 1,
                    'sort'     => $k,
                    'add_time' => time(),
                ];
                if (Db::table('goods_photo')->insertGetId($temp_photo) <= 0) {
                    return dataReturn('相册添加失败', -1);
                }
            }
        }

        return dataReturn('添加成功', 0);
    }


    /**
     * 商品规格添加.
     *
     * @param $data
     * @param $goods_id
     *
     * @return array
     */
    private function goodsSpecificationsInsert($data, $goods_id)
    {
        // 删除原来的数据
        Db::table('goods_spec_type')->where(['goods_id' => $goods_id])->delete();
        Db::table('goods_spec_value')->where(['goods_id' => $goods_id])->delete();
        Db::table('goods_spec_base')->where(['goods_id' => $goods_id])->delete();

        // 类型
        if (!empty($data['title'])) {
            foreach ($data['title'] as $v) {
                $spec = [];
                foreach ($v['value'] as $vs) {
                    $spec[] = [
                        'name'   => $vs,
                        'images' => isset($data['images'][$vs]) ? ResourcesService::AttachmentPathHandle($data['images'][$vs]) : '',
                    ];
                }

                $v['goods_id'] = $goods_id;
                $v['value']    = json_encode($spec);
                $v['add_time'] = time();
            }

            if (Db::table('goods_spec_type')->insert($data['title']) < \count($data['title'])) {
                return dataReturn('规格类型添加失败', -1);
            }
        }

        // 基础/规格值
        if (!empty($data['data'])) {
            // 基础字段
            $count     = \count($data['data'][0]);
            $temp_key  = [
                'price',
                'inventory',
                'weight',
                'coding',
                'barcode',
                'original_price',
            ];
            $key_count = \count($temp_key);

            // 等于key总数则只有一列基础规格
            if ($count === $key_count) {
                $temp_data = [
                    'goods_id' => $goods_id,
                    'add_time' => time(),
                ];
                for ($i = 0; $i < $count; ++$i) {
                    $temp_data[$temp_key[$i]] = $data['data'][0][$i];
                }

                // 规格基础添加
                if (Db::table('goods_spec_base')->insertGetId($temp_data) <= 0) {
                    return dataReturn('规格基础添加失败', -1);
                }

                // 多规格操作
            } else {
                $base_start = ($count - $key_count);
                // $value      = [];
                // $base       = [];
                foreach ($data['data'] as $v) {
                    $temp_value = [];
                    $temp_data  = [
                        'goods_id' => $goods_id,
                        'add_time' => time(),
                    ];
                    for ($i = 0; $i < $count; ++$i) {
                        if ($i < $base_start) {
                            $temp_value[] = [
                                'goods_id' => $goods_id,
                                'value'    => $v[$i],
                                'add_time' => time(),
                            ];
                        } else {
                            $temp_data[$temp_key[($i - $base_start)]] = $v[$i];
                        }
                    }

                    // 规格基础添加
                    $base_id = Db::table('goods_spec_base')->insertGetId($temp_data);
                    if (empty($base_id)) {
                        return dataReturn('规格基础添加失败', -1);
                    }

                    // 规格值添加
                    foreach ($temp_value as $value) {
                        $value['goods_spec_base_id'] = $base_id;
                    }

                    if (Db::table('goods_spec_value')->insert($temp_value) < \count($temp_value)) {
                        return dataReturn('规格值添加失败', -1);
                    }
                }//end foreach
            }//end if
        }//end if

        return dataReturn('添加成功', 0);
    }

}
