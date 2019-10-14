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
 * @property $user_id
 * @property $alias
 * @property $name
 * @property $tel
 * @property $province
 * @property $city
 * @property $county
 * @property $address
 * @property $lng
 * @property $lat
 * @property $is_default
 * @property $is_delete_time
 * @property $add_time
 * @property $upd_time
 */
class UserAddress extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_address';

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
    protected $fillable = ['id', 'user_id', 'alias', 'name', 'tel', 'province', 'city', 'county', 'address', 'lng', 'lat', 'is_default', 'is_delete_time', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'province' => 'integer', 'city' => 'integer', 'county' => 'integer', 'lng' => 'float', 'lat' => 'float', 'is_default' => 'integer', 'is_delete_time' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
