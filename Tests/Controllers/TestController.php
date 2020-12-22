<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing\Controllers;

use Qubus\Tests\Routing\Services\TestService;

class TestController
{
    public function returnHelloWorld()
    {
        return 'Hello World!';
    }

    public function expectsInjectedParams($postId, $commentId)
    {
        return '$postId: ' . $postId . ' $commentId: ' . $commentId;
    }

    public function postId($postId)
    {
        return '$postId: ' . $postId;
    }

    public function typeHintTestService(TestService $testService)
    {
        return $testService->value;
    }

    public function typeHintTestServiceWithParams(TestService $testService, $postId, $commentId)
    {
        return '$postId: ' . $postId . ' $commentId: ' . $commentId . ' TestService: ' . $testService->value;
    }
}
