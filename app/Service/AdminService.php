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

class AdminService
{
    /**
     * 管理员列表.
     *
     * @param array $params
     *
     * @return \Hyperf\Utils\Collection
     */
    public function adminList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];
        $order_by = empty($params['order_by']) ? 'id' : trim($params['order_by']);

        $m = isset($params['m']) ? (int) ($params['m']) : 0;
        $n = isset($params['n']) ? (int) ($params['n']) : 10;

        // 获取管理员列表
        $data = Db::table('admin')->where($where)->orderBy($order_by)->limit($n)->offset($m)->get($field);
        if (! empty($data)) {
            foreach ($data as &$v) {
                $v->role_name = Db::table('role')->where(['id' => $v->role_id])->value('name');
            }
        }

        return $data;
    }

    //end adminList()

    /**
     * 管理员列表条件.
     *
     * @param array $params
     *
     * @return array
     */
    public static function adminListWhere($params = [])
    {
        $where = [];
        if (! empty($params['keywords'])) {
            $where[] = [
                'username|mobile',
                'like',
                '%' . $params['keywords'] . '%',
            ];
        }

        // 是否更多条件
        if (isset($params['is_more']) && $params['is_more'] === 1) {
            if (isset($params['role_id']) && $params['role_id'] > -1) {
                $where[] = [
                    'role_id',
                    '=',
                    (int) ($params['role_id']),
                ];
            }

            // 等值
            if (isset($params['gender']) && $params['gender'] > -1) {
                $where[] = [
                    'gender',
                    '=',
                    (int) ($params['gender']),
                ];
            }

            if (! empty($params['time_start'])) {
                $where[] = [
                    'add_time',
                    '>',
                    strtotime($params['time_start']),
                ];
            }

            if (! empty($params['time_end'])) {
                $where[] = [
                    'add_time',
                    '<',
                    strtotime($params['time_end']),
                ];
            }
        }//end if

        return $where;
    }

    //end adminListWhere()

    /**
     * 管理员总数.
     *
     * @param $where
     *
     * @return int
     */
    public static function adminTotal($where)
    {
        return Db::table('admin')->where($where)->count();
    }

    //end adminTotal()

    /**
     * 角色列表.
     *
     * @param array $params
     *
     * @return \Hyperf\Utils\Collection
     */
    public static function roleList($params = [])
    {
        $where = empty($params['where']) ? [] : $params['where'];
        $field = empty($params['field']) ? '*' : $params['field'];

        return Db::table('role')->where($where)->get($field);
    }

    //end roleList()

    /**
     * 管理员保存.
     *
     * @param array $params
     *
     * @return array
     */
    public function adminSave($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'empty',
                'key_name' => 'admin',
                'error_msg' => '用户信息有误',
            ],
            [
                'checked_type' => 'fun',
                'key_name' => 'mobile',
                'checked_data' => 'CheckMobile',
                'is_checked' => 1,
                'error_msg' => '手机号码格式错误',
            ],
            [
                'checked_type' => 'in',
                'key_name' => 'gender',
                'checked_data' => [
                    0,
                    1,
                    2,
                ],
                'error_msg' => '性别值范围不正确',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        return empty($params['id']) ? $this->adminInsert($params) : $this->adminUpdate($params);
    }

    //end adminSave()

    /**
     * 管理员添加.
     *
     * @param array $params
     *
     * @return array
     */
    public function adminInsert($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'empty',
                'key_name' => 'username',
                'error_msg' => '用户名不能为空',
            ],
            [
                'checked_type' => 'empty',
                'key_name' => 'login_pwd',
                'error_msg' => '密码不能为空',
            ],
            [
                'checked_type' => 'fun',
                'key_name' => 'username',
                'checked_data' => 'CheckUserName',
                'error_msg' => '用户名格式 5~18 个字符（可以是字母数字下划线）',
            ],
            [
                'checked_type' => 'unique',
                'key_name' => 'username',
                'checked_data' => 'Admin',
                'error_msg' => '用户名已存在',
            ],
            [
                'checked_type' => 'fun',
                'key_name' => 'login_pwd',
                'checked_data' => 'CheckLoginPwd',
                'error_msg' => '密码格式 6~18 个字符',
            ],
            [
                'checked_type' => 'empty',
                'key_name' => 'role_id',
                'error_msg' => '角色组有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 添加账号
        $salt = getNumberCode(6);
        $data = [
            'username' => $params['username'],
            'login_salt' => $salt,
            'login_pwd' => loginPwdEncryption($params['login_pwd'], $salt),
            'mobile' => isset($params['mobile']) ? $params['mobile'] : '',
            'gender' => (int) ($params['gender']),
            'role_id' => (int) ($params['role_id']),
            'add_time' => time(),
        ];

        // 添加
        if (Db::table('admin')->insert($data)) {
            return dataReturn('新增成功', 0);
        }

        return dataReturn('新增失败', -100);
    }

    //end adminInsert()

    /**
     * 管理员更新.
     *
     * @param array $params
     *
     * @return array
     */
    public function AdminUpdate($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'fun',
                'key_name' => 'login_pwd',
                'checked_data' => 'CheckLoginPwd',
                'is_checked' => 1,
                'error_msg' => '密码格式 6~18 个字符',
            ],
        ];
        if ($params['id'] !== $params['admin']['id']) {
            $p[] = [
                'checked_type' => 'empty',
                'key_name' => 'role_id',
                'error_msg' => '角色组有误',
            ];
        }

        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 是否非法修改超管
        if ($params['id'] === 1 && $params['id'] !== $params['admin']['id']) {
            return dataReturn('非法操作', -1);
        }

        // 数据
        $data = [
            'mobile' => isset($params['mobile']) ? $params['mobile'] : '',
            'gender' => (int) ($params['gender']),
            'upd_time' => time(),
        ];

        // 密码
        if (! empty($params['login_pwd'])) {
            $data['login_salt'] = getNumberCode(6);
            $data['login_pwd'] = loginPwdEncryption($params['login_pwd'], $data['login_salt']);
        }

        // 不能修改自身所属角色组
        if ($params['id'] !== $params['admin']['id']) {
            $data['role_id'] = (int) ($params['role_id']);
        }

        // 更新
        if (Db::table('admin')->where(['id' => (int) ($params['id'])])->update($data) === 1) {
            // 自己修改密码则重新登录
            // if(!empty($params['login_pwd']) && $params['id'] == $params['admin']['id'])
            // {
            // TODO
            // session_destroy();
            // }
            return dataReturn('编辑成功', 0);
        }

        return dataReturn('编辑失败或数据未改变', -100);
    }

    //end AdminUpdate()

    /**
     * 管理员删除.
     *
     * @param array $params
     *
     * @return array
     */
    public function adminDelete($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'empty',
                'key_name' => 'id',
                'error_msg' => '删除id有误',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 删除操作
        if (Db::table('admin')->delete((int) ($params['id'])) === 1) {
            return dataReturn('删除成功');
        }

        return dataReturn('删除失败或资源不存在', -100);
    }

    //end adminDelete()

    /**
     * 管理员登录.
     *
     * @param array $params
     *
     * @return array
     */
    public function Login($params = [])
    {
        // 请求参数
        $p = [
            [
                'checked_type' => 'empty',
                'key_name' => 'username',
                'error_msg' => '用户名不能为空',
            ],
            [
                'checked_type' => 'empty',
                'key_name' => 'login_pwd',
                'error_msg' => '密码不能为空',
            ],
            [
                'checked_type' => 'fun',
                'key_name' => 'username',
                'checked_data' => 'CheckUserName',
                'error_msg' => '用户名格式 5~18 个字符（可以是字母数字下划线）',
            ],
            [
                'checked_type' => 'fun',
                'key_name' => 'login_pwd',
                'checked_data' => 'CheckLoginPwd',
                'error_msg' => '密码格式 6~18 个字符',
            ],
        ];
        $ret = paramsChecked($params, $p);
        if ($ret !== true) {
            return dataReturn($ret, -1);
        }

        // 获取管理员
        $admin = Db::table('admin')->where(['username' => $params['username']])->first(explode(',', 'id,username,login_pwd,login_salt,mobile,login_total,role_id'));
        if (empty($admin)) {
            return dataReturn('管理员不存在', -2);
        }

        // 密码校验
        $login_pwd = LoginPwdEncryption($params['login_pwd'], $admin['login_salt']);
        if ($login_pwd !== $admin['login_pwd']) {
            return dataReturn('密码错误', -3);
        }

        // 校验成功
        // session存储
        // session('admin', $admin);
        // 返回数据,更新数据库
        // if(session('admin') != null)
        // {
        // $login_salt = GetNumberCode(6);
        // $data = array(
        // 'login_salt'    =>  $login_salt,
        // 'login_pwd'     =>  LoginPwdEncryption($params['login_pwd'], $login_salt),
        // 'login_total'   =>  $admin['login_total']+1,
        // 'login_time'    =>  time(),
        // );
        // if(Db::table('admin')->where(['id'=>$admin['id']])->update($data) == 1)
        // {
        // 清空缓存目录下的数据
        // \base\FileUtil::UnlinkDir(ROOT.'runtime'.DS.'cache');
        //
        // return dataReturn('登录成功');
        // }
        // }
        // 失败
        // session('admin', null);
        return dataReturn('登录失败，请稍后再试！', -100);
    }

    //end Login()
}//end class
