<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Route\RouteGroup;
use Qubus\Routing\Router;

class RouteGroupTest extends TestCase
{
    /** @test */
    public function groupFunctionIsChainable()
    {
        $router = new Router(new RouteCollector());

        Assert::assertInstanceOf(Router::class, $router->group('test/123', function () {
        }));
    }

    /** @test */
    public function canAddGetRequestToaGroup()
    {
        $router = new Router(new RouteCollector());
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->get('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['GET'], $route->getMethods());
            Assert::assertSame('test/all', $route->getUri());
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function canAddRequestToaGroupWithLeadingSlash()
    {
        $router = new Router(new RouteCollector());
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->get('/all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['GET'], $route->getMethods());
            Assert::assertSame('test/all', $route->getUri());
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function canAddHeadRequestToaGroup()
    {
        $router = new Router(new RouteCollector());
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->head('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['HEAD'], $route->getMethods());
            Assert::assertSame('test/all', $route->getUri());
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function canAddPostRequestToaGroup()
    {
        $router = new Router(new RouteCollector());
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->post('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['POST'], $route->getMethods());
            Assert::assertSame('test/all', $route->getUri());
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function canAddPutRequestToaGroup()
    {
        $router = new Router(new RouteCollector());
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->put('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['PUT'], $route->getMethods());
            Assert::assertSame('test/all', $route->getUri());
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function canAddPatchRequestToaGroup()
    {
        $router = new Router(new RouteCollector());
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->patch('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['PATCH'], $route->getMethods());
            Assert::assertSame('test/all', $route->getUri());
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function canAddDeleteRequestToaGroup()
    {
        $router = new Router(new RouteCollector());
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->delete('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['DELETE'], $route->getMethods());
            Assert::assertSame('test/all', $route->getUri());
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function canAddOptionRequestToaGroup()
    {
        $router = new Router(new RouteCollector());
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->options('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['OPTIONS'], $route->getMethods());
            Assert::assertSame('test/all', $route->getUri());
        });

        Assert::assertSame(1, $count);
    }

    /**
     * @test
     */
    public function canExtendPostBehaviourWithMacros()
    {
        RouteGroup::macro('testFunctionAddedByMacro', function () {
            return 'abc123';
        });

        $queryBuilder = new RouteGroup([], new Router(new RouteCollector()));

        Assert::assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        Assert::assertSame('abc123', RouteGroup::testFunctionAddedByMacro());
    }

    /**
     * @test
     */
    public function canExtendPostBehaviourWithMixin()
    {
        RouteGroup::mixin(new RouteGroupMixin());

        $queryBuilder = new RouteGroup([], new Router(new RouteCollector()));

        Assert::assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
    }
}

class RouteGroupMixin
{
    public function testFunctionAddedByMixin()
    {
        return function () {
            return 'abc123';
        };
    }
}
