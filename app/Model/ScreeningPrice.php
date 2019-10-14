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
 * @property $min_price
 * @property $max_price
 * @property $is_enable
 * @property $sort
 * @property $add_time
 * @property $upd_time
 */
class ScreeningPrice extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'screening_price';

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
    protected $fillable = ['id', 'pid', 'name', 'min_price', 'max_price', 'is_enable', 'sort', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'pid' => 'integer', 'min_price' => 'integer', 'max_price' => 'integer', 'is_enable' => 'integer', 'sort' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
