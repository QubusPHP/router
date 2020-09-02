<?php

namespace Qubus\Router\Test;

use DI\ContainerBuilder;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Qubus\Router\Route\RouteCollector;
use Qubus\Router\Router;
use Qubus\Router\Test\Requests\TestRequest;
use Qubus\Router\Test\Services\TestService;
use ReflectionException;
use stdClass;
use TypeError;

class RouterDITest extends TestCase
{
    /** @test */
    public function canPassaContainerIntoConstructor()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router    = new Router(new RouteCollector(), $container);

        $this->assertInstanceOf(Router::class, $router);
    }

    /** @test */
    public function containerPassedToConstructorMustBePsr11Compatible()
    {
        $this->expectException(TypeError::class);

        $container = new stdClass();
        $router    = new Router(new RouteCollector(), $container);

        $this->assertInstanceOf(Router::class, $router);
    }

    /** @test */
    public function routeParamsAreInjectedIntoClosure()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router    = new Router(new RouteCollector(), $container);
        $count     = 0;

        $router->get('/posts/{postId}/comments/{commentId}', function (int $postId, int $commentId) use (&$count) {
            $count++;

            $this->assertSame(1, $postId);
            $this->assertSame(2, $commentId);

            return 'abc';
        });

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoClosure()
    {
        $container           = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router = new Router(new RouteCollector(), $container);
        $count  = 0;

        $router->get('/test/route', function (TestService $test) use (&$count, $testServiceInstance) {
            $count++;

            $this->assertSame($testServiceInstance, $test);
            $this->assertSame('abc123', $test->value);

            return 'abc';
        });

        $request  = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoClosureWithParams()
    {
        $container           = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router = new Router(new RouteCollector(), $container);
        $count  = 0;

        $router->get('/posts/{postId}/comments/{commentId}', function (TestService $test, int $postId, int $commentId) use (&$count, $testServiceInstance) {
            $count++;

            $this->assertSame($testServiceInstance, $test);
            $this->assertSame('abc123', $test->value);
            $this->assertSame(1, $postId);
            $this->assertSame(2, $commentId);

            return 'abc';
        });

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function routeParamsAreInjectedIntoClosureRegardlessOfParamOrder()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router    = new Router(new RouteCollector(), $container);
        $count     = 0;

        $router->get('/posts/{postId}/comments/{commentId}', function (int $commentId, int $postId) use (&$count) {
            $count++;

            $this->assertSame(1, $postId);
            $this->assertSame(2, $commentId);

            return 'abc';
        });

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc', $response->getBody()->getContents());
    }

    /** @test */
    public function reflectionErrorIsThrownWhenTypehintsCantBeResolvedFromTheContainer()
    {
        $this->expectException(ReflectionException::class);

        $container = ContainerBuilder::buildDevContainer();
        $router    = new Router(new RouteCollector(), $container);

        $router->get('/test/route', function (UndefinedType $test) {
        });

        $request  = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);
    }

    /** @test */
    public function routeParamsAreInjectedIntoControllerClass()
    {
        $container = ContainerBuilder::buildDevContainer();
        $router    = new Router(new RouteCollector(), $container);

        $router->get('/posts/{postId}/comments/{commentId}', 'Qubus\Router\Test\Controllers\TestController@expectsInjectedParams');

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('$postId: 1 $commentId: 2', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoControllerClass()
    {
        $container           = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router = new Router(new RouteCollector(), $container);

        $router->get('/test/route', 'Qubus\Router\Test\Controllers\TestController@typeHintTestService');

        $request  = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoControllerClassWithParams()
    {
        $container           = ContainerBuilder::buildDevContainer();
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router = new Router(new RouteCollector(), $container);

        $router->get('/posts/{postId}/comments/{commentId}', 'Qubus\Router\Test\Controllers\TestController@typeHintTestServiceWithParams');

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('$postId: 1 $commentId: 2 TestService: abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canInjectRequestObject()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request   = new ServerRequest([], [], '/test/route', 'GET');
        $router    = new Router(new RouteCollector(), $container);
        $count     = 0;

        $router->get('/test/route', function (ServerRequest $injectedRequest) use (&$count) {
            $count++;

            $this->assertInstanceOf(ServerRequest::class, $injectedRequest);
            $this->assertSame('GET', $injectedRequest->getMethod());
            $this->assertSame('/test/route', $injectedRequest->getUri()->getPath());

            return 'abc123';
        });

        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canInjectRequestObjectWithaBody()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request   = new ServerRequest([], [], '/test/route', 'POST', 'php://input', [], [], [], 'post body');
        $router    = new Router(new RouteCollector(), $container);
        $count     = 0;

        $router->post('/test/route', function (ServerRequest $injectedRequest) use (&$count) {
            $count++;

            $this->assertInstanceOf(ServerRequest::class, $injectedRequest);
            $this->assertSame('POST', $injectedRequest->getMethod());
            $this->assertSame('/test/route', $injectedRequest->getUri()->getPath());
            $this->assertSame('post body', $injectedRequest->getParsedBody());

            return 'abc123';
        });

        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canInjectRequestSubClass()
    {
        $container = ContainerBuilder::buildDevContainer();
        $request   = new ServerRequest([], [], '/test/route', 'GET');
        $router    = new Router(new RouteCollector(), $container);

        $count = 0;

        $router->get('/test/route', function (TestRequest $injectedRequest) use (&$count) {
            $count++;

            $this->assertInstanceOf(TestRequest::class, $injectedRequest);
            $this->assertSame('GET', $injectedRequest->getMethod());
            $this->assertSame('/test/route', $injectedRequest->getUri()->getPath());

            return 'abc123';
        });

        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function constructorParamsAreInjectedIntoControllerClass()
    {
        $container           = ContainerBuilder::buildDevContainer();
        $router              = new Router(new RouteCollector(), $container);
        $testServiceInstance = new TestService('abc123');
        $container->set(TestService::class, $testServiceInstance);

        $router->get('/test/url', 'Qubus\Router\Test\Controllers\TestConstructorParamController@returnTestServiceValue');

        $request  = new ServerRequest([], [], '/test/url', 'GET');
        $response = $router->match($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }
}
