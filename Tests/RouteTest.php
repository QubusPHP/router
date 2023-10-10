<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Request;
use Qubus\Http\Response;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Injector;
use Qubus\Injector\Psr11\Container;
use Qubus\Routing\Exceptions\RouteNameRedefinedException;
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;

class RouteTest extends TestCase
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
    public function aRouteCanBeNamed()
    {
        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertFalse($router->has('test'));
        $route = $router->get('test/123', function () {
        })->name('test');
        Assert::assertTrue($router->has('test'));
    }

    /** @test */
    public function nameFunctionIsChainable()
    {
        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertInstanceOf(Route::class, $router->get('test/123', function () {
        })->name('test'));
    }

    /** @test */
    public function aRouteCannotBeRenamed()
    {
        $this->expectException(RouteNameRedefinedException::class);

        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('test/123', function () {
        })->name('test1')->name('test2');
    }

    /** @test */
    public function whereFunctionIsChainable()
    {
        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertInstanceOf(Route::class, $router->get('test/{id}', function () {
        })->where('id', '[0-9]+'));
    }

    /** @test */
    public function whereFunctionIsChainableWhenPassedAnArray()
    {
        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertInstanceOf(Route::class, $router->get('test/{id}', function () {
        })->where(['id' => '[0-9]+']));
    }

    /** @test */
    public function whereFunctionThrowsExceptionWhenNoParamsProvided()
    {
        $this->expectException(TypeException::class);

        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertInstanceOf(Route::class, $router->get('test/{id}', function () {
        })->where());
    }

    /** @test */
    public function canGetRouteActionNameWhenClosure()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $route  = $router->get('test/123', function () {
        });

        Assert::assertSame('Closure', $route->getActionName());
    }

    /** @test */
    public function canGetRouteActionNameWhenCallable()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $route  = $router->get('test/123', [TestCallableController::class, 'testStatic']);

        Assert::assertSame(TestCallableController::class . '@testStatic', $route->getActionName());
    }

    /** @test */
    public function canGetRouteActionNameWhenCallableInstance()
    {
        $router     = new Router(new RouteCollector(), $this->container);
        $controller = new TestCallableController();
        $route      = $router->get('test/123', [$controller, 'test']);

        Assert::assertSame(TestCallableController::class . '@test', $route->getActionName());
    }

    /** @test */
    public function canGetRouteActionNameWhenControllerString()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $route  = $router->get('test/123', TestCallableController::class . '@test');

        Assert::assertSame(TestCallableController::class . '@test', $route->getActionName());
    }

    /**
     * @test
     */
    public function canExtendPostBehaviorWithMacros()
    {
        Route::macro('testFunctionAddedByMacro', function () {
            return 'abc123';
        });

        $queryBuilder = new Route(['GET'], '/test/url', function () {
        });

        Assert::assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        Assert::assertSame('abc123', Route::testFunctionAddedByMacro());
    }

    /**
     * @test
     */
    public function canExtendPostBehaviorWithMixin()
    {
        Route::mixin(new RouteMixin());

        $queryBuilder = new Route(['GET'], '/test/url', function () {
        });

        Assert::assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
    }
}

class TestCallableController
{
    public static function testStatic()
    {
    }

    public function test()
    {
    }
}

class RouteMixin
{
    public function testFunctionAddedByMixin()
    {
        return function () {
            return 'abc123';
        };
    }
}
