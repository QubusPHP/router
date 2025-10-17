<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

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
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Route\RouteGroup;
use Qubus\Routing\Router;
use Qubus\Routing\Tests\Fixtures\RouteGroupMixin;

class RouteGroupTest extends TestCase
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
    public function testGroupFunctionIsChainable()
    {
        $router = new Router(new RouteCollector(), $this->container);

        Assert::assertInstanceOf(Router::class, $router->group('test/123', function () {
        }));
    }

    /** @test */
    public function testCanAddGetRequestToaGroup()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->get('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['GET'], $route->methods);
            Assert::assertSame('test/all', $route->uri);
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddRequestToaGroupWithLeadingSlash()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->get('/all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['GET'], $route->methods);
            Assert::assertSame('test/all', $route->uri);
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddHeadRequestToaGroup()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->head('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['HEAD'], $route->methods);
            Assert::assertSame('test/all', $route->uri);
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddPostRequestToaGroup()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->post('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['POST'], $route->methods);
            Assert::assertSame('test/all', $route->uri);
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddPutRequestToaGroup()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->put('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['PUT'], $route->methods);
            Assert::assertSame('test/all', $route->uri);
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddPatchRequestToaGroup()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->patch('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['PATCH'], $route->methods);
            Assert::assertSame('test/all', $route->uri);
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddDeleteRequestToaGroup()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->delete('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['DELETE'], $route->methods);
            Assert::assertSame('test/all', $route->uri);
        });

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddOptionRequestToaGroup()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $count  = 0;

        $router->group(['prefix' => 'test'], function ($group) use (&$count) {
            $count++;
            $route = $group->options('all', function () {
            });

            Assert::assertInstanceOf(Route::class, $route);
            Assert::assertSame(['OPTIONS'], $route->methods);
            Assert::assertSame('test/all', $route->uri);
        });

        Assert::assertSame(1, $count);
    }

    /**
     * @test
     */
    public function testCanExtendPostBehaviourWithMacros()
    {
        RouteGroup::macro('testFunctionAddedByMacro', function () {
            return 'abc123';
        });

        $queryBuilder = new RouteGroup([], new Router(new RouteCollector(), $this->container));

        Assert::assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        Assert::assertSame('abc123', RouteGroup::testFunctionAddedByMacro());
    }

    /**
     * @test
     */
    public function testCanExtendPostBehaviourWithMixin()
    {
        RouteGroup::mixin(new RouteGroupMixin());

        $queryBuilder = new RouteGroup([], new Router(new RouteCollector(), $this->container));

        Assert::assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
    }
}
