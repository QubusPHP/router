<?php

namespace Qubus\Router\Test;

use DI\ContainerBuilder;
use Laminas\Diactoros\ServerRequest;
use Mockery;
use PHPUnit\Framework\TestCase;
use Qubus\Router\ControllerMiddlewareOptions;
use Qubus\Router\Interfaces\MiddlewareResolverInterface;
use Qubus\Router\Route\RouteCollector;
use Qubus\Router\Router;
use Qubus\Router\Test\Controllers\MiddlewareProvidingController;
use Qubus\Router\Test\Middlewares\AddHeaderMiddleware;

class ControllerTest extends TestCase
{
    /** @test */
    public function canAddSingleMiddlewareViaController()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request   = new ServerRequest([], [], '/test/123', 'GET');
        $router    = new Router(new RouteCollector(), $container);
        $router->setDefaultNamespace('Qubus\\Router\\Test\\Controllers');

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'));
        $container->set(MiddlewareProvidingController::class, $controller);

        $router->get(
            '/test/123',
            'MiddlewareProvidingController@returnOne'
        );

        $response = $router->match($request);

        $this->assertTrue($response->hasHeader('X-Header'));
        $this->assertSame('testing123', $response->getHeader('X-Header')[0]);
    }

    /** @test */
    public function canResolveMiddlewareOnaControllerUsingCustomResolver()
    {
        $container = ContainerBuilder::buildDevContainer();
        $resolver  = $this->createMockMiddlewareResolverWithHeader('X-Header', 'testing123');
        $request   = new ServerRequest([], [], '/test/123', 'GET');
        $router    = new Router(new RouteCollector(), $container, null, $resolver);
        $router->setDefaultNamespace('Qubus\\Router\\Test\\Controllers');

        $controller = new MiddlewareProvidingController();
        $controller->middleware('middleware-key');
        $container->set(MiddlewareProvidingController::class, $controller);

        $router->get(
            '/test/123',
            'MiddlewareProvidingController@returnOne'
        );

        $response = $router->match($request);

        $this->assertTrue($response->hasHeader('X-Header'));
        $this->assertSame('testing123', $response->getHeader('X-Header')[0]);
    }

    /** @test */
    public function canAddMultipleMiddlewareAsArrayViaController()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request   = new ServerRequest([], [], '/test/123', 'GET');
        $router    = new Router(new RouteCollector(), $container);
        $router->setDefaultNamespace('Qubus\\Router\\Test\\Controllers');

        $controller = new MiddlewareProvidingController();
        $controller->middleware([
            new AddHeaderMiddleware('X-Header-1', 'testing123'),
            new AddHeaderMiddleware('X-Header-2', 'testing456'),
        ]);
        $container->set(MiddlewareProvidingController::class, $controller);

        $router->get(
            '/test/123',
            'MiddlewareProvidingController@returnOne'
        );

        $response = $router->match($request);

        $this->assertTrue($response->hasHeader('X-Header-1'));
        $this->assertSame('testing123', $response->getHeader('X-Header-1')[0]);
        $this->assertTrue($response->hasHeader('X-Header-2'));
        $this->assertSame('testing456', $response->getHeader('X-Header-2')[0]);
    }

    /** @test */
    public function controllerMiddlewareMethodReturnsOptions()
    {
        $controller = new MiddlewareProvidingController();

        $options = $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'));

        $this->assertInstanceOf(ControllerMiddlewareOptions::class, $options);
    }

    /** @test */
    public function middlewareCanBeLimitedToMethodsUsingOnly()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router    = new Router(new RouteCollector(), $container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->only('returnOne');
        $container->set(MiddlewareProvidingController::class, $controller);

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
        $container = ContainerBuilder::buildDevContainer();
        $router    = new Router(new RouteCollector(), $container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->only(['returnOne', 'returnThree']);
        $container->set(MiddlewareProvidingController::class, $controller);

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
        $container = ContainerBuilder::buildDevContainer();
        $router    = new Router(new RouteCollector(), $container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->except('returnOne');
        $container->set(MiddlewareProvidingController::class, $controller);

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
        $container = ContainerBuilder::buildDevContainer();
        $router    = new Router(new RouteCollector(), $container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->except(['returnOne', 'returnThree']);
        $container->set(MiddlewareProvidingController::class, $controller);

        $middlewareAppliedToMethods = [
            'returnOne'   => false,
            'returnTwo'   => true,
            'returnThree' => false,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    protected function assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods)
    {
        $router->setDefaultNamespace('Qubus\\Router\\Test\\Controllers');
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
                $this->assertTrue($response->hasHeader('X-Header'), '`' . $method . '` should have middleware applied');
                $this->assertSame('testing123', $response->getHeader('X-Header')[0]);
            } else {
                $this->assertFalse($response->hasHeader('X-Header'), '`' . $method . '` should not have middleware applied');
            }
        }
    }

    private function createMockMiddlewareResolverWithHeader($header, $value)
    {
        $middleware = new AddHeaderMiddleware($header, $value);
        $resolver   = Mockery::mock(MiddlewareResolverInterface::class);
        $resolver->shouldReceive('resolve')->with('middleware-key')->andReturn($middleware);
        $resolver->shouldReceive('resolve')->with(Mockery::type('callable'))->andReturnUsing(function ($argument) {
            return $argument;
        });

        return $resolver;
    }
}
