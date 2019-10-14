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

/**
 * 品牌服务层
 */
class BrandService
{
    /**
     * 品牌列表.
     *
     * @param array $params
     *
     * @return array
     */
    public function brandList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $order_by = empty($params['order_by']) ? 'sort asc' : trim($params['order_by']);

        $m = isset($params['m']) ? (int)($params['m']) : 0;
        $n = isset($params['n']) ? (int)($params['n']) : 10;

        // 获取品牌列表
        $data = Db::table('brand')->where($where)->orderByRaw($order_by)->limit($n)->offset($m * $n)->get();
        if (!empty($data)) {
            foreach ($data as $v) {
                // 分类名称
                if (isset($v->brand_category_id)) {
                    $v->brand_category_name = Db::table('brand_category')->where(['id' => $v->brand_category_id])->value('name');
                }

                // logo
                if (isset($v->logo)) {
                    $v->logo_old = $v->logo;
                    $v->logo     = ResourcesService::AttachmentPathViewHandle($v->logo);
                }
            }
        }

        return dataReturn('处理成功', 0, $data);
    }

    /**
     * 品牌总数.
     *
     * @param array $where
     *
     * @return int
     */
    public function brandTotal(array $where)
    {
        return Db::table('brand')->where($where)->count();
    }

    /**
     * 列表条件.
     *
     * @param array $params
     *
     * @return array
     */
    public function brandListWhere($params = [])
    {
        $where = [];

        if (!empty($params['keywords'])) {
            $where[] = [
                'name',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] === 1) {
            // 等值
            if (isset($params['is_enable']) && $params['is_enable'] > -1) {
                $where[] = [
                    'is_enable',
                    '=',
                    (int)($params['is_enable']),
                ];
            }

            if (isset($params['brand_category_id']) && $params['brand_category_id'] > -1) {
                $where[] = [
                    'brand_category_id',
                    '=',
                    (int)($params['brand_category_id']),
                ];
            }

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
        }

        return $where;
    }

    /**
     * 获取所有分类及下面品牌.
     *
     * @return \Hyperf\Utils\Collection
     */
    public function CategoryBrand()
    {
        $data = Db::table('brand_category')->where(['is_enable' => 1])->get();
        if (!empty($data)) {
            foreach ($data as $v) {
                $v->items = Db::table('brand')->selectRaw('id,name')->where(['is_enable'         => 1,
                                                                             'brand_category_id' => $v->id
                ])->orderByRaw('sort asc')->get();
            }
        }

        return $data;
    }

    /**
     * 分类下品牌列表.
     *
     * @param array $params
     *
     * @return \Hyperf\Utils\Collection
     */
    public function CategoryBrandList($params = [])
    {
        $brand_where = ['is_enable' => 1];

        // 分类id
        if (!empty($params['category_id'])) {
            // 根据分类获取品牌id
            $goodsService   = make(GoodsService::class);
            $category_ids   = $goodsService->goodsCategoryItemsIds([$params['category_id']], 1);
            $category_ids[] = $params['category_id'];
            $where          = [
                'goods.is_delete_time'            => 0,
                'goods.is_shelves'                => 1,
                'goods_category_join.category_id' => $category_ids,
            ];
            // $brand_where['id'] = Db::table('Goods')->alias('g')->join(['__GOODS_CATEGORY_JOIN__'=>'gci'], 'g.id=gci.goods_id')->field('g.brand_id')->where($where)->group('g.brand_id')->column('brand_id');
            $brand_where['id'] = Db::table('goods')->join('goods_category_join', 'goods.id', '=',
                'goods_category_join.goods_id')->selectRaw('goods.brand_id')->where($where)->groupBy('goods.brand_id')->pluck('brand_id')->toArray();
        }

        // 关键字
        if (!empty($params['keywords'])) {
            $where             = [
                [
                    'title',
                    'like',
                    '%' . $params['keywords'] . '%',
                ],
            ];
            $brand_where['id'] = Db::table('Goods')->where($where)->groupBy('brand_id')->pluck('brand_id')->toArray();
        }

        // 获取品牌列表
        $brand = Db::table('brand')->where($brand_where)->selectRaw('id,name,logo,website_url')->get();
        if (!empty($brand)) {
            foreach ($brand as $v) {
                $v->logo_old    = $v->logo;
                $v->logo        = ResourcesService::attachmentPathViewHandle($v->logo);
                $v->website_url = empty($v['website_url']) ? null : $v->website_url;

                // logo
                if (isset($v->logo)) {
                    $v->logo_old = $v->logo;
                    $v->logo     = ResourcesService::AttachmentPathViewHandle($v->logo);
                }
            }
        }

        return $brand;
    }

    /**
     * 获取品牌名称.
     *
     * @param int $brand_id
     *
     * @return null|mixed
     */
    public function brandName($brand_id = 0)
    {
        return empty($brand_id) ? null : Db::table('brand')->where(['id' => (int)$brand_id])->value('name');
    }

    /**
     * 品牌分类.
     *
     * @param array $params
     *
     * @return array
     */
    public function brandCategoryList($params = [])
    {
        $field    = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort asc' : trim($params['order_by']);

        $data = Db::table('brand_category')->where(['is_enable' => 1])->selectRaw($field)->orderByRaw($order_by)->get();

        return dataReturn('处理成功', 0, $data);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function brandSave($params = [])
    {
        // 请求类型
        $p   = [
            [
                'checked_type' => 'length',
                'key_name'     => 'name',
                'checked_data' => '2,30',
                'error_msg'    => '名称格式 2~30 个字符',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'brand_category_id',
                'error_msg'    => '请选择品牌分类',
            ],
            [
                'checked_type' => 'fun',
                'key_name'     => 'website_url',
                'checked_data' => 'CheckUrl',
                'is_checked'   => 1,
                'error_msg'    => '官网地址格式有误',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'sort',
                'checked_data' => '3',
                'error_msg'    => '顺序 0~255 之间的数值',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 附件
        $data_fields = ['logo'];
        $attachment  = ResourcesService::AttachmentParams($params, $data_fields);

        // 数据
        $data = [
            'name'              => $params['name'],
            'brand_category_id' => (int)($params['brand_category_id']),
            'logo'              => $attachment['data']['logo'],
            'website_url'       => empty($params['website_url']) ? '' : $params['website_url'],
            'sort'              => (int)($params['sort']),
            'is_enable'         => isset($params['is_enable']) ? (int)($params['is_enable']) : 0,
        ];

        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('brand')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }

            return dataReturn('添加失败', -100);
        }

        $data['upd_time'] = time();
        if (Db::table('brand')->where(['id' => (int)($params['id'])])->update($data) === 1) {
            return dataReturn('编辑成功', 0);
        }

        return dataReturn('编辑失败', -100);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function brandDelete($params = [])
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
        if (Db::table('brand')->where(['id' => $params['id']])->delete() === 1) {
            return dataReturn('删除成功');
        }

        return dataReturn('删除失败或资源不存在', -100);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function brandStatusUpdate($params = [])
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
        if (Db::table('brand')->where(['id' => (int)($params['id'])])->update(['is_enable' => (int)($params['state'])]) === 1) {
            return dataReturn('编辑成功');
        }

        return dataReturn('编辑失败或数据未改变', -100);
    }

    /**
     * 获取品牌分类节点数据.
     *
     * @param array $params
     *
     * @return array
     */
    public function brandCategoryNodeSon($params = [])
    {
        // id
        $id = isset($params['id']) ? (int)($params['id']) : 0;

        // 获取数据
        $field = '*';
        $data  = Db::table('brand_category')->selectRaw($field)->where(['pid' => $id])->orderByRaw('sort asc')->get();
        if (!empty($data)) {
            foreach ($data as $v) {
                $v->is_son = (Db::table('brand_category')->where(['pid' => $v->id])->count() > 0) ? 'ok' : 'no';
//                $v->ajax_url = MyUrl('admin/brandcategory/getnodeson', ['id' => $v->id]);
//                $v->delete_url = MyUrl('admin/brandcategory/delete');
                $v->json = json_encode((array)$v);
            }

            return dataReturn('操作成功', 0, $data);
        }

        return dataReturn('没有相关数据', -100);
    }

    /**
     * 品牌分类保存.
     *
     * @param array $params
     *
     * @return array
     */
    public function brandCategorySave($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'length',
                'key_name'     => 'name',
                'checked_data' => '2,16',
                'error_msg'    => '名称格式 2~16 个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 数据
        $data = [
            'name'      => $params['name'],
            'pid'       => isset($params['pid']) ? (int)($params['pid']) : 0,
            'sort'      => isset($params['sort']) ? (int)($params['sort']) : 0,
            'is_enable' => isset($params['is_enable']) ? (int)($params['is_enable']) : 0,
        ];

        // 添加
        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('brand_category')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }

            return dataReturn('添加失败', -100);
        }

        $data['upd_time'] = time();
        if (Db::table('brand_category')->where(['id' => (int)($params['id'])])->update($data) === 1) {
            return dataReturn('编辑成功', 0);
        }

        return dataReturn('编辑失败', -100);
    }

    /**
     * 品牌分类删除.
     *
     * @param array $params
     *
     * @return array
     */
    public function brandCategoryDelete($params = [])
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

        // 开始删除
        if (Db::table('brand_category')->where(['id' => (int)($params['id'])])->delete() === 1) {
            return dataReturn('删除成功', 0);
        }

        return dataReturn('删除失败', -100);
    }
}
