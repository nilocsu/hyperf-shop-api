<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 轮播图服务层
 * @author colin.
 * date 19-6-27 下午8:44
 */
class SlideService
{
    const common_platform_type = [
        'pc'      => ['value' => 'pc', 'name' => 'PC网站'],
        'h5'      => ['value' => 'h5', 'name' => 'H5手机网站'],
        'ios'     => ['value' => 'ios', 'name' => '苹果APP'],
        'android' => ['value' => 'android', 'name' => '安卓APP'],
        'alipay'  => ['value' => 'alipay', 'name' => '支付宝小程序'],
        'weixin'  => ['value' => 'weixin', 'name' => '微信小程序'],
        'baidu'   => ['value' => 'baidu', 'name' => '百度小程序'],
    ];

    /**
     * @param array $params
     * @return array
     */
    public static function slideList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $m     = isset($params['m']) ? intval($params['m']) : 0;
        $n     = isset($params['n']) ? intval($params['n']) : 10;

        $data = Db::table('slide')->selectRaw($field)->where($where)->orderByRaw('sort asc')->limit($n)->offset($n * $m)->get()->toArray();
        if (!empty($data)) {
            foreach ($data as &$v) {
                // 图片地址
                if (isset($v['images_url'])) {
                    $v['images_url_old'] = $v['images_url'];
                    $v['images_url']     = ResourcesService::AttachmentPathViewHandle($v['images_url']);
                }
            }
        }
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * @param array $where
     * @return int
     */
    public static function slideTotal($where = [])
    {
        return Db::table('slide')->where($where)->count();
    }

    /**
     * @param array $params
     * @return array
     */
    public static function slideListWhere($params = [])
    {
        $where = [];

        if (!empty($params['keywords'])) {
            $where[] = ['name', 'like', '%' . $params['keywords'] . '%'];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] == 1) {
            // 等值
            if (isset($params['is_enable']) && $params['is_enable'] > -1) {
                $where[] = ['is_enable', '=', intval($params['is_enable'])];
            }
            if (isset($params['event_type']) && $params['event_type'] > -1) {
                $where[] = ['event_type', '=', intval($params['event_type'])];
            }
            if (!empty($params['platform'])) {
                $where[] = ['platform', '=', $params['platform']];
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
     * @param array $params
     * @return array
     */
    public static function slideSave($params = [])
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
                'checked_data' => array_column(SlideService::common_platform_type, 'value'),
                'error_msg'    => '平台类型有误',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'event_type',
                'checked_data' => array_column(SlideService::common_platform_type, 'value'),
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
            'name'        => $params['name'],
            'platform'    => $params['platform'],
            'event_type'  => isset($params['event_type']) ? intval($params['event_type']) : -1,
            'event_value' => $params['event_value'],
            'images_url'  => $attachment['data']['images_url'],
            'bg_color'    => isset($params['bg_color']) ? $params['bg_color'] : '',
            'sort'        => intval($params['sort']),
            'is_enable'   => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
        ];

        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('slide')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }
            return dataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if (Db::table('slide')->where(['id' => intval($params['id'])])->update($data)) {
                return dataReturn('编辑成功', 0);
            }
            return dataReturn('编辑失败', -100);
        }
    }

    /**
     * @param array $params
     * @return array
     */
    public static function slideDelete($params = [])
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
        if (Db::table('slide')->where(['id' => $params['id']])->delete()) {
            return dataReturn('删除成功');
        }

        return dataReturn('删除失败或资源不存在', -100);
    }

    /**
     * @param array $params
     * @return array
     */
    public static function slideStatusUpdate($params = [])
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
        if (Db::table('slide')->where(['id' => intval($params['id'])])->update(['is_enable' => intval($params['state'])]) == 1) {
            return dataReturn('编辑成功');
        }
        return dataReturn('编辑失败或数据未改变', -100);
    }

}