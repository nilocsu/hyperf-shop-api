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
 * 地区服务层
 */
class RegionService
{
    /**
     * 获取地区名称.
     *
     * @param int $region_id
     *
     * @return null|mixed
     */
    public static function regionName(int $region_id = 0)
    {
        return empty($region_id) ? null : Db::table('region')->where(['id' => (int) $region_id])->value('name');
    }

    //end regionName()

    /**
     * 获取地区id下列表.
     *
     * @param array $params
     *
     * @return \Hyperf\Utils\Collection
     */
    public static function regionItems(array $params = [])
    {
        $pid = isset($params['pid']) ? (int) ($params['pid']) : 0;
        $field = empty($params['field']) ? '*' : $params['field'];

        return Db::table('region')->selectRaw($field)->where(['pid' => $pid, 'is_enable' => 1])->get();
    }

    //end regionItems()

    /**
     * 获取地区节点数据.
     *
     * @param array $params
     *
     * @return \Hyperf\Utils\Collection
     */
    public static function regionNode(array $params = [])
    {
        $field = empty($params['field']) ? 'id,name,level,letters' : $params['field'];
        $where = empty($params['where']) ? [] : $params['where'];
        $where['is_enable'] = 1;

        return Db::table('region')->where($where)->selectRaw($field)->orderByRaw('id asc, sort asc')->get();
    }

    //end regionNode()

    /**
     * 获取地区节点数据.
     *
     * @param array $params
     *
     * @return array
     */
    public static function regionNodeSon(array $params = [])
    {
        // id
        $id = isset($params['id']) ? (int) ($params['id']) : 0;

        // 获取数据
        $field = 'id,pid,name,sort,is_enable';
        $data = Db::table('region')->selectRaw($field)->where(['pid' => $id])->orderByRaw('sort asc')->get();
        if (! empty($data)) {
            foreach ($data as &$v) {
                $v->is_son = (Db::table('region')->where(['pid' => $v->id])->count() > 0) ? 'ok' : 'no';
                $v->json = json_encode($v);
            }

            return dataReturn('操作成功', 0, $data);
        }

        return dataReturn('没有相关数据', -100);
    }

    //end regionNodeSon()

    /**
     * 地区保存.
     *
     * @param array $params
     *
     * @return array
     */
    public static function regionSave(array $params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'length',
                'key_name' => 'name',
                'checked_data' => '2,16',
                'error_msg' => '名称格式 2~16 个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 数据
        $data = [
            'name' => $params['name'],
            'pid' => isset($params['pid']) ? (int) ($params['pid']) : 0,
            'sort' => isset($params['sort']) ? (int) ($params['sort']) : 0,
            'is_enable' => isset($params['is_enable']) ? (int) ($params['is_enable']) : 0,
        ];

        // 添加
        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('region')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }

            return dataReturn('添加失败', -100);
        }

        $data['upd_time'] = time();
        if (Db::table('region')->where(['id' => (int) ($params['id'])])->update($data) === 1) {
            return dataReturn('编辑成功', 0);
        }

        return dataReturn('编辑失败', -100);
    }

    //end regionSave()

    /**
     * 地区删除.
     *
     * @param array $params
     *
     * @return array
     */
    public static function regionDelete(array $params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'empty',
                'key_name' => 'id',
                'error_msg' => '删除数据id有误',
            ],
            [
                'checked_type' => 'empty',
                'key_name' => 'admin',
                'error_msg' => '用户信息有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 是否还有子数据
        $temp_count = Db::table('region')->where(['pid' => $params['id']])->count();
        if ($temp_count > 0) {
            return dataReturn('请先删除子数据', -10);
        }

        // 开始删除
        if (Db::table('region')->where(['id' => $params['id']])->delete() === 1) {
            return dataReturn('删除成功', 0);
        }

        return dataReturn('删除失败', -100);
    }

    //end regionDelete()
}//end class
