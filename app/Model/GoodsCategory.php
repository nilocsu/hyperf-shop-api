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
 * @property $pid
 * @property $icon
 * @property $name
 * @property $vice_name
 * @property $describe
 * @property $bg_color
 * @property $big_images
 * @property $is_home_recommended
 * @property $sort
 * @property $is_enable
 * @property $seo_title
 * @property $seo_keywords
 * @property $seo_desc
 * @property $add_time
 * @property $upd_time
 */
class GoodsCategory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'goods_category';

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
    protected $fillable = ['id', 'pid', 'icon', 'name', 'vice_name', 'describe', 'bg_color', 'big_images', 'is_home_recommended', 'sort', 'is_enable', 'seo_title', 'seo_keywords', 'seo_desc', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'pid' => 'integer', 'is_home_recommended' => 'integer', 'sort' => 'integer', 'is_enable' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
