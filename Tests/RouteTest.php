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
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;
use ReflectionException;

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

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testaRouteCanBeNamed()
    {
        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertFalse($router->has('test'));
        $route = $router->get('test/123', function () {
        })->name('test');
        Assert::assertTrue($router->has('test'));
    }

    /** @test */
    public function testNameFunctionIsChainable()
    {
        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertInstanceOf(Route::class, $router->get('test/123', function () {
        })->name('test'));
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testaRouteCannotBeRenamed()
    {
        $this->expectException(RouteNameRedefinedException::class);

        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('test/123', function () {
        })->name('test1')->name('test2');
    }

    /** @test
     * @throws TypeException
     */
    public function testWhereFunctionIsChainable()
    {
        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertInstanceOf(Route::class, $router->get('test/{id}', function () {
        })->where('id', '[0-9]+'));
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testWhereFunctionIsChainableWhenPassedAnArray()
    {
        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertInstanceOf(Route::class, $router->get('test/{id}', function () {
        })->where(['id' => '[0-9]+']));
    }

    /** @test */
    public function testWhereFunctionThrowsExceptionWhenNoParamsProvided()
    {
        $this->expectException(TypeException::class);

        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertInstanceOf(Route::class, $router->get('test/{id}', function () {
        })->where());
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testCanGetRouteActionNameWhenClosure()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $route  = $router->get('test/123', function () {
        });

        Assert::assertSame('Closure', $route->getActionName());
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testCanGetRouteActionNameWhenCallable()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $route  = $router->get('test/123', [TestCallableController::class, 'testStatic']);

        Assert::assertSame(TestCallableController::class . '@testStatic', $route->getActionName());
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testCanGetRouteActionNameWhenCallableInstance()
    {
        $router     = new Router(new RouteCollector(), $this->container);
        $controller = new TestCallableController();
        $route      = $router->get('test/123', [$controller, 'test']);

        Assert::assertSame(TestCallableController::class . '@test', $route->getActionName());
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testCanGetRouteActionNameWhenControllerString()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $route  = $router->get('test/123', TestCallableController::class . '@test');

        Assert::assertSame(TestCallableController::class . '@test', $route->getActionName());
    }

    /**
     * @test
     */
    public function testCanExtendPostBehaviorWithMacros()
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
     * @throws ReflectionException
     */
    public function testCanExtendPostBehaviorWithMixin()
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
    public function testFunctionAddedByMixin(): \Closure
    {
        return function () {
            return 'abc123';
        };
    }
}
