<?php


namespace App\Service;

use Hyperf\DbConnection\Db;

/**
 * 快递服务层
 * @author colin.
 * date 19-6-26 下午6:35
 */
class ExpressService
{
    /**
     * 获取地区名称
     * @param int $express_id
     * @return mixed|null
     */
    public static function expressName($express_id = 0)
    {
        return empty($express_id) ? null : Db::table('express')->where(['id' => intval($express_id)])->value('name');
    }

    /**
     * 快递列表
     * @param array $params
     * @return mixed
     */
    public static function expressList($params = [])
    {
        $where = [];
        if (isset($params['is_enable'])) {
            $where['is_enable'] = intval($params['is_enable']);
        }
        $data = Db::table('express')->where($where)->selectRaw('id,icon,name,sort,is_enable')->orderByRaw('sort asc')->get();
        if (!empty($data) && is_array($data)) {
            foreach ($data as &$v) {
                $v['icon_old'] = $v['icon'];
                $v['icon']     = ResourcesService::AttachmentPathViewHandle($v['icon']);
            }
        }
        return $data;
    }

    /**
     * 获取快递节点数据
     * @param array $params
     * @return array
     */
    public static function expressNodeSon($params = [])
    {
        // id
        $id = isset($params['id']) ? intval($params['id']) : 0;

        // 获取数据
        $field = 'id,pid,icon,name,sort,is_enable';
        $data  = Db::table('express')->selectRaw($field)->where(['pid' => $id])->orderByRaw('sort asc')->get();
        if (!empty($data)) {
            foreach ($data as &$v) {
                $v['is_son'] = (Db::table('express')->where(['pid' => $v['id']])->count() > 0) ? 'ok' : 'no';
//                $v['ajax_url']          =   MyUrl('admin/express/getnodeson', array('id'=>$v['id']));
//                $v['delete_url']        =   MyUrl('admin/express/delete');
                $v['icon_url'] = ResourcesService::AttachmentPathViewHandle($v['icon']);
                $v['json']     = json_encode($v);
            }
            return dataReturn('操作成功', 0, $data);
        }
        return dataReturn('没有相关数据', -100);
    }

    /**
     * 快递保存
     * @param array $params
     * @return array
     */
    public static function expressSave($params = [])
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

        // 其它附件
        $data_fields = ['icon'];
        $attachment  = ResourcesService::attachmentParams($params, $data_fields);
        if ($attachment['code'] != 0) {
            return $attachment;
        }

        // 数据
        $data = [
            'name'      => $params['name'],
            'pid'       => isset($params['pid']) ? intval($params['pid']) : 0,
            'sort'      => isset($params['sort']) ? intval($params['sort']) : 0,
            'is_enable' => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            'icon'      => $attachment['data']['icon'],
        ];

        // 添加
        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('express')->insertGetId($data) > 0) {
                return dataReturn('添加成功', 0);
            }
            return dataReturn('添加失败', -100);
        } else {
            $data['upd_time'] = time();
            if (Db::table('express')->where(['id' => intval($params['id'])])->update($data)) {
                return dataReturn('编辑成功', 0);
            }
            return dataReturn('编辑失败', -100);
        }
    }

    /**
     * 快递删除
     * @param array $params
     * @return array
     */
    public static function expressDelete($params = [])
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
        if (Db::table('express')->where(['id' => intval($params['id'])])->delete() == 1) {
            return dataReturn('删除成功', 0);
        }
        return dataReturn('删除失败', -100);
    }

}