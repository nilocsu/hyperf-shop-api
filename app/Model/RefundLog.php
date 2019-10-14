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
 * @property $order_id
 * @property $trade_no
 * @property $buyer_user
 * @property $refund_price
 * @property $pay_price
 * @property $msg
 * @property $payment
 * @property $payment_name
 * @property $refundment
 * @property $business_type
 * @property $return_params
 * @property $add_time
 */
class RefundLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'refund_log';

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
    protected $fillable = ['id', 'user_id', 'order_id', 'trade_no', 'buyer_user', 'refund_price', 'pay_price', 'msg', 'payment', 'payment_name', 'refundment', 'business_type', 'return_params', 'add_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'order_id' => 'integer', 'refund_price' => 'float', 'pay_price' => 'float', 'refundment' => 'integer', 'business_type' => 'integer', 'add_time' => 'integer'];
}
