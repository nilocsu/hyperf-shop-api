<?php

namespace App\Controller\V1;

use App\Service\BannerService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

/**
 * @author colin.
 * date 19-6-25 下午6:27
 * @Controller(prefix="/api/v1")
 */
class BannerController
{
    /**
     * @Inject()
     *
     * @var BannerService
     */
    private $bannerService;

    /**
     * 轮播图.
     *
     * @GetMapping(path="banners")
     *
     * @return \Hyperf\Utils\Collection
     */
    public function list()
    {
        return $this->bannerService->banner();
    }
}
