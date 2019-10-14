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
 * @property $shop_id
 * @property $order_id
 * @property $goods_id
 * @property $business_type
 * @property $content
 * @property $reply
 * @property $rating
 * @property $is_show
 * @property $is_anonymous
 * @property $is_reply
 * @property $reply_time
 * @property $add_time
 * @property $upd_time
 */
class GoodsComments extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'goods_comments';

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
    protected $fillable = ['id', 'user_id', 'shop_id', 'order_id', 'goods_id', 'business_type', 'content', 'reply', 'rating', 'is_show', 'is_anonymous', 'is_reply', 'reply_time', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'shop_id' => 'integer', 'order_id' => 'integer', 'goods_id' => 'integer', 'rating' => 'integer', 'is_show' => 'integer', 'is_anonymous' => 'integer', 'is_reply' => 'integer', 'reply_time' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
