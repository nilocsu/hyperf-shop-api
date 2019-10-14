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

use App\Exception\ValidateException;
use App\Exception\ValueIndexException;
use App\Model\Power;
use Hyperf\DbConnection\Db;

/**
 * 权限服务层
 */
class AdminPowerService
{
    /**
     * 权限菜单列表.
     *
     * @param array $params
     *
     * @return \Hyperf\Utils\Collection
     */
    public function powerList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $field    = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id asc' : trim($params['order_by']);

        $data = Power::query()->where($where)->orderBy($order_by)->get();
        // * @var  Power $v */
        foreach ($data as $v) {
            $v->item = $v->power()->selectRaw($field)->get();
        }

        return $data;
    }

    //end powerList()

    /**
     * 权限菜单保存.
     *
     * @param array $params
     *
     * @throws ValidateException
     *
     * @return bool
     */
    public function powerSave($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'name',
                'error_msg'    => '权限名称不能为空',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'name',
                'checked_data' => '2,16',
                'error_msg'    => '权限名称格式 2~16 个字符之间',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'control',
                'error_msg'    => '控制器名称不能为空',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'control',
                'checked_data' => '1,30',
                'error_msg'    => '控制器名称格式 1~30 个字符之间',
            ],
            [
                'checked_type' => 'empty',
                'key_name'     => 'action',
                'error_msg'    => '方法名称不能为空',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'action',
                'checked_data' => '1,30',
                'error_msg'    => '方法名称格式 1~30 个字符之间',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'icon',
                'checked_data' => '60',
                'is_checked'   => 1,
                'error_msg'    => '图标格式 0~30 个字符之间',
            ],
            [
                'checked_type' => 'in',
                'key_name'     => 'is_show',
                'checked_data' => [
                    0,
                    1,
                ],
                'error_msg'    => '是否显示范围值有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            throw new ValidateException($ret, -1);
        }

        // 保存数据
        $data = [
            'pid'     => isset($params['pid']) ? intval($params['pid']) : 0,
            'sort'    => isset($params['sort']) ? intval($params['sort']) : 0,
            'icon'    => isset($params['icon']) ? $params['icon'] : '',
            'name'    => $params['name'],
            'control' => $params['control'],
            'action'  => $params['action'],
            'is_show' => isset($params['is_show']) ? intval($params['is_show']) : 0,
        ];
        if (empty($params['id'])) {
            $data['add_time'] = time();
            if (Db::table('power')->insertGetId($data) > 0) {
                // 清除用户权限数据
                // self::PowerCacheDelete();
                return dataReturn('添加成功', 0);
            }

            return dataReturn('添加失败', -100);
        }

        if (Db::table('power')->where(['id' => intval($params['id'])])->update($data) === 1) {
            // 清除用户权限数据
            // self::PowerCacheDelete();
            return dataReturn('更新成功', 0);
        }

        return dataReturn('更新失败', -100);
    }

    //end powerSave()

    /**
     * 权限菜单删除.
     *
     * @param array $params
     *
     * @throws ValueIndexException
     *
     * @return mixed
     */
    public function powerDelete($params = [])
    {
        // 参数是否有误
        if (empty($params['id'])) {
            throw new ValueIndexException('权限菜单id有误', -1);
        }

        if (Db::table('power')->delete(intval($params['id'])) === 1) {
            // 清除用户权限数据
            // self::PowerCacheDelete();
            return dataReturn('删除成功', 0);
        }

        return dataReturn('删除失败', -100);
    }

    //end powerDelete()

    /**
     * 角色列表.
     *
     * @param array $params
     *
     * @return \Hyperf\Utils\Collection
     */
    public static function roleList($params = [])
    {
        $where    = empty($params['where']) ? [] : $params['where'];
        $field    = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id' : trim($params['order_by']);

        // 获取管理员列表
        $data = Db::table('role')->where($where)->orderBy($order_by)->get(explode(',', $field));
        if (!empty($data)) {
            foreach ($data as &$v) {
                // 关联查询权限和角色数据
                if ($v->id == 1) {
                    $v->tiem = Db::table('power')->get();
                } else {
                    $v->tiem = Db::table('role')->join('role_power', 'role_power.role_id', '=',
                        'role.id')->join('power', 'role_power.power_id', '=',
                        'power.id')->where(['role.id' => $v->id])->get(['power.id', 'power.name']);
                }
            }
        }

        return $data;
    }

    //end roleList()

    /**
     * 角色状态更新.
     *
     * @param array $params
     *
     * @throws ValueIndexException
     *
     * @return array
     */
    public function roleStatusUpdate($params = [])
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
            throw new ValueIndexException($ret, -1);
        }

        // 数据更新
        if (Db::table('Role')->where(['id' => intval($params['id'])])->update(['is_enable' => intval($params['state'])]) === 1) {
            return dataReturn('编辑成功');
        }

        return dataReturn('编辑失败或数据未改变', -100);
    }

    //end roleStatusUpdate()

    /**
     * 权限菜单编辑列表.
     *
     * @param array $params
     *
     * @return \Hyperf\Utils\Collection
     */
    public function rolePowerEditData($params = [])
    {
        // 当前角色关联的所有菜单id
        $action = empty($params['role_id']) ? [] : Db::table('role_power')->where(['role_id' => $params['role_id']])->pluck('power_id');

        // 权限列表
        $power_field = [
            'id',
            'name',
            'is_show',
        ];
        $power       = Db::table('power')->where(['pid' => 0])->orderBy('sort')->get($power_field);
        if (!empty($power)) {
            foreach ($power as &$v) {
                // 是否有权限
                $v['is_power'] = in_array($v['id'], $action) ? 'ok' : 'no';

                // 获取子权限
                $item = Db::table('power')->where(['pid' => $v->id])->orderBy('sort')->get($power_field);
                if (!empty($item)) {
                    foreach ($item as $vs) {
                        $vs->is_power = in_array($vs->id, $action) ? 'ok' : 'no';
                    }

                    $v->item = $item;
                }
            }
        }

        return $power;
    }

    //end rolePowerEditData()

    /**
     * 角色保存.
     *
     * @param array $params
     *
     * @return array
     */
    public static function roleSave($params = [])
    {
        // 请求参数
        $p   = [
            [
                'checked_type' => 'empty',
                'key_name'     => 'name',
                'error_msg'    => '角色名称不能为空',
            ],
            [
                'checked_type' => 'length',
                'key_name'     => 'name',
                'checked_data' => '2,8',
                'error_msg'    => '角色名称格式 2~8 个字符之间',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        Db::beginTransaction();

        try {
            // 角色数据更新
            $role_data = [
                'name'      => $params['name'],
                'is_enable' => isset($params['is_enable']) ? intval($params['is_enable']) : 0,
            ];
            if (empty($params['id'])) {
                $role_data['add_time'] = time();
                $role_id               = Db::table('role')->insertGetId($role_data);
            } else {
                Db::table('Role')->where(['id' => $params['id']])->update($role_data);
                $role_id = $params['id'];
            }

            // 权限关联数据删除
            Db::table('role_power')->where(['role_id' => $role_id])->delete();

            // 权限关联数据添加
            if (!empty($params['power_id'])) {
                $rp_data = [];
                foreach (explode(',', $params['power_id']) as $power_id) {
                    $rp_data[] = [
                        'role_id'  => $role_id,
                        'power_id' => $power_id,
                        'add_time' => time(),
                    ];
                }

                Db::table('role_power')->insert($rp_data);
            }

            // 提交事务
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();

            return dataReturn('角色权限操作失败', -3);
        }//end try

        // todo:清除用户权限数据
        // self::PowerCacheDelete();
        return dataReturn('操作成功', 0);
    }

    //end roleSave()

    public function roleDelete($params = [])
    {
        // 参数是否有误
        if (empty($params['id'])) {
            return dataReturn('角色id有误', -1);
        }

        // 开启事务
        Db::beginTransaction();

        try {
            // 删除角色
            Db::table('role')->delete(intval($params['id']));
            Db::table('role_power')->where(['role_id' => intval($params['id'])])->delete();
            // 提交事务
            Db::commit();

            // todo 清除用户权限数据
            // self::PowerCacheDelete();
            return dataReturn('删除成功', 0);
        } catch (\Throwable $e) {
            Db::rollback();

            return dataReturn('删除失败', -100);
        }
    }

    //end roleDelete()

    // todo
    public function powerMenuInit($admin_id, $role_id = 0)
    {
        // 基础参数
        // $admin = session('admin');
        // $admin_id = isset($admin['id']) ? intval($admin['id']) : 0;
        // $role_id = isset($admin['role_id']) ? intval($admin['role_id']) : 0;
        // 读取缓存数据
        // $admin_left_menu = cache(config('cache_admin_left_menu_key').$admin_id);
        // $admin_power = cache(config('cache_admin_power_key').$admin_id);
        // 缓存没数据则从数据库重新读取
        if (($role_id > 0 || $admin_id == 1) && empty($admin_left_menu)) {
            // 获取一级数据
            if ($admin_id == 1 || $role_id == 1) {
                $field           = 'id,name,control,action,is_show,icon';
                $admin_left_menu = Db::table('power')->where(['pid' => 0])->orderBy('sort')->get();
            } else {
                $field           = 'power.*';
                $admin_left_menu = Db::table('power')->join('role_power', 'power.id', '=',
                    'role_power.power_id')->where([
                    'role_power.role_id' => $role_id,
                    'power.pid'          => 0,
                ])->orderBy('power.sort')->selectRaw($field)->get();
            }

            // 有数据，则处理子级数据
            if (!empty($admin_left_menu)) {
                $items = '';
                foreach ($admin_left_menu as $k => $v) {
                    // 权限
                    $admin_power[$v['id']] = strtolower($v['control'] . '_' . $v['action']);

                    // 获取子权限
                    if ($admin_id == 1 || $role_id == 1) {
                        $items = Db::table('power')->where(['pid' => $v['id']])->orderBy('sort')->get();
                    } else {
                        $admin_left_menu = Db::table('power')->join('role_power', 'power.id', '=',
                            'role_power.power_id')->where([
                            'role_power.role_id' => $role_id,
                            'power.pid'          => 0,
                        ])->orderBy('power.sort')->selectRaw($field)->get();
                    }

                    // 权限列表
                    if (!empty($items)) {
                        foreach ($items as $ks => $vs) {
                            // 权限
                            $admin_power[$vs['id']] = strtolower($vs['control'] . '_' . $vs['action']);

                            // 是否显示视图
                            if ($vs['is_show'] == 0) {
                                unset($items[$ks]);
                            }
                        }
                    }

                    // 是否显示视图
                    if ($v['is_show'] == 1) {
                        // 子级
                        $admin_left_menu[$k]['items'] = $items;
                    } else {
                        unset($admin_left_menu[$k]);
                    }
                }//end foreach
            }//end if
        }//end if
    }

    //end powerMenuInit()
}//end class
