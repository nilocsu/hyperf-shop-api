<?php


namespace App\Controller\V1;

use App\Model\User;
use App\Service\QrService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

/**
 * @author colin.
 * date 19-6-27 下午10:50
 * @Controller(prefix="/api/v1")
 */
class QrController
{

    /**
     * @var QrService
     */
    private $qrService;

    public function __construct(QrService $qrService)
    {
        $this->qrService = $qrService;
    }


    /**
     * @RequestMapping(path="user/{is}/qr")
     * @param User $user
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return mixed
     */
    public function userNewQr(User $user, RequestInterface $request, ResponseInterface $response){


//        BASE_PATH;
        $qr = $this->qrService->newQr(\env('RESOURCE') . $request->getPathInfo());
        return $response->raw($qr->writeString())->withHeader('Content-Type',$qr->getContentType());
    }
}