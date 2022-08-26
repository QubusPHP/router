<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\ServerRequest;
use Mockery;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Route\RouteGroup;
use Qubus\Routing\Router;
use Qubus\Tests\Routing\Middlewares\AddHeaderMiddleware;

class RouterMiddlewareTest extends TestCase
{
    /** @test */
    public function canAddMiddlewareAsaClosureToaRoute()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        })->middleware(function (ServerRequestInterface $request, callable $next) use (&$count) {
            $count++;

            $response = $next($request);
            return $response->withHeader('X-Key', 'value');
        });
        $response = $router->match($request);

        Assert::assertSame(2, $count);
        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertTrue($response->hasHeader('X-Key'));
        Assert::assertSame('value', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function canAddMiddlewareAsAnObjectToaRoute()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        })->middleware(new AddHeaderMiddleware('X-Key', 'value'));
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertTrue($response->hasHeader('X-Key'));
        Assert::assertSame('value', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function canAddMultipleMiddlewareToaRouteInSuccessiveCalls()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());

        $router->get('/test/123', function () {
        })
            ->middleware(new AddHeaderMiddleware('X-Key1', 'abc'))
            ->middleware(new AddHeaderMiddleware('X-Key2', '123'));

        $response = $router->match($request);

        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertTrue($response->hasHeader('X-Key1'));
        Assert::assertTrue($response->hasHeader('X-Key2'));
        Assert::assertSame('abc', $response->getHeader('X-Key1')[0]);
        Assert::assertSame('123', $response->getHeader('X-Key2')[0]);
    }

    /** @test */
    public function canAddMultipleMiddlewareToaRouteInaSingleCall()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());

        $router->get('/test/123', function () {
        })->middleware(
            new AddHeaderMiddleware('X-Key1', 'abc'),
            new AddHeaderMiddleware('X-Key2', '123')
        );

        $response = $router->match($request);

        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertTrue($response->hasHeader('X-Key1'));
        Assert::assertTrue($response->hasHeader('X-Key2'));
        Assert::assertSame('abc', $response->getHeader('X-Key1')[0]);
        Assert::assertSame('123', $response->getHeader('X-Key2')[0]);
    }

    /** @test */
    public function canAddMultipleMiddlewareToaRouteAsAnArray()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());

        $router->get('/test/123', function () {
        })->middleware([
            new AddHeaderMiddleware('X-Key1', 'abc'),
            new AddHeaderMiddleware('X-Key2', '123'),
        ]);

        $response = $router->match($request);

        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertTrue($response->hasHeader('X-Key1'));
        Assert::assertTrue($response->hasHeader('X-Key2'));
        Assert::assertSame('abc', $response->getHeader('X-Key1')[0]);
        Assert::assertSame('123', $response->getHeader('X-Key2')[0]);
    }

    /** @test */
    public function canAddMiddlewareToaGroup()
    {
        $request = new ServerRequest([], [], '/all', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $router->group(['middleware' => [new AddHeaderMiddleware('X-Key', 'abc')]], function ($group) use (&$count) {
            $count++;
            Assert::assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc123', $response->getBody()->getContents());
        Assert::assertTrue($response->hasHeader('X-Key'));
        Assert::assertSame('abc', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function canAddSingleMiddlewareToaGroupWithoutWrappingInArray()
    {
        $request = new ServerRequest([], [], '/all', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $router->group(['middleware' => new AddHeaderMiddleware('X-Key', 'abc')], function ($group) use (&$count) {
            $count++;
            Assert::assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc123', $response->getBody()->getContents());
        Assert::assertTrue($response->hasHeader('X-Key'));
        Assert::assertSame('abc', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function canAddBaseMiddlewareToBeAppliedToAllRoutes()
    {
        $router = new Router(new RouteCollector());
        $router->setBaseMiddleware([
            new AddHeaderMiddleware('X-Key', 'abc'),
        ]);
        $count = 0;

        $router->get('one', function () use (&$count) {
            $count++;
            return 'abc123';
        });

        $router->get('two', function () use (&$count) {
            $count++;
            return 'abc123';
        });

        $response1 = $router->match(new ServerRequest([], [], '/one', 'GET'));
        $response2 = $router->match(new ServerRequest([], [], '/two', 'GET'));

        Assert::assertSame(2, $count);

        Assert::assertSame(200, $response1->getStatusCode());
        Assert::assertSame('abc123', $response1->getBody()->getContents());
        Assert::assertTrue($response1->hasHeader('X-Key'));
        Assert::assertSame('abc', $response1->getHeader('X-Key')[0]);

        Assert::assertSame(200, $response2->getStatusCode());
        Assert::assertSame('abc123', $response2->getBody()->getContents());
        Assert::assertTrue($response2->hasHeader('X-Key'));
        Assert::assertSame('abc', $response2->getHeader('X-Key')[0]);
    }

    /** @test */
    public function canResolveMiddlewareOnaRouteUsingaCustomResolver()
    {
        $resolver = $this->createMockMiddlewareResolverWithHeader('X-Key', 'abc');
        $request  = new ServerRequest([], [], '/test/123', 'GET');
        $router   = new Router(new RouteCollector(), null, null, $resolver);

        $router->get('/test/123', function () {
        })->middleware('middleware-key');

        $response = $router->match($request);

        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertTrue($response->hasHeader('X-Key'));
        Assert::assertSame('abc', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function canResolveMiddlewareOnaGroupUsingaCustomResolver()
    {
        $resolver = $this->createMockMiddlewareResolverWithHeader('X-Key', 'abc');
        $request  = new ServerRequest([], [], '/test/123', 'GET');
        $router   = new Router(new RouteCollector(), null, null, $resolver);

        $router->group(['middleware' => 'middleware-key'], function ($group) {
            $group->get('/test/123', function () {
            });
        });

        $response = $router->match($request);

        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertTrue($response->hasHeader('X-Key'));
        Assert::assertSame('abc', $response->getHeader('X-Key')[0]);
    }

    /** @test */
    public function canResolveGlobalMiddlewareUsingaCustomResolver()
    {
        $resolver = $this->createMockMiddlewareResolverWithHeader('X-Key', 'abc');
        $request  = new ServerRequest([], [], '/test/123', 'GET');
        $router   = new Router(new RouteCollector(), null, null, $resolver);
        $router->setBaseMiddleware(['middleware-key']);

        $router->get('/test/123', function () {
        });

        $response = $router->match($request);

        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertTrue($response->hasHeader('X-Key'));
        Assert::assertSame('abc', $response->getHeader('X-Key')[0]);
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
