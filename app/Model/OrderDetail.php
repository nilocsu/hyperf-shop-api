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
 * @property $goods_id
 * @property $shop_id
 * @property $title
 * @property $images
 * @property $original_price
 * @property $price
 * @property $total_price
 * @property $spec
 * @property $buy_number
 * @property $model
 * @property $spec_weight
 * @property $spec_coding
 * @property $spec_barcode
 * @property $refund_price
 * @property $returned_quantity
 * @property $add_time
 * @property $upd_time
 */
class OrderDetail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_detail';

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
    protected $fillable = ['id', 'user_id', 'order_id', 'goods_id', 'shop_id', 'title', 'images', 'original_price', 'price', 'total_price', 'spec', 'buy_number', 'model', 'spec_weight', 'spec_coding', 'spec_barcode', 'refund_price', 'returned_quantity', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'order_id' => 'integer', 'goods_id' => 'integer', 'shop_id' => 'integer', 'original_price' => 'float', 'price' => 'float', 'total_price' => 'float', 'buy_number' => 'integer', 'spec_weight' => 'float', 'refund_price' => 'float', 'returned_quantity' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
