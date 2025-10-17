<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing\Controllers;

use Qubus\Tests\Routing\Services\TestService;

class TestController
{
    public function returnHelloWorld(): string
    {
        return 'Hello World!';
    }

    public function expectsInjectedParams($postId, $commentId): string
    {
        return '$postId: ' . $postId . ' $commentId: ' . $commentId;
    }

    public function postId($postId): string
    {
        return '$postId: ' . $postId;
    }

    public function typeHintTestService(TestService $testService)
    {
        return $testService->value;
    }

    public function typeHintTestServiceWithParams(TestService $testService, $postId, $commentId): string
    {
        return '$postId: ' . $postId . ' $commentId: ' . $commentId . ' TestService: ' . $testService->value;
    }
}
