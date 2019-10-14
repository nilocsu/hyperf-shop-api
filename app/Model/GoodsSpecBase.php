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
 * @property $goods_id
 * @property $price
 * @property $inventory
 * @property $weight
 * @property $coding
 * @property $barcode
 * @property $original_price
 * @property $add_time
 */
class GoodsSpecBase extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'goods_spec_base';

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
    protected $fillable = ['id', 'goods_id', 'price', 'inventory', 'weight', 'coding', 'barcode', 'original_price', 'add_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'goods_id' => 'integer', 'price' => 'float', 'inventory' => 'integer', 'weight' => 'float', 'original_price' => 'float', 'add_time' => 'integer'];
}
