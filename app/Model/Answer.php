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
 * @property $name
 * @property $tel
 * @property $title
 * @property $content
 * @property $reply
 * @property $is_reply
 * @property $reply_time
 * @property $is_show
 * @property $access_count
 * @property $add_time
 * @property $upd_time
 */
class Answer extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'answer';

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
    protected $fillable = ['id', 'user_id', 'name', 'tel', 'title', 'content', 'reply', 'is_reply', 'reply_time', 'is_show', 'access_count', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'is_reply' => 'integer', 'reply_time' => 'integer', 'is_show' => 'integer', 'access_count' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
