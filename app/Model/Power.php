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
 * @property $control
 * @property $action
 * @property $sort
 * @property $is_show
 * @property $icon
 * @property $add_time
 */
class Power extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'power';

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
    protected $fillable = ['id', 'pid', 'name', 'control', 'action', 'sort', 'is_show', 'icon', 'add_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'pid' => 'integer', 'sort' => 'integer', 'is_show' => 'integer', 'add_time' => 'integer'];

    public function power()
    {
        return $this->hasMany(self::class, 'pid', 'id');
    }
}
