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
 * @property $brand_category_id
 * @property $logo
 * @property $name
 * @property $website_url
 * @property $is_enable
 * @property $sort
 * @property $add_time
 * @property $upd_time
 */
class Brand extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'brand';

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
    protected $fillable = ['id', 'brand_category_id', 'logo', 'name', 'website_url', 'is_enable', 'sort', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'brand_category_id' => 'integer', 'is_enable' => 'integer', 'sort' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
