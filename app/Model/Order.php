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
 * @property $user_id
 * @property $shop_id
 * @property $receive_address_id
 * @property $receive_name
 * @property $receive_tel
 * @property $receive_province
 * @property $receive_city
 * @property $receive_county
 * @property $receive_address
 * @property $user_note
 * @property $express_id
 * @property $express_number
 * @property $payment_id
 * @property $status
 * @property $pay_status
 * @property $extension_data
 * @property $buy_number_count
 * @property $increase_price
 * @property $preferential_price
 * @property $price
 * @property $total_price
 * @property $pay_price
 * @property $refund_price
 * @property $returned_quantity
 * @property $client_type
 * @property $pay_time
 * @property $confirm_time
 * @property $delivery_time
 * @property $cancel_time
 * @property $collect_time
 * @property $close_time
 * @property $comments_time
 * @property $is_comments
 * @property $user_is_comments
 * @property $is_delete_time
 * @property $user_is_delete_time
 * @property $add_time
 * @property $upd_time
 */
class Order extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order';

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
    protected $fillable = ['id', 'order_no', 'user_id', 'shop_id', 'receive_address_id', 'receive_name', 'receive_tel', 'receive_province', 'receive_city', 'receive_county', 'receive_address', 'user_note', 'express_id', 'express_number', 'payment_id', 'status', 'pay_status', 'extension_data', 'buy_number_count', 'increase_price', 'preferential_price', 'price', 'total_price', 'pay_price', 'refund_price', 'returned_quantity', 'client_type', 'pay_time', 'confirm_time', 'delivery_time', 'cancel_time', 'collect_time', 'close_time', 'comments_time', 'is_comments', 'user_is_comments', 'is_delete_time', 'user_is_delete_time', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'shop_id' => 'integer', 'receive_address_id' => 'integer', 'receive_province' => 'integer', 'receive_city' => 'integer', 'receive_county' => 'integer', 'express_id' => 'integer', 'payment_id' => 'integer', 'status' => 'integer', 'pay_status' => 'integer', 'buy_number_count' => 'integer', 'increase_price' => 'float', 'preferential_price' => 'float', 'price' => 'float', 'total_price' => 'float', 'pay_price' => 'float', 'refund_price' => 'float', 'returned_quantity' => 'integer', 'pay_time' => 'integer', 'confirm_time' => 'integer', 'delivery_time' => 'integer', 'cancel_time' => 'integer', 'collect_time' => 'integer', 'close_time' => 'integer', 'comments_time' => 'integer', 'is_comments' => 'integer', 'user_is_comments' => 'integer', 'is_delete_time' => 'integer', 'user_is_delete_time' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
