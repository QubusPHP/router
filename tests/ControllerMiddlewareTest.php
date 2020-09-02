<?php

namespace Qubus\Router\Test;

use PHPUnit\Framework\TestCase;
use Qubus\Router\ControllerMiddleware;
use Qubus\Router\ControllerMiddlewareOptions;
use Qubus\Router\Test\Middlewares\AddHeaderMiddleware;

class ControllerMiddlewareTest extends TestCase
{
    /** @test */
    public function canRetrieveMiddleware()
    {
        $middleware = new AddHeaderMiddleware('X-Header', 'testing123');
        $options    = new ControllerMiddlewareOptions();

        $controllerMiddleware = new ControllerMiddleware($middleware, $options);

        $this->assertSame($middleware, $controllerMiddleware->middleware());
    }

    /** @test */
    public function canRetrieveOptions()
    {
        $middleware = new AddHeaderMiddleware('X-Header', 'testing123');
        $options    = new ControllerMiddlewareOptions();

        $controllerMiddleware = new ControllerMiddleware($middleware, $options);

        $this->assertSame($options, $controllerMiddleware->options());
    }
}
