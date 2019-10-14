<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 友情链接服务层
 * @author colin.
 * date 19-6-26 下午8:43
 */
class LinkService
{
    /**
     * 列表
     * @param array $params
     * @return array
     */
    public static function linkList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $data  = Db::table('link')->where($where)->orderByRaw('sort asc')->get();
        return dataReturn('处理成功', 0, $data);
    }

    /**
     * @param array $params
     * @return array
     */
    public static function linkSave($params = [])
    {
        // 请求类型
        $p   = [
            [
                'checked_type' => 'length',
                'key_name'     => 'name',
                'checked_data' => '2,16',
                'error_msg'    => '名称格式 2~16 个字符',
            ],
            [
                'checked_type' => 'fun',
                'key_name'     => 'url',
                'checked_data' => 'CheckUrl',
                'error_msg'    => '链接地址格式有误',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'sort',
                'checked_data' => '3',
                'error_msg'    => '顺序 0~255 之间的数值',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'is_new_window_open',
                'checked_data' => [0, 1],
                'error_msg'    => '是否新窗口打开范围值有误',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'is_enable',
                'checked_data' => [0, 1],
                'error_msg'    => '是否显示范围值有误',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'describe',
                'checked_data' => '60',
                'error_msg'    => '描述不能大于60个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 数据
        $data = [
            'name'               => $params['name'],
            'describe'           => $params['describe'],
            'url'                => $params['url'],
            'sort'               => intval($params['sort']),
            'is_enable'          => intval($params['is_enable']),
            'is_new_window_open' => intval($params['is_new_window_open']),
        ];

        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('link')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }
            return dataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if (Db::table('link')->where(['id' => intval($params['id'])])->update($data)) {
                return dataReturn('编辑成功', 0);
            }
            return dataReturn('编辑失败', -100);
        }
    }

    /**
     * @param array $params
     * @return array
     */
    public static function linkDelete($params = [])
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
        if (Db::table('link')->where(['id' => $params['id']])->delete()) {
            return dataReturn('删除成功');
        }

        return dataReturn('删除失败或资源不存在', -100);
    }

    /**
     * @param array $params
     * @return array
     */
    public static function linkStatusUpdate($params = [])
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
        if (Db::table('link')->where(['id' => intval($params['id'])])->update(['is_enable' => intval($params['state'])])) {
            return dataReturn('编辑成功');
        }
        return dataReturn('编辑失败或数据未改变', -100);
    }

}