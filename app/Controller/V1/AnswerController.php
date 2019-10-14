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

namespace App\Controller\V1;

use App\Service\AnswerService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

/**
 * @Controller(prefix="api/v1")
 */
class AnswerController
{
    /**
     * @Inject
     *
     * @var AnswerService
     */
    private $answerService;

    /**
     * @GetMapping(path="answers")
     */
    public function list()
    {
        return $this->answerService->answerList();
    }
}
