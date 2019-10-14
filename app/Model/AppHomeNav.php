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
 * @property $platform
 * @property $event_type
 * @property $event_value
 * @property $images_url
 * @property $name
 * @property $is_enable
 * @property $is_need_login
 * @property $bg_color
 * @property $sort
 * @property $add_time
 * @property $upd_time
 */
class AppHomeNav extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'app_home_nav';

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
    protected $fillable = ['id', 'platform', 'event_type', 'event_value', 'images_url', 'name', 'is_enable', 'is_need_login', 'bg_color', 'sort', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'event_type' => 'integer', 'is_enable' => 'integer', 'is_need_login' => 'integer', 'sort' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
