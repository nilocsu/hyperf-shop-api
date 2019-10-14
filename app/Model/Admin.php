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
 * @property $username
 * @property $login_pwd
 * @property $login_salt
 * @property $mobile
 * @property $gender
 * @property $login_total
 * @property $login_time
 * @property $role_id
 * @property $add_time
 * @property $upd_time
 */
class Admin extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin';

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
    protected $fillable = ['id', 'username', 'login_pwd', 'login_salt', 'mobile', 'gender', 'login_total', 'login_time', 'role_id', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'gender' => 'integer', 'login_total' => 'integer', 'login_time' => 'integer', 'role_id' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
