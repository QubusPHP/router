<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Psr11\Container;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;
use Qubus\Tests\Routing\Controllers\TestConstructorParamController;
use Qubus\Tests\Routing\Requests\TestRequest;
use Qubus\Tests\Routing\Services\TestService;
use ReflectionException;
use stdClass;
use TypeError;

class RouterDITest extends TestCase
{
    /** @test */
    public function canPassaContainerIntoConstructor()
    {
        $container = new Container(InjectorFactory::create([]));
        $router    = new Router(new RouteCollector(), $container);

        Assert::assertInstanceOf(Router::class, $router);
    }

    /** @test */
    public function containerPassedToConstructorMustBePsr11Compatible()
    {
        $this->expectException(TypeError::class);

        $container = new stdClass();
        $router    = new Router(new RouteCollector(), $container);

        Assert::assertInstanceOf(Router::class, $router);
    }

    /** @test */
    public function routeParamsAreInjectedIntoClosure()
    {
        $container = new Container(InjectorFactory::create([]));
        $router    = new Router(new RouteCollector(), $container);
        $count     = 0;

        $router->get('/posts/{postId}/comments/{commentId}', function (int $postId, int $commentId) use (&$count) {
            $count++;

            Assert::assertSame(1, $postId);
            Assert::assertSame(2, $commentId);

            return 'abc';
        });

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoClosure()
    {
        $container = new Container(InjectorFactory::create([]));
        $testServiceInstance = new TestService('abc123');
        $container->make(TestService::class, [$testServiceInstance]);

        $router = new Router(new RouteCollector(), $container);
        $count  = 0;

        $router->get('/test/route', function (TestService $test) use (&$count, $testServiceInstance) {
            $count++;

            Assert::assertSame($testServiceInstance, $test);
            Assert::assertSame('abc123', $test->value);

            return 'abc';
        });

        $request  = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoClosureWithParams()
    {
        $container = new Container(InjectorFactory::create([]));
        $testServiceInstance = new TestService('abc123');
        $container->make(TestService::class, [$testServiceInstance]);

        $router = new Router(new RouteCollector(), $container);
        $count  = 0;

        $router->get('/posts/{postId}/comments/{commentId}', function (TestService $test, int $postId, int $commentId) use (&$count, $testServiceInstance) {
            $count++;

            Assert::assertSame($testServiceInstance, $test);
            Assert::assertSame('abc123', $test->value);
            Assert::assertSame(1, $postId);
            Assert::assertSame(2, $commentId);

            return 'abc';
        });

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function routeParamsAreInjectedIntoClosureRegardlessOfParamOrder()
    {
        $container = new Container(InjectorFactory::create([]));
        $router    = new Router(new RouteCollector(), $container);
        $count     = 0;

        $router->get('/posts/{postId}/comments/{commentId}', function (int $commentId, int $postId) use (&$count) {
            $count++;

            Assert::assertSame(1, $postId);
            Assert::assertSame(2, $commentId);

            return 'abc';
        });

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function reflectionErrorIsThrownWhenTypehintsCantBeResolvedFromTheContainer()
    {
        $this->expectException(ReflectionException::class);

        $container = new Container(InjectorFactory::create([]));
        $router    = new Router(new RouteCollector(), $container);

        $router->get('/test/route', function (UndefinedType $test) {
        });

        $request  = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);
    }

    /** @test */
    public function routeParamsAreInjectedIntoControllerClass()
    {
        $container = new Container(InjectorFactory::create([]));
        $router    = new Router(new RouteCollector(), $container);

        $router->get('/posts/{postId}/comments/{commentId}', 'Qubus\Tests\Routing\Controllers\TestController@expectsInjectedParams');

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('$postId: 1 $commentId: 2', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoControllerClass()
    {
        $container = new Container(InjectorFactory::create([]));
        $testServiceInstance = new TestService('abc123');
        $container->make(TestService::class, [$testServiceInstance]);

        $router = new Router(new RouteCollector(), $container);

        $router->get('/test/route', 'Qubus\Tests\Routing\Controllers\TestController@typeHintTestService');

        $request  = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoControllerClassWithParams()
    {
        $container = new Container(InjectorFactory::create([]));
        $testServiceInstance = new TestService('abc123');
        $container->make(TestService::class, [$testServiceInstance]);

        $router = new Router(new RouteCollector(), $container);

        $router->get('/posts/{postId}/comments/{commentId}', 'Qubus\Tests\Routing\Controllers\TestController@typeHintTestServiceWithParams');

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('$postId: 1 $commentId: 2 TestService: abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canInjectRequestObject()
    {
        $container = new Container(InjectorFactory::create([]));
        $request   = new ServerRequest([], [], '/test/route', 'GET');
        $router    = new Router(new RouteCollector(), $container);
        $count     = 0;

        $router->get('/test/route', function (ServerRequest $injectedRequest) use (&$count) {
            $count++;

            Assert::assertInstanceOf(ServerRequest::class, $injectedRequest);
            Assert::assertSame('GET', $injectedRequest->getMethod());
            Assert::assertSame('/test/route', $injectedRequest->getUri()->getPath());

            return 'abc123';
        });

        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canInjectRequestObjectWithaBody()
    {
        $container = new Container(InjectorFactory::create([]));
        $request   = new ServerRequest([], [], '/test/route', 'POST', 'php://input', [], [], [], 'post body');
        $router    = new Router(new RouteCollector(), $container);
        $count     = 0;

        $router->post('/test/route', function (ServerRequest $injectedRequest) use (&$count) {
            $count++;

            Assert::assertInstanceOf(ServerRequest::class, $injectedRequest);
            Assert::assertSame('POST', $injectedRequest->getMethod());
            Assert::assertSame('/test/route', $injectedRequest->getUri()->getPath());
            Assert::assertSame('post body', $injectedRequest->getParsedBody());

            return 'abc123';
        });

        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canInjectRequestSubClass()
    {
        $container = new Container(InjectorFactory::create([]));
        $request   = new ServerRequest([], [], '/test/route', 'GET');
        $router    = new Router(new RouteCollector(), $container);

        $count = 0;

        $router->get('/test/route', function (TestRequest $injectedRequest) use (&$count) {
            $count++;

            Assert::assertInstanceOf(TestRequest::class, $injectedRequest);
            Assert::assertSame('GET', $injectedRequest->getMethod());
            Assert::assertSame('/test/route', $injectedRequest->getUri()->getPath());

            return 'abc123';
        });

        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function constructorParamsAreInjectedIntoControllerClass()
    {
        $container = new Container(InjectorFactory::create([]));
        $router              = new Router(new RouteCollector(), $container);
        $testServiceInstance = new TestService('abc123');
        $container->make(TestService::class, [$testServiceInstance]);

        $router->get('/test/url', [TestConstructorParamController::class, 'Qubus\Tests\Routing\Controllers\TestConstructorParamController@returnTestServiceValue']);

        $request  = new ServerRequest([], [], '/test/url', 'GET');
        $response = $router->match($request);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }
}
