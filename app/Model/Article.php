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
 * @property $article_category_id
 * @property $title_color
 * @property $jump_url
 * @property $is_enable
 * @property $content
 * @property $image
 * @property $image_count
 * @property $access_count
 * @property $is_home_recommended
 * @property $seo_title
 * @property $seo_keywords
 * @property $seo_desc
 * @property $add_time
 * @property $upd_time
 */
class Article extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'article';

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
    protected $fillable = ['id', 'title', 'article_category_id', 'title_color', 'jump_url', 'is_enable', 'content', 'image', 'image_count', 'access_count', 'is_home_recommended', 'seo_title', 'seo_keywords', 'seo_desc', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'article_category_id' => 'integer', 'is_enable' => 'integer', 'image_count' => 'integer', 'access_count' => 'integer', 'is_home_recommended' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
