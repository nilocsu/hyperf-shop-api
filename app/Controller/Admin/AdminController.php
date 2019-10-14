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

namespace App\Controller\Admin;

use App\Service\AdminPowerService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;

/**
 * @Controller(prefix="admin")
 */
class AdminController
{
    /**
     * @Inject
     *
     * @var AdminPowerService
     */
    private $adminService;

    public function index()
    {
    }
}
