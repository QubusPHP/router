<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\ServerRequest;
use Mockery;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Qubus\Http\Request;
use Qubus\Http\Response;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Injector;
use Qubus\Injector\Psr11\Container;
use Qubus\Routing\Controller\ControllerMiddlewareOptions;
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;
use Qubus\Tests\Routing\Controllers\MiddlewareProvidingController;
use Qubus\Tests\Routing\Middlewares\AddHeaderMiddleware;

class ControllerTest extends TestCase
{
    private ContainerInterface $container;

    public function setUp(): void
    {
        $this->container = new Container(InjectorFactory::create([
            Injector::STANDARD_ALIASES => [
                RequestInterface::class => Request::class,
                ResponseInterface::class => Response::class,
                ResponseFactoryInterface::class => ResponseFactory::class,
                \Psr\Http\Message\ServerRequestInterface::class => \Laminas\Diactoros\ServerRequest::class,
                \Psr\Http\Server\RequestHandlerInterface::class => \Qubus\Http\RequestHandler::class,
                \Qubus\Routing\Interfaces\MiddlewareResolver::class =>
                    \Qubus\Routing\Route\InjectorMiddlewareResolver::class,
            ],
        ]));
    }

    /** @test */
    public function canAddSingleMiddlewareViaController()
    {
        $request   = new ServerRequest([], [], '/test/123', 'GET');
        $router    = new Router(new RouteCollector(), $this->container);
        $router->setDefaultNamespace('Qubus\\Tests\\Routing\\Controllers');

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'));
        $this->container->make(MiddlewareProvidingController::class, [$controller]);

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
        $resolver  = $this->createMockMiddlewareResolverWithHeader('X-Header', 'testing123');
        $request   = new ServerRequest([], [], '/test/123', 'GET');
        $router    = new Router(new RouteCollector(), $this->container, null, $resolver);
        $router->setDefaultNamespace('Qubus\\Tests\\Routing\\Controllers');

        $controller = new MiddlewareProvidingController();
        $controller->middleware('middleware-key');
        $this->container->make(MiddlewareProvidingController::class, [$controller]);

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
        $request   = new ServerRequest([], [], '/test/123', 'GET');
        $router    = new Router(new RouteCollector(), $this->container);
        $router->setDefaultNamespace('Qubus\\Tests\\Routing\\Controllers');

        $controller = new MiddlewareProvidingController();
        $controller->middleware([
            new AddHeaderMiddleware('X-Header-1', 'testing123'),
            new AddHeaderMiddleware('X-Header-2', 'testing456'),
        ]);
        $this->container->make(MiddlewareProvidingController::class, [$controller]);

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
        $router = new Router(new RouteCollector(), $this->container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->only('returnOne');
        $this->container->make(MiddlewareProvidingController::class, [$controller]);

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
        $router = new Router(new RouteCollector(), $this->container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->only(['returnOne', 'returnThree']);
        $this->container->make(MiddlewareProvidingController::class, [$controller]);

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
        $router = new Router(new RouteCollector(), $this->container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware('X-Header', 'testing123'))->except('returnOne');
        $this->container->make(MiddlewareProvidingController::class, [$controller]);

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
        $router = new Router(new RouteCollector(), $this->container);

        $controller = new MiddlewareProvidingController();
        $controller->middleware(new AddHeaderMiddleware(
            'X-Header',
            'testing123'
        ))->except(['returnOne', 'returnThree']);
        $this->container->make(MiddlewareProvidingController::class, [$controller]);

        $middlewareAppliedToMethods = [
            'returnOne'   => false,
            'returnTwo'   => true,
            'returnThree' => false,
        ];

        $this->assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods);
    }

    protected function assertMiddlewareIsAppliedToMethods($router, $middlewareAppliedToMethods): void
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
