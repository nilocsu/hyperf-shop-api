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
 * @property $pay_price
 * @property $total_price
 * @property $subject
 * @property $payment
 * @property $payment_name
 * @property $business_type
 * @property $add_time
 */
class PayLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pay_log';

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
    protected $fillable = ['id', 'user_id', 'order_id', 'trade_no', 'buyer_user', 'pay_price', 'total_price', 'subject', 'payment', 'payment_name', 'business_type', 'add_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'order_id' => 'integer', 'pay_price' => 'float', 'total_price' => 'float', 'business_type' => 'integer', 'add_time' => 'integer'];
}
