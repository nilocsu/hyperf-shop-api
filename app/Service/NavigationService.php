<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * @author colin.
 * date 19-6-27 上午11:55
 */
class NavigationService
{
    /**
     * 获取导航
     * @return array
     */
    public function nav()
    {
        // 读取缓存数据
//        $header = cache(config('cache_common_home_nav_header_key'));
//        $footer = cache(config('cache_common_home_nav_footer_key'));

        // 导航模型
        $field = 'id, pid, name, url, value, data_type, is_new_window_open';

        // 缓存没数据则从数据库重新读取,顶部菜单
        if (empty($header)) {
            $header = $this->navDataDealWith(Db::table('navigation')->selectRaw($field)->where([
                'nav_type' => 'header',
                'is_show'  => 1,
                'pid'      => 0,
            ])->orderBy('sort')->get()->toArray());
            if (!empty($header)) {
                foreach ($header as &$v) {
                    $v['items'] = $this->navDataDealWith(Db::table('navigation')->selectRaw($field)->where([
                        'nav_type' => 'header',
                        'is_show'  => 1,
                        'pid'      => $v['id'],
                    ])->orderBy('sort')->get());
                }
            }

//            cache(config('cache_common_home_nav_header_key'), $header);
        }

        // 底部导航
        if (empty($footer)) {
            $footer = $this->navDataDealWith(Db::table('navigation')->selectRaw($field)->where([
                'nav_type' => 'footer',
                'is_show'  => 1,
                'pid'      => 0,
            ])->orderBy('sort')->get()->toArray());
            if (!empty($footer)) {
                foreach ($footer as &$v) {
                    $v['items'] = $this->navDataDealWith(Db::table('navigation')->selectRaw($field)->where([
                        'nav_type' => 'footer',
                        'is_show'  => 1,
                        'pid'      => $v['id'],
                    ])->orderBy('sort')->get());
                }
            }

//            cache(config('cache_common_home_nav_footer_key'), $footer);
        }

        return [
            'header' => $header,
            'footer' => $footer,
        ];
    }

    /**
     * 导航数据处理
     * @param $data
     * @return array
     */
    public function navDataDealWith($data)
    {
        if (!empty($data) && is_array($data)) {
            foreach ($data as $k => $v) {
                // todo url处理
//                switch($v['data_type'])
//                {
//                    // 文章分类
//                    case 'article':
//                        $v['url'] = MyUrl('index/article/index', ['id'=>$v['value']]);
//                        break;
//
//                    // 自定义页面
//                    case 'custom_view':
//                        $v['url'] = MyUrl('index/custom_view/index', ['id'=>$v['value']]);
//                        break;
//
//                    // 商品分类
//                    case 'goods_category':
//                        $v['url'] = MyUrl('index/search/index', ['category_id'=>$v['value']]);
//                        break;
//                }
                $data[$k] = $v;
            }
        }
        return $data;
    }

    /**
     * 获取导航列表
     * @param array $params
     * @return array
     */
    public function navList($params = [])
    {
        if (empty($params['nav_type'])) {
            return [];
        }

        $field = 'id,pid,name,url,value,data_type,sort,is_show,is_new_window_open';
        $data  = $this->NavDataDealWith(Db::table('navigation')->selectRaw($field)->where([
            'nav_type' => $params['nav_type'],
            'pid'      => 0,
        ])->orderBy('sort')->get()->toArray());
        if (!empty($data)) {
            foreach ($data as &$v) {
                $v['items'] = $this->NavDataDealWith(Db::table('navigation')->selectRaw($field)->where([
                    'nav_type' => $params['nav_type'],
                    'pid'      => $v['id'],
                ])->orderBy('sort')->get());
            }
        }
        return $data;
    }

    /**
     * 获取一级导航列表
     * @param array $params
     * @return array|\Hyperf\Utils\Collection
     */
    public function levelOneNav($params = [])
    {
        if (empty($params['nav_type'])) {
            return [];
        }

        return Db::table('navigation')->selectRaw('id,name')->where([
            'is_show'  => 1,
            'pid'      => 0,
            'nav_type' => $params['nav_type'],
        ])->get();
    }

    /**
     * 导航保存
     * @param array $params
     * @return array
     */
    public function navSave($params = [])
    {
        if (empty($params['data_type'])) {
            return dataReturn('操作类型有误', -1);
        }

        // 请求类型
//        $p = [
//            [
//                'checked_type'      => 'length',
//                'key_name'          => 'sort',
//                'checked_data'      => '4',
//                'error_msg'         => '顺序 0~255 之间的数值',
//            ],
//            [
//                'checked_type'      => 'in',
//                'key_name'          => 'is_show',
//                'checked_data'      => [0,1],
//                'error_msg'         => '是否显示范围值有误',
//            ],
//            [
//                'checked_type'      => 'in',
//                'key_name'          => 'is_new_window_open',
//                'checked_data'      => [0,1],
//                'error_msg'         => '是否新窗口打开范围值有误',
//            ]
//        ];
        switch ($params['data_type']) {
            // 自定义导航
            case 'custom':
                $p = [
                    [
                        'checked_type' => 'in',
                        'key_name'     => 'nav_type',
                        'checked_data' => ['header', 'footer'],
                        'error_msg'    => '数据类型有误',
                    ],
                    [
                        'checked_type' => 'length',
                        'key_name'     => 'name',
                        'checked_data' => '2,16',
                        'error_msg'    => '导航名称格式 2~16 个字符',
                    ],
                    [
                        'checked_type' => 'fun',
                        'key_name'     => 'url',
                        'checked_data' => 'CheckUrl',
                        'error_msg'    => 'url格式有误',
                    ],
                ];
                break;

            // 文章分类导航
            case 'article':
                $p = [
                    [
                        'checked_type' => 'length',
                        'key_name'     => 'name',
                        'checked_data' => '2,16',
                        'is_checked'   => 1,
                        'error_msg'    => '导航名称格式 2~16 个字符',
                    ],
                    [
                        'checked_type' => 'empty',
                        'key_name'     => 'value',
                        'error_msg'    => '文章选择有误',
                    ],
                ];
                break;

            // 自定义页面导航
            case 'custom_view':
                $p = [
                    [
                        'checked_type' => 'length',
                        'key_name'     => 'name',
                        'checked_data' => '2,16',
                        'is_checked'   => 1,
                        'error_msg'    => '导航名称格式 2~16 个字符',
                    ],
                    [
                        'checked_type' => 'empty',
                        'key_name'     => 'value',
                        'error_msg'    => '自定义页面选择有误',
                    ],
                ];
                break;

            // 商品分类导航
            case 'goods_category':
                $p = [
                    [
                        'checked_type' => 'length',
                        'key_name'     => 'name',
                        'checked_data' => '2,16',
                        'is_checked'   => 1,
                        'error_msg'    => '导航名称格式 2~16 个字符',
                    ],
                    [
                        'checked_type' => 'empty',
                        'key_name'     => 'value',
                        'error_msg'    => '商品分类选择有误',
                    ],
                ];
                break;

            // 没找到
            default :
                return dataReturn('操作类型有误', -1);
        }

        // 参数
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 保存数据
        return $this->nacDataSave($params);
    }

    /**
     * 导航数据保存
     * @param array $params
     * @return array
     */
    public function nacDataSave($params = [])
    {
        // 非自定义导航数据处理
        if (empty($params['name'])) {
            switch ($params['data_type']) {
                // 文章分类导航
                case 'article':
                    $tmp_name = Db::table('Article')->where(['id' => $params['value']])->value('title');
                    break;

                // 自定义页面导航
                case 'custom_view':
                    $tmp_name = Db::table('custom_view')->where(['id' => $params['value']])->value('title');
                    break;

                // 商品分类导航
                case 'goods_category':
                    $tmp_name = Db::table('GoodsCategory')->where(['id' => $params['value']])->value('name');
                    break;
                default:
                    $tmp_name = '';
                    break;
            }
            // 只截取16个字符
            $params['name'] = mb_substr($tmp_name, 0, 16);
        }

        // 清除缓存
//        cache(config('cache_common_home_nav_'.$params['nav_type'].'_key'), null);

        // 数据
        $data = [
            'pid'                => isset($params['pid']) ? intval($params['pid']) : 0,
            'value'              => isset($params['value']) ? intval($params['value']) : 0,
            'name'               => $params['name'],
            'url'                => isset($params['url']) ? $params['url'] : '',
            'nav_type'           => $params['nav_type'],
            'data_type'          => $params['data_type'],
            'sort'               => intval($params['sort']),
            'is_show'            => intval($params['is_show']),
            'is_new_window_open' => intval($params['is_new_window_open']),
        ];

        // id为空则表示是新增
        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('navigation')->insertGetId($data) > 0) {
                // 清除缓存
//                cache(config('cache_common_home_nav_'.$params['nav_type'].'_key'), null);

                return dataReturn('新增成功', 0);
            } else {
                return dataReturn('新增失败', -100);
            }
        } else {
            $data['upd_time'] = time();
            if (Db::table('navigation')->where(['id' => intval($params['id'])])->update($data)) {
                // 清除缓存
//                cache(config('cache_common_home_nav_'.$params['nav_type'].'_key'), null);

                return dataReturn('编辑成功', 0);
            } else {
                return dataReturn('编辑失败或数据未改变', -100);
            }
        }
    }

    /**
     * 导航删除
     * @param array $params
     * @return array
     */
    public function navDelete($params = [])
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

        // 启动事务
        Db::beginTransaction();

        // 删除操作
        if (Db::table('navigation')->where(['id' => $params['id']])->delete() !== false && Db::table('navigation')->where(['pid' => $params['id']])->delete() !== false) {
            // 提交事务
            Db::commit();

            // 清除缓存
//            cache(config('cache_common_home_nav_header_key'), null);
//            cache(config('cache_common_home_nav_footer_key'), null);

            return dataReturn('删除成功');
        }

        // 回滚事务
        Db::rollback();

        return dataReturn('删除失败或资源不存在', -100);
    }

    /**
     * 状态更新
     * @param array $params
     * @return array
     */
    public function navStatusUpdate($params = [])
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
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 数据更新
        if (Db::table('navigation')->where(['id' => intval($params['id'])])->update(['is_show' => intval($params['state'])])) {
            // 清除缓存
//            cache(config('cache_common_home_nav_header_key'), null);
//            cache(config('cache_common_home_nav_footer_key'), null);

            return dataReturn('编辑成功');
        }
        return dataReturn('编辑失败或数据未改变', -100);
    }

}