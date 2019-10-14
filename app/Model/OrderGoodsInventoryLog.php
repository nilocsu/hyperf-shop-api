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
 * @property $order_id
 * @property $goods_id
 * @property $order_status
 * @property $original_inventory
 * @property $new_inventory
 * @property $is_rollback
 * @property $rollback_time
 * @property $add_time
 */
class OrderGoodsInventoryLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_goods_inventory_log';

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
    protected $fillable = ['id', 'order_id', 'goods_id', 'order_status', 'original_inventory', 'new_inventory', 'is_rollback', 'rollback_time', 'add_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'order_id' => 'integer', 'goods_id' => 'integer', 'order_status' => 'integer', 'original_inventory' => 'integer', 'new_inventory' => 'integer', 'is_rollback' => 'integer', 'rollback_time' => 'integer', 'add_time' => 'integer'];
}
