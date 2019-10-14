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
 * @property $order_no
 * @property $order_detail_id
 * @property $order_id
 * @property $goods_id
 * @property $user_id
 * @property $status
 * @property $type
 * @property $refundment
 * @property $reason
 * @property $number
 * @property $price
 * @property $msg
 * @property $images
 * @property $refuse_reason
 * @property $express_name
 * @property $express_number
 * @property $apply_time
 * @property $confirm_time
 * @property $delivery_time
 * @property $audit_time
 * @property $cancel_time
 * @property $add_time
 * @property $upd_time
 */
class OrderAftersale extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_aftersale';

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
    protected $fillable = ['id', 'order_no', 'order_detail_id', 'order_id', 'goods_id', 'user_id', 'status', 'type', 'refundment', 'reason', 'number', 'price', 'msg', 'images', 'refuse_reason', 'express_name', 'express_number', 'apply_time', 'confirm_time', 'delivery_time', 'audit_time', 'cancel_time', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'order_detail_id' => 'integer', 'order_id' => 'integer', 'goods_id' => 'integer', 'user_id' => 'integer', 'status' => 'integer', 'type' => 'integer', 'refundment' => 'integer', 'number' => 'integer', 'price' => 'float', 'apply_time' => 'integer', 'confirm_time' => 'integer', 'delivery_time' => 'integer', 'audit_time' => 'integer', 'cancel_time' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
