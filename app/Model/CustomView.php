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
 * @property $title
 * @property $content
 * @property $is_enable
 * @property $is_header
 * @property $is_footer
 * @property $is_full_screen
 * @property $image
 * @property $image_count
 * @property $access_count
 * @property $add_time
 * @property $upd_time
 */
class CustomView extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'custom_view';

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
    protected $fillable = ['id', 'title', 'content', 'is_enable', 'is_header', 'is_footer', 'is_full_screen', 'image', 'image_count', 'access_count', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'is_enable' => 'integer', 'is_header' => 'integer', 'is_footer' => 'integer', 'is_full_screen' => 'integer', 'image_count' => 'integer', 'access_count' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
