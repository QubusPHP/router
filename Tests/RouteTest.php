<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Qubus\Exception\Data\TypeException;
use Qubus\Routing\Exceptions\RouteNameRedefinedException;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;

class RouteTest extends TestCase
{
    /** @test */
    public function aRouteCanBeNamed()
    {
        $router = new Router(new RouteCollector());

        $this->assertFalse($router->has('test'));
        $route = $router->get('test/123', function () {
        })->name('test');
        $this->assertTrue($router->has('test'));
    }

    /** @test */
    public function nameFunctionIsChainable()
    {
        $router = new Router(new RouteCollector());

        $this->assertInstanceOf(Route::class, $router->get('test/123', function () {
        })->name('test'));
    }

    /** @test */
    public function aRouteCannotBeRenamed()
    {
        $this->expectException(RouteNameRedefinedException::class);

        $router = new Router(new RouteCollector());

        $route = $router->get('test/123', function () {
        })->name('test1')->name('test2');
    }

    /** @test */
    public function whereFunctionIsChainable()
    {
        $router = new Router(new RouteCollector());

        $this->assertInstanceOf(Route::class, $router->get('test/{id}', function () {
        })->where('id', '[0-9]+'));
    }

    /** @test */
    public function whereFunctionIsChainableWhenPassedAnArray()
    {
        $router = new Router(new RouteCollector());

        $this->assertInstanceOf(Route::class, $router->get('test/{id}', function () {
        })->where(['id' => '[0-9]+']));
    }

    /** @test */
    public function whereFunctionThrowsExceptionWhenNoParamsProvided()
    {
        $this->expectException(TypeException::class);

        $router = new Router(new RouteCollector());

        $this->assertInstanceOf(Route::class, $router->get('test/{id}', function () {
        })->where());
    }

    /** @test */
    public function canGetRouteActionNameWhenClosure()
    {
        $router = new Router(new RouteCollector());
        $route  = $router->get('test/123', function () {
        });

        $this->assertSame('Closure', $route->getActionName());
    }

    /** @test */
    public function canGetRouteActionNameWhenCallable()
    {
        $router = new Router(new RouteCollector());
        $route  = $router->get('test/123', [TestCallableController::class, 'testStatic']);

        $this->assertSame(TestCallableController::class . '@testStatic', $route->getActionName());
    }

    /** @test */
    public function canGetRouteActionNameWhenCallableInstance()
    {
        $router     = new Router(new RouteCollector());
        $controller = new TestCallableController();
        $route      = $router->get('test/123', [$controller, 'test']);

        $this->assertSame(TestCallableController::class . '@test', $route->getActionName());
    }

    /** @test */
    public function canGetRouteActionNameWhenControllerString()
    {
        $router = new Router(new RouteCollector());
        $route  = $router->get('test/123', TestCallableController::class . '@test');

        $this->assertSame(TestCallableController::class . '@test', $route->getActionName());
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

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        $this->assertSame('abc123', Route::testFunctionAddedByMacro());
    }

    /**
     * @test
     */
    public function canExtendPostBehaviorWithMixin()
    {
        Route::mixin(new RouteMixin());

        $queryBuilder = new Route(['GET'], '/test/url', function () {
        });

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
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
