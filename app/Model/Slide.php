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
 * @property $bg_color
 * @property $is_enable
 * @property $sort
 * @property $add_time
 * @property $upd_time
 */
class Slide extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'slide';

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
    protected $fillable = ['id', 'platform', 'event_type', 'event_value', 'images_url', 'name', 'bg_color', 'is_enable', 'sort', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'event_type' => 'integer', 'is_enable' => 'integer', 'sort' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
