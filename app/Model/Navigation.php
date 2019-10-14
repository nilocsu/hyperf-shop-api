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
 * @property $pid
 * @property $name
 * @property $url
 * @property $value
 * @property $data_type
 * @property $nav_type
 * @property $sort
 * @property $is_show
 * @property $is_new_window_open
 * @property $add_time
 * @property $upd_time
 */
class Navigation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'navigation';

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
    protected $fillable = ['id', 'pid', 'name', 'url', 'value', 'data_type', 'nav_type', 'sort', 'is_show', 'is_new_window_open', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'pid' => 'integer', 'value' => 'integer', 'sort' => 'integer', 'is_show' => 'integer', 'is_new_window_open' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
