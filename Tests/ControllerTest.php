<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\ServerRequest;
use Mockery;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Psr11\Container;
use Qubus\Routing\Controller\ControllerMiddlewareOptions;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;
use Qubus\Tests\Routing\Controllers\MiddlewareProvidingController;
use Qubus\Tests\Routing\Middlewares\AddHeaderMiddleware;

class ControllerTest extends TestCase
{
    /** @test */
    public function canAddSingleMiddlewareViaController()
    {
        $container = new Container(InjectorFactory::create([]));
        $request   = new ServerRequest([], [], '/test/123', 'GET');
        $router    = new Router(new RouteCollector(), $container);
        $router->setDefaultNamespace('Qubus\\Tests\\Routing\\Controllers');

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'));
        $container->make(MiddlewareProvidingController::class, [$controller]);

        $router->get(
            '/test/123',
            'MiddlewareProvidingController@returnOne'
        );

        $response = $router->match($request);

        Assert::assertTrue($response->hasHeader('X-Header'));
        Assert::assertSame('testing123', $response->getHeader('X-Header')[0]);
    }

    /** @test */
    public function canResolveMiddlewareOnaControllerUsingCustomResolver()
    {
        $container = new Container(InjectorFactory::create([]));
        $resolver  = $this->createMockMiddlewareResolverWithHeader('X-Header', 'testing123');
        $request   = new ServerRequest([], [], '/test/123', 'GET');
        $router    = new Router(new RouteCollector(), $container, null, $resolver);
        $router->setDefaultNamespace('Qubus\\Tests\\Routing\\Controllers');

        $controller = new MiddlewareProvidingController();
        $controller->middleware('middleware-key');
        $container->make(MiddlewareProvidingController::class, [$controller]);

        $router->get(
            '/test/123',
            'MiddlewareProvidingController@returnOne'
        );

        $response = $router->match($request);

        Assert::assertTrue($response->hasHeader('X-Header'));
        Assert::assertSame('testing123', $response->getHeader('X-Header')[0]);
    }

    /** @test */
    public function canAddMultipleMiddlewareAsArrayViaController()
    {
        $container = new Container(InjectorFactory::create([]));
        $request   = new ServerRequest([], [], '/test/123', 'GET');
        $router    = new Router(new RouteCollector(), $container);
        $router->setDefaultNamespace('Qubus\\Tests\\Routing\\Controllers');

        $controller = new MiddlewareProvidingController();
        $controller->middleware([
            new AddHeaderMiddleware('X-Header-1', 'testing123'),
            new AddHeaderMiddleware('X-Header-2', 'testing456'),
        ]);
        $container->make(MiddlewareProvidingController::class, [$controller]);

        $router->get(
            '/test/123',
            'MiddlewareProvidingController@returnOne'
        );

        $response = $router->match($request);

        Assert::assertTrue($response->hasHeader('X-Header-1'));
        Assert::assertSame('testing123', $response->getHeader('X-Header-1')[0]);
        Assert::assertTrue($response->hasHeader('X-Header-2'));
        Assert::assertSame('testing456', $response->getHeader('X-Header-2')[0]);
    }

    /** @test */
    public function controllerMiddlewareMethodReturnsOptions()
    {
        $controller = new MiddlewareProvidingController();

        $options = $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'));

        Assert::assertInstanceOf(ControllerMiddlewareOptions::class, $options);
    }

    /** @test */
    public function middlewareCanBeLimitedToMethodsUsingOnly()
    {
        $container = new Container(InjectorFactory::create([]));
        $router    = new Router(new RouteCollector(), $container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->only('returnOne');
        $container->make(MiddlewareProvidingController::class, [$controller]);

        $middlewareAppliedToMethods = [
            'returnOne'   => true,
            'returnTwo'   => false,
            'returnThree' => false,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    /** @test */
    public function middlewareCanBeLimitedToMultipleMethodsUsingOnly()
    {
        $container = new Container(InjectorFactory::create([]));
        $router    = new Router(new RouteCollector(), $container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->only(['returnOne', 'returnThree']);
        $container->make(MiddlewareProvidingController::class, [$controller]);

        $middlewareAppliedToMethods = [
            'returnOne'   => true,
            'returnTwo'   => false,
            'returnThree' => true,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    /** @test */
    public function middlewareCanBeLimitedToMethodsUsingExcept()
    {
        $container = new Container(InjectorFactory::create([]));
        $router    = new Router(new RouteCollector(), $container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->except('returnOne');
        $container->make(MiddlewareProvidingController::class, [$controller]);

        $middlewareAppliedToMethods = [
            'returnOne'   => false,
            'returnTwo'   => true,
            'returnThree' => true,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    /** @test */
    public function middlewareCanBeLimitedToMultipleMethodsUsingExcept()
    {
        $container = new Container(InjectorFactory::create([]));
        $router    = new Router(new RouteCollector(), $container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->except(['returnOne', 'returnThree']);
        $container->make(MiddlewareProvidingController::class, [$controller]);

        $middlewareAppliedToMethods = [
            'returnOne'   => false,
            'returnTwo'   => true,
            'returnThree' => false,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    protected function assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods)
    {
        $router->setDefaultNamespace('Qubus\\Tests\\Routing\\Controllers');
        // Add the routes
        foreach ($middlewareAppliedToMethods as $method => $applied) {
            $router->get(
                '/test/' . $method,
                'MiddlewareProvidingController@' . $method
            );
        }

        // Test middleware is only applied to the correct routes
        foreach ($middlewareAppliedToMethods as $method => $applied) {
            $response = $router->match(new ServerRequest([], [], '/test/' . $method, 'GET'));

            if ($applied) {
                Assert::assertTrue($response->hasHeader('X-Header'), '`' . $method . '` should have middleware applied');
                Assert::assertSame('testing123', $response->getHeader('X-Header')[0]);
            } else {
                Assert::assertFalse($response->hasHeader('X-Header'), '`' . $method . '` should not have middleware applied');
            }
        }
    }

    private function createMockMiddlewareResolverWithHeader($header, $value)
    {
        $middleware = new AddHeaderMiddleware($header, $value);
        $resolver   = Mockery::mock(MiddlewareResolver::class);
        $resolver->shouldReceive('resolve')->with('middleware-key')->andReturn($middleware);
        $resolver->shouldReceive('resolve')->with(Mockery::type('callable'))->andReturnUsing(function ($argument) {
            return $argument;
        });

        return $resolver;
    }
}
