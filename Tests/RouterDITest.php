<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\ServerRequest;
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
    public function canPassaContainerIntoConstructor()
    {
        $router = new Router(new RouteCollector(), $this->container);

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
        $router    = new Router(new RouteCollector(), $this->container);
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
        $testServiceInstance = new TestService('abc123');
        $this->container->make(TestService::class, [$testServiceInstance]);

        $router = new Router(new RouteCollector(), $this->container);
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
        $testServiceInstance = new TestService('abc123');
        $this->container->make(TestService::class, [$testServiceInstance]);

        $router = new Router(new RouteCollector(), $this->container);
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
        $router    = new Router(new RouteCollector(), $this->container);
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

        $router = new Router(new RouteCollector(), $this->container);

        $router->get('/test/route', function (UndefinedType $test) {
        });

        $request  = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);
    }

    /** @test */
    public function routeParamsAreInjectedIntoControllerClass()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $router->get('/posts/{postId}/comments/{commentId}', 'Qubus\Tests\Routing\Controllers\TestController@expectsInjectedParams');

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('$postId: 1 $commentId: 2', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoControllerClass()
    {
        $testServiceInstance = new TestService('abc123');
        $this->container->make(TestService::class, [$testServiceInstance]);

        $router = new Router(new RouteCollector(), $this->container);

        $router->get('/test/route', 'Qubus\Tests\Routing\Controllers\TestController@typeHintTestService');

        $request  = new ServerRequest([], [], '/test/route', 'GET');
        $response = $router->match($request);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function typehintsAreInjectedIntoControllerClassWithParams()
    {
        $testServiceInstance = new TestService('abc123');
        $this->container->make(TestService::class, [$testServiceInstance]);

        $router = new Router(new RouteCollector(), $this->container);

        $router->get('/posts/{postId}/comments/{commentId}', 'Qubus\Tests\Routing\Controllers\TestController@typeHintTestServiceWithParams');

        $request  = new ServerRequest([], [], '/posts/1/comments/2', 'GET');
        $response = $router->match($request);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('$postId: 1 $commentId: 2 TestService: abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canInjectRequestObject()
    {
        $request   = new ServerRequest([], [], '/test/route', 'GET');
        $router    = new Router(new RouteCollector(), $this->container);
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
        $request   = new ServerRequest([], [], '/test/route', 'POST', 'php://input', [], [], [], 'post body');
        $router    = new Router(new RouteCollector(), $this->container);
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
        $request   = new ServerRequest([], [], '/test/route', 'GET');
        $router    = new Router(new RouteCollector(), $this->container);

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
        $router              = new Router(new RouteCollector(), $this->container);
        $testServiceInstance = new TestService('abc123');
        $this->container->make(TestService::class, [$testServiceInstance]);

        $router->get('/test/url', [TestConstructorParamController::class, 'Qubus\Tests\Routing\Controllers\TestConstructorParamController@returnTestServiceValue']);

        $request  = new ServerRequest([], [], '/test/url', 'GET');
        $response = $router->match($request);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }
}
