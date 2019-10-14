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
 * @property $goods_id
 * @property $title
 * @property $images
 * @property $original_price
 * @property $price
 * @property $stock
 * @property $spec
 * @property $add_time
 * @property $upd_time
 */
class Cart extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cart';

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
    protected $fillable = ['id', 'user_id', 'goods_id', 'title', 'images', 'original_price', 'price', 'stock', 'spec', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'goods_id' => 'integer', 'original_price' => 'float', 'price' => 'float', 'stock' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
