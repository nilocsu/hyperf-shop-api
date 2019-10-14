<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @see     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 *
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property $id
 * @property $alipay_openid
 * @property $weixin_openid
 * @property $baidu_openid
 * @property $status
 * @property $salt
 * @property $pwd
 * @property $username
 * @property $nickname
 * @property $mobile
 * @property $email
 * @property $gender
 * @property $avatar
 * @property $province
 * @property $city
 * @property $birthday
 * @property $address
 * @property $integral
 * @property $locking_integral
 * @property $referrer
 * @property $is_delete_time
 * @property $add_time
 * @property $upd_time
 */
class User extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'default';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'alipay_openid', 'weixin_openid', 'baidu_openid', 'status', 'salt', 'pwd', 'username', 'nickname', 'mobile', 'email', 'gender', 'avatar', 'province', 'city', 'birthday', 'address', 'integral', 'locking_integral', 'referrer', 'is_delete_time', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'status' => 'integer', 'gender' => 'integer', 'birthday' => 'integer', 'integral' => 'integer', 'locking_integral' => 'integer', 'referrer' => 'integer', 'is_delete_time' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
