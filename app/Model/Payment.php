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
 * @property $name
 * @property $payment
 * @property $logo
 * @property $version
 * @property $apply_version
 * @property $desc
 * @property $author
 * @property $author_url
 * @property $element
 * @property $config
 * @property $apply_terminal
 * @property $is_enable
 * @property $is_open_user
 * @property $sort
 * @property $add_time
 * @property $upd_time
 */
class Payment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment';

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
    protected $fillable = ['id', 'name', 'payment', 'logo', 'version', 'apply_version', 'desc', 'author', 'author_url', 'element', 'config', 'apply_terminal', 'is_enable', 'is_open_user', 'sort', 'add_time', 'upd_time'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'is_enable' => 'integer', 'is_open_user' => 'integer', 'sort' => 'integer', 'add_time' => 'integer', 'upd_time' => 'integer'];
}
