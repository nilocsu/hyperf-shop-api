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

namespace App\Controller;

use App\Service\AppNavService;
use App\Service\ConfigService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\RequestMapping;

/**
 * @author colin.
 * date 19-6-27 下午4:09
 * @\Hyperf\HttpServer\Annotation\Controller(prefix="/")
 */
class IndexController extends Controller
{
    /**
     * @Inject()
     *
     * @var AppNavService
     */
    private $appNavService;


    public function index()
    {
        $order = ConfigService::getConfigCache('common_order_is_booking');
        return $this->appNavService->appHomeNav();
    }

}
