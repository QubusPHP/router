<?php

namespace Qubus\Router\Test\Controllers;

use Qubus\Router\Test\Services\TestService;

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
