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

class AppNavService
{
    const common_app_event_type = [
        0 => [
            'value' => 0,
            'name'  => 'WEB页面',
        ],
        1 => [
            'value' => 1,
            'name'  => '内部页面(小程序/APP内部地址)',
        ],
        2 => [
            'value' => 2,
            'name'  => '外部小程序(同一个主体下的小程序appid)',
        ],
        3 => [
            'value' => 3,
            'name'  => '跳转原生地图查看指定位置',
        ],
        4 => [
            'value' => 4,
            'name'  => '拨打电话',
        ],
    ];

    const common_platform_type = [
        'pc'      => [
            'value' => 'pc',
            'name'  => 'PC网站',
        ],
        'h5'      => [
            'value' => 'h5',
            'name'  => 'H5手机网站',
        ],
        'ios'     => [
            'value' => 'ios',
            'name'  => '苹果APP',
        ],
        'android' => [
            'value' => 'android',
            'name'  => '安卓APP',
        ],
        'alipay'  => [
            'value' => 'alipay',
            'name'  => '支付宝小程序',
        ],
        'weixin'  => [
            'value' => 'weixin',
            'name'  => '微信小程序',
        ],
        'baidu'   => [
            'value' => 'baidu',
            'name'  => '百度小程序',
        ],
    ];

    public function appHomeNavList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $field    = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'sort asc' : trim($params['order_by']);

        $m = isset($params['m']) ? (int)($params['m']) : 0;
        $n = isset($params['n']) ? (int)($params['n']) : 10;

        // 获取品牌列表
        $data = Db::table('app_home_nav')->where($where)->orderByRaw($order_by)->limit($n)->offset($m * $n)->selectRaw($field)->get();
        if (!empty($data)) {
            foreach ($data as &$v) {
                // 图片地址
                if (isset($v->images_url)) {
                    $v->images_url_old = $v->images_url;
                    $v->images_url     = ResourcesService::AttachmentPathViewHandle($v->images_url);
                }
            }
        }

        return dataReturn('处理成功', 0, $data);
    }

    /**
     * @param $where
     *
     * @return int
     */
    public function appHomeNavTotal($where)
    {
        return Db::table('app_home_nav')->where($where)->count();
    }

    /**
     * 首页导航列表条件.
     *
     * @param array $params
     *
     * @return array
     */
    public function appHomeNavListWhere($params = [])
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

            if (isset($params['is_need_login']) && $params['is_need_login'] > -1) {
                $where[] = [
                    'is_need_login',
                    '=',
                    (int)($params['is_need_login']),
                ];
            }

            if (isset($params['event_type']) && $params['event_type'] > -1) {
                $where[] = [
                    'event_type',
                    '=',
                    (int)($params['event_type']),
                ];
            }

            if (!empty($params['platform'])) {
                $where[] = [
                    'platform',
                    '=',
                    $params['platform'],
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

    public function appHomeNavSave($params = [])
    {
        // 请求类型
        $p   = [
            [
                'checked_type' => 'length',
                'key_name'     => 'name',
                'checked_data' => '2,60',
                'error_msg'    => '名称长度 2~60 个字符',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'platform',
                'checked_data' => array_column(self::common_platform_type, 'value'),
                'error_msg'    => '平台类型有误',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'event_type',
                'checked_data' => array_column(self::common_app_event_type, 'value'),
                'is_checked'   => 2,
                'error_msg'    => '事件值类型有误',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'event_value',
                'checked_data' => '255',
                'error_msg'    => '事件值最多 255 个字符',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'images_url',
                'checked_data' => '255',
                'error_msg'    => '请上传图片',
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
        $data_fields = ['images_url'];
        $attachment  = ResourcesService::AttachmentParams($params, $data_fields);

        // 数据
        $data = [
            'name'          => $params['name'],
            'platform'      => $params['platform'],
            'event_type'    => isset($params['event_type']) ? (int)($params['event_type']) : -1,
            'event_value'   => $params['event_value'],
            'images_url'    => $attachment['data']['images_url'],
            'bg_color'      => isset($params['bg_color']) ? $params['bg_color'] : '',
            'sort'          => (int)($params['sort']),
            'is_enable'     => isset($params['is_enable']) ? (int)($params['is_enable']) : 0,
            'is_need_login' => isset($params['is_need_login']) ? (int)($params['is_need_login']) : 0,
        ];

        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('app_home_nav')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }

            return dataReturn('添加失败', -100);
        }

        $data['upd_time'] = time();
        if (Db::table('app_home_nav')->where(['id' => (int)($params['id'])])->update($data) === 1) {
            return dataReturn('编辑成功', 0);
        }

        return dataReturn('编辑失败', -100);
    }

    public function appHomeNavStatusUpdate($params = [])
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
                'error_msg'    => '操作字段有误',
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
        if (Db::table('app_home_nav')->where(['id' => (int)($params['id'])])->update([$params['field'] => (int)($params['state'])]) === 1) {
            return dataReturn('编辑成功');
        }

        return dataReturn('编辑失败或数据未改变', -100);
    }

    /**
     * APP获取首页导航.
     *
     * @return mixed
     */
    public function appHomeNav()
    {
        // todo 判断平台类型
        $client_type = isMobile() ? 'h5' : 'pc';
        $data = Db::table('app_home_nav')->where([
            'platform'  => $client_type,
            'is_enable' => 1,
        ])->orderByRaw('sort asc')->selectRaw('id,name,images_url,event_value,event_type,bg_color,is_need_login')->get();
        if (!empty($data)) {
            foreach ($data as &$v) {
                $v->images_url_old = $v->images_url;
                $v->images_url     = ResourcesService::AttachmentPathViewHandle($v->images_url);
                $v->event_value    = empty($v->event_value) ? null : $v->event_value;
            }
        }

        return $data;
    }
}
