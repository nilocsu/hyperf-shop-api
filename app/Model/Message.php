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
 * @property $title
 * @property $detail
 * @property $business_id
 * @property $business_type
 * @property $type
 * @property $is_read
 * @property $is_delete_time
 * @property $user_is_delete_time
 * @property $add_time
 */
class Message extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'message';

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
    protected $fillable = ['id', 'user_id', 'title', 'detail', 'business_id', 'business_type', 'type', 'is_read', 'is_delete_time', 'user_is_delete_time', 'add_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'business_id' => 'integer', 'business_type' => 'integer', 'type' => 'integer', 'is_read' => 'integer', 'is_delete_time' => 'integer', 'user_is_delete_time' => 'integer', 'add_time' => 'integer'];
}
