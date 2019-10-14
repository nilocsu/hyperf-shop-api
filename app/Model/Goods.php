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
 * @property $brand_id
 * @property $title
 * @property $title_color
 * @property $simple_desc
 * @property $model
 * @property $place_origin
 * @property $inventory
 * @property $inventory_unit
 * @property $images
 * @property $original_price
 * @property $min_original_price
 * @property $max_original_price
 * @property $price
 * @property $min_price
 * @property $max_price
 * @property $give_integral
 * @property $buy_min_number
 * @property $buy_max_number
 * @property $is_deduction_inventory
 * @property $is_shelves
 * @property $is_home_recommended
 * @property $content_web
 * @property $photo_count
 * @property $sales_count
 * @property $access_count
 * @property $video
 * @property $home_recommended_images
 * @property $seo_title
 * @property $seo_keywords
 * @property $seo_desc
 * @property $is_delete_time
 * @property $add_time
 * @property $upd_time
 */
class Goods extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'goods';

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
    protected $fillable = ['id', 'brand_id', 'title', 'title_color', 'simple_desc', 'model', 'place_origin', 'inventory', 'inventory_unit', 'images', 'original_price', 'min_original_price', 'max_original_price', 'price', 'min_price', 'max_price', 'give_integral', 'buy_min_number', 'buy_max_number', 'is_deduction_inventory', 'is_shelves', 'is_home_recommended', 'content_web', 'photo_count', 'sales_count', 'access_count', 'video', 'home_recommended_images', 'seo_title', 'seo_keywords', 'seo_desc', 'is_delete_time', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'brand_id' => 'integer', 'place_origin' => 'integer', 'inventory' => 'integer', 'min_original_price' => 'float', 'max_original_price' => 'float', 'min_price' => 'float', 'max_price' => 'float', 'give_integral' => 'integer', 'buy_min_number' => 'integer', 'buy_max_number' => 'integer', 'is_deduction_inventory' => 'integer', 'is_shelves' => 'integer', 'is_home_recommended' => 'integer', 'photo_count' => 'integer', 'sales_count' => 'integer', 'access_count' => 'integer', 'is_delete_time' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
