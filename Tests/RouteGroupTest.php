<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

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

        $this->assertInstanceOf(Router::class, $router->group('test/123', function () {
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

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame(['GET'], $route->getMethods());
            $this->assertSame('test/all', $route->getUri());
        });

        $this->assertSame(1, $count);
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

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame(['GET'], $route->getMethods());
            $this->assertSame('test/all', $route->getUri());
        });

        $this->assertSame(1, $count);
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

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame(['HEAD'], $route->getMethods());
            $this->assertSame('test/all', $route->getUri());
        });

        $this->assertSame(1, $count);
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

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame(['POST'], $route->getMethods());
            $this->assertSame('test/all', $route->getUri());
        });

        $this->assertSame(1, $count);
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

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame(['PUT'], $route->getMethods());
            $this->assertSame('test/all', $route->getUri());
        });

        $this->assertSame(1, $count);
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

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame(['PATCH'], $route->getMethods());
            $this->assertSame('test/all', $route->getUri());
        });

        $this->assertSame(1, $count);
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

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame(['DELETE'], $route->getMethods());
            $this->assertSame('test/all', $route->getUri());
        });

        $this->assertSame(1, $count);
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

            $this->assertInstanceOf(Route::class, $route);
            $this->assertSame(['OPTIONS'], $route->getMethods());
            $this->assertSame('test/all', $route->getUri());
        });

        $this->assertSame(1, $count);
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

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        $this->assertSame('abc123', RouteGroup::testFunctionAddedByMacro());
    }

    /**
     * @test
     */
    public function canExtendPostBehaviourWithMixin()
    {
        RouteGroup::mixin(new RouteGroupMixin());

        $queryBuilder = new RouteGroup([], new Router(new RouteCollector()));

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
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
