<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing\Controllers;

use Qubus\Tests\Routing\Services\TestService;

class TestConstructorParamController
{
    private $testService;

    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }

    public function returnTestServiceValue()
    {
        return $this->testService->value;
    }
}
