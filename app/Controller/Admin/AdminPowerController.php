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
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * @Controller(prefix="admin")
 */
class AdminPowerController
{
    /**
     * @Inject
     *
     * @var AdminPowerService
     */
    private $adminPowerService;

    /**
     * @GetMapping(path="power/list")
     *
     * @return mixed
     */
    public function list()
    {
        $data_params = [
            'field' => 'id,pid,name,control,action,sort,is_show,icon',
            'order_by' => 'sort',
            'where' => ['pid' => 0],
        ];

        return $this->adminPowerService->powerList($data_params);
    }

    /**
     * @RequestMapping(path="power/save", methods="put,post")
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    public function powerSave(RequestInterface $request)
    {
        return $this->adminPowerService->powerSave($request->post());
    }

    /**
     * @RequestMapping(path="power/delete", methods="delete")
     *
     * @param RequestInterface $request
     *
     * @return bool
     */
    public function powerDelete(RequestInterface $request)
    {
        return $this->adminPowerService->powerDelete($request->post());
    }

    /**
     * @GetMapping(path="role/list")
     *
     * @return mixed
     */
    public function roleList()
    {
        $data_params = [
            'field' => 'id,name,is_enable,add_time',
        ];

        return $this->adminPowerService->roleList($data_params);
    }
}
