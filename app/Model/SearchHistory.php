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
 * @property $brand_id
 * @property $category_id
 * @property $keywords
 * @property $screening_price
 * @property $order_by_field
 * @property $order_by_type
 * @property $ymd
 * @property $add_time
 */
class SearchHistory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'search_history';

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
    protected $fillable = ['id', 'user_id', 'brand_id', 'category_id', 'keywords', 'screening_price', 'order_by_field', 'order_by_type', 'ymd', 'add_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'brand_id' => 'integer', 'category_id' => 'integer', 'ymd' => 'integer', 'add_time' => 'integer'];
}
