<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Service;

use Hyperf\DbConnection\Db;

class BannerService
{
    /**
     * 获取轮播.
     *
     * @return \Hyperf\Utils\Collection
     */
    public static function banner()
    {
        // 'platform'=>APPLICATION_CLIENT_TYPE,
        $banner = Db::table('slide')->selectRaw('name,images_url,event_value,event_type,bg_color')->where(['is_enable' => 1])->orderByRaw('sort asc')->get();
        if (!empty($banner)) {
            foreach ($banner as $v) {
                var_dump($v);
                $v->images_url_old = $v->images_url;
                $v->images_url     = ResourcesService::AttachmentPathViewHandle($v->images_url);
                $v->event_value    = empty($v->event_value) ? null : $v->event_value;
            }
        }

        return $banner;
    }
}
