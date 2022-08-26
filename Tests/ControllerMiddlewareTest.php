<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Routing\Controller\ControllerMiddlewareOptions;
use Qubus\Routing\Controller\ControllerMiddlewarePipe;
use Qubus\Tests\Routing\Middlewares\AddHeaderMiddleware;

class ControllerMiddlewareTest extends TestCase
{
    /** @test */
    public function canRetrieveMiddleware()
    {
        $middleware = new AddHeaderMiddleware('X-Header', 'testing123');
        $options    = new ControllerMiddlewareOptions();

        $controllerMiddleware = new ControllerMiddlewarePipe($middleware, $options);

        Assert::assertSame($middleware, $controllerMiddleware->middleware());
    }

    /** @test */
    public function canRetrieveOptions()
    {
        $middleware = new AddHeaderMiddleware('X-Header', 'testing123');
        $options    = new ControllerMiddlewareOptions();

        $controllerMiddleware = new ControllerMiddlewarePipe($middleware, $options);

        Assert::assertSame($options, $controllerMiddleware->options());
    }
}
