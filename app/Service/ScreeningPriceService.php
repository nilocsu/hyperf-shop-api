<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 筛选价格服务层
 * @author colin.
 * date 19-7-2 下午4:10
 */

class ScreeningPriceService
{
    /**
     * 获取节点数据
     * 
     * @param    [array]          $params [输入参数]
     * @return array
     */
    public static function screeningPriceNodeSon($params = [])
    {
        // id
        $id = isset($params['id']) ? intval($params['id']) : 0;

        // 获取数据
        $field = 'id,name,sort,is_enable,min_price,max_price';
        $data = Db::table('screening_price')->selectRaw($field)->where(['pid'=>$id])->orderByRaw('sort asc')->get()->toArray();
        if(!empty($data))
        {
            foreach($data as &$v)
            {
                $v['is_son']            =   (Db::table('screening_price')->where(['pid'=>$v['id']])->count() > 0) ? 'ok' : 'no';
                $v['json']              =   json_encode($v);
            }
            return dataReturn('操作成功', 0, $data);
        }
        return dataReturn('没有相关数据', -100);
    }

    /**
     * @param array $params
     * @return array
     */
    public static function screeningPriceSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'length',
                'key_name'          => 'name',
                'checked_data'      => '2,16',
                'error_msg'         => '名称格式 2~16 个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if($ret !== true)
        {
            return dataReturn($ret, -1);
        }

        // 数据
        $data = [
            'name'                  => $params['name'],
            'pid'                   => isset($params['pid']) ? intval($params['pid']) : 0,
            'min_price'             => intval($params['min_price']),
            'max_price'             => intval($params['max_price']),
            'sort'                  => isset($params['sort']) ? intval($params['sort']) : 0,
            'is_enable'             => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
        ];

        // 添加
        if(empty($params['id']))
        {
            $data['add_time'] = time();
            if(Db::table('screening_price')->insertGetId($data) > 0)
            {
                return dataReturn('添加成功', 0);
            }
            return dataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if(Db::table('screening_price')->where(['id'=>intval($params['id'])])->update($data))
            {
                return dataReturn('编辑成功', 0);
            }
            return dataReturn('编辑失败', -100);
        }
    }

    /**
     * @param array $params
     * @return array
     */
    public static function screeningPriceDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type'      => 'empty',
                'key_name'          => 'id',
                'error_msg'         => '删除数据id有误',
            ],
            [
                'checked_type'      => 'empty',
                'key_name'          => 'admin',
                'error_msg'         => '用户信息有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if($ret !== true)
        {
            return dataReturn($ret, -1);
        }

        // 开始删除
        if(Db::table('screening_price')->where(['id'=>intval($params['id'])])->delete() == 1)
        {
            return dataReturn('删除成功', 0);
        }
        return dataReturn('删除失败', -100);
    }

}