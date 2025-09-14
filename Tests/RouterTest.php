<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Exception;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Http\Request;
use Qubus\Http\Response;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Injector;
use Qubus\Injector\Psr11\Container;
use Qubus\Routing\Exceptions\NamedRouteNotFoundException;
use Qubus\Routing\Exceptions\RouteControllerNotFoundException;
use Qubus\Routing\Exceptions\RouteMethodNotFoundException;
use Qubus\Routing\Exceptions\RouteParamFailedConstraintException;
use Qubus\Routing\Exceptions\RouteParseException;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Route\RouteGroup;
use Qubus\Routing\Route\RouteParams;
use Qubus\Routing\Router;
use Qubus\Routing\Tests\Fixtures\RouterMixin;

use function dd;

class RouterTest extends TestCase
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
    public function testMapReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->map([Router::HTTP_METHOD_GET], '/test/123', function () {
        });

        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertSame(['GET'], $route->methods);
        Assert::assertSame('/test/123', $route->uri);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testMapAcceptsLowercaseVerbs()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->map(['get', 'head', 'post', 'put', 'patch', 'delete', 'options'], '/test/123', function () {
        });

        Assert::assertSame(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route->methods);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testGetReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/test/123', function () {
        });

        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertSame(['GET'], $route->methods);
        Assert::assertSame('/test/123', $route->uri);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testHeadReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->head('/test/123', function () {
        });

        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertSame(['HEAD'], $route->methods);
        Assert::assertSame('/test/123', $route->uri);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testPostReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->post('/test/123', function () {
        });

        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertSame(['POST'], $route->methods);
        Assert::assertSame('/test/123', $route->uri);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testPatchReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->patch('/test/123', function () {
        });

        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertSame(['PATCH'], $route->methods);
        Assert::assertSame('/test/123', $route->uri);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testPutReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->put('/test/123', function () {
        });

        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertSame(['PUT'], $route->methods);
        Assert::assertSame('/test/123', $route->uri);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testDeleteReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->delete('/test/123', function () {
        });

        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertSame(['DELETE'], $route->methods);
        Assert::assertSame('/test/123', $route->uri);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testOptionsReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->options('/test/123', function () {
        });

        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertSame(['OPTIONS'], $route->methods);
        Assert::assertSame('/test/123', $route->uri);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     */
    public function testMapRemovesTrailingSlashFromUri()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->map([Router::HTTP_METHOD_GET], '/test/123/', function () {
        });

        Assert::assertInstanceOf(Route::class, $route);
        Assert::assertSame(['GET'], $route->methods);
        Assert::assertSame('/test/123', $route->uri);
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     * @throws Exception
     */
    public function testNoReturnFromRouteActionResultsIna204StatusCode()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route    = $router->get('test/123', function () use (&$count) {
            $count++;
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame(204, $response->getStatusCode());
    }

    /** @test
     * @throws TooLateToAddNewRouteException
     * @throws Exception
     */
    public function testLeadingSlashIsOptionalWhenCreatingaRoute()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route    = $router->get('test/123', function () use (&$count) {
            $count++;
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function testMatchReturnsaResponseObject()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function testMatchDoesNotMutateReturnedResponseObject()
    {
        $request  = new ServerRequest([], [], '/test/123', 'GET');
        $router   = new Router(new RouteCollector(), $this->container);
        $response = new TextResponse('This is a test', 202, ['content-type' => 'text/plain']);

        $route          = $router->get('/test/123', function () use (&$response) {
            return $response;
        });
        $routerResponse = $router->match($request);

        Assert::assertSame($response, $routerResponse);
    }

    /** @test */
    public function testMatchReturnsa404ResponseObjectWhenRouteIsNotFound()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);

        $response = $router->match($request);

        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function testMatchWorksWithaClosure()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function testMatchUriWithTrailingWhenRouteHasBeenDefinedWithoutTrailingSlash()
    {
        $request = new ServerRequest([], [], '/test/123/', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function testMatchUriWithTrailingWhenRouteHasBeenDefinedWithTrailingSlash()
    {
        $request = new ServerRequest([], [], '/test/123/', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route    = $router->get('/test/123/', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function testMatchUriWithoutTrailingWhenRouteHasBeenDefinedWithoutTrailingSlash()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function testMatchUriWithoutTrailingWhenRouteHasBeenDefinedWithTrailingSlash()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route    = $router->get('/test/123/', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function testMatchWorksWithaClassAndMethodString()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);

        $route    = $router->get('/test/123', 'Qubus\Tests\Routing\Controllers\TestController@returnHelloWorld');
        $response = $router->match($request);

        Assert::assertSame('Hello World!', $response->getBody()->getContents());
    }

    /** @test */
    public function testMatchThrowsExceptionWithInvalidClassAndStringMethod()
    {
        $this->expectException(RouteParseException::class);

        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/test/123', 'Qubus\Tests\Routing\Controllers\TestController:returnHelloWorld');
    }

    /** @test */
    public function testMatchThrowsExceptionWhenClassAndStringMethodContainsAnUnfoundClass()
    {
        $this->expectException(RouteControllerNotFoundException::class);

        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/test/123', 'Qubus\Tests\Routing\Controllers\UndefinedController@returnHelloWorld');
    }

    /** @test */
    public function testMatchThrowsExceptionWhenClassAndStringMethodContainsAnUnfoundMethod()
    {
        $this->expectException(RouteMethodNotFoundException::class);

        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/test/123', 'Qubus\Tests\Routing\Controllers\TestController@undefinedMethod');
    }

    /** @test */
    public function testParamsAreParsedAndPassedIntoCallbackFunction()
    {
        $request = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/posts/{postId}/comments/{commentId}', function ($params) use (&$count) {
            $count++;

            Assert::assertInstanceOf(RouteParams::class, $params);
            Assert::assertSame('123', $params->postId);
            Assert::assertSame('abc', $params->commentId);
        });
        $router->match($request);

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testParamsAreParsedAndPassedIntoCallbackFunctionWhenSurroundedByWhitespace()
    {
        $request = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/posts/{postId}/comments/{commentId}', function ($params) use (&$count) {
            $count++;

            Assert::assertInstanceOf(RouteParams::class, $params);
            Assert::assertSame('123', $params['postId']);
            Assert::assertSame('abc', $params['commentId']);
        });
        dd($route);
        $router->match($request);

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddRegexConstraintsOnParamsAsKeyValue()
    {
        $matchingRequest    = new ServerRequest([], [], '/posts/123/comments', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments', 'GET');
        $router             = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/posts/{postId}/comments', function () use (&$count) {
            $count++;
        })->where('postId', '[0-9]+');

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddMultipleRegexConstraintsOnParamsAsKeyValue()
    {
        $matchingRequest    = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments/123', 'GET');
        $router             = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/posts/{postId}/comments/{commentId}', function () use (&$count) {
            $count++;
        })->where('postId', '[0-9]+')->where('commentId', '[a-z]+');

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddRegexConstraintsOnParamsAsArray()
    {
        $matchingRequest    = new ServerRequest([], [], '/posts/123/comments', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments', 'GET');
        $router             = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/posts/{postId}/comments', function () use (&$count) {
            $count++;
        })->where(['postId' => '[0-9]+']);

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanAddMultipleRegexConstraintsOnParamsAsArray()
    {
        $matchingRequest    = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments/123', 'GET');
        $router             = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/posts/{postId}/comments/{commentId}', function () use (&$count) {
            $count++;
        })->where([
            'postId'    => '[0-9]+',
            'commentId' => '[a-z]+',
        ]);

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        Assert::assertSame(1, $count);
    }

    /** @test */
    public function testCanProvideOptionalParams()
    {
        $matchingRequest1   = new ServerRequest([], [], '/posts/123', 'GET');
        $matchingRequest2   = new ServerRequest([], [], '/posts', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments', 'GET');
        $router             = new Router(new RouteCollector(), $this->container);

        $count = 0;

        $route = $router->get('/posts/{postId?}', function (?string $postId = null) use (&$count) {
            $count++;
        });

        $router->match($matchingRequest1);
        $router->match($matchingRequest2);
        $router->match($nonMatchingRequest);

        Assert::assertSame(2, $count);
    }

    /** @test */
    public function testCanGenerateCanonicalUriWithTrailingSlashForNamedRoute()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/posts/all', function () {
        })->name('test.name');

        Assert::assertSame('/posts/all/', $router->url('test.name'));
    }

    /** @test */
    public function testCanGenerateCanonicalUriWithTrailingSlashForNamedRouteWithParams()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/posts/{id}/comments', function () {
        })->name('test.name');

        Assert::assertSame('/posts/123/comments/', $router->url('test.name', ['id' => 123]));
    }

    /** @test */
    public function testUrlThrowsExceptionWhenProvidedParamsFailTheRegexConstraints()
    {
        $this->expectException(RouteParamFailedConstraintException::class);

        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/posts/{id}/comments', function () {
        })
            ->name('test.name')
            ->where('id', '[a-z]+');

        $router->url('test.name', ['id' => 123]);
    }

    /** @test */
    public function testGeneratingaUrlForaNamedRouteThatDoesntExistThrowsAnException()
    {
        $this->expectException(NamedRouteNotFoundException::class);

        $router = new Router(new RouteCollector(), $this->container);

        $router->url('test.name');
    }

    /** @test */
    public function testCanGenerateCanonicalUriAfterMatchHasBeenCalled()
    {
        $router = new Router(new RouteCollector(), $this->container);

        $route   = $router->get('/posts/all', function () {
        })->name('test.name');
        $request = new ServerRequest([], [], '/does/not/match', 'GET');
        $router->match($request, 'GET');

        Assert::assertSame('/posts/all/', $router->url('test.name'));
    }

    /** @test */
    public function testAddingRoutesAfterCallingUrlThrowsAnException()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $router = new Router(new RouteCollector(), $this->container);

        $route = $router->get('posts/all', function () {
        })->name('test.name');
        $router->url('test.name');

        $route = $router->get('another/url', function () {
        });
    }

    /** @test */
    public function testAddingRoutesAfterCallingMatchThrowsAnException()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $request = new ServerRequest([], [], '/posts/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);

        $route = $router->get('posts/all', function () {
        });
        $router->match($request);

        $route = $router->get('another/url', function () {
        });
    }

    /** @test */
    public function testCanAddRoutesInaGroup()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $router->group(['prefix' => 'prefix'], function ($group) use (&$count) {
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
    }

    /** @test */
    public function testCanAddRoutesInaGroupUsingArrayAsFirstParam()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $router->group(['prefix' => 'prefix'], function ($group) use (&$count) {
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
    }

    /** @test */
    public function testCanAddRoutesInaGroupUsingArrayAsFirstParamWithNoPrefix()
    {
        $request = new ServerRequest([], [], '/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $router->group([], function ($group) use (&$count) {
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
    }

    /** @test */
    public function testGroupPrefixesWorkWithLeadingSlash()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $router->group(['prefix' => '/prefix'], function ($group) use (&$count) {
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
    }

    /** @test */
    public function testGroupPrefixesWorkWithTrailingSlash()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $router->group(['prefix' => 'prefix/'], function ($group) use (&$count) {
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
    }

    /** @test */
    public function testCanSetBasePath()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $router->setBasePath('/base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function testCanSetBasePathWithoutTrailingSlash()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $router->setBasePath('/base-path');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function testCanSetBasePathWithoutLeadingSlash()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $router->setBasePath('base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function testCanSetBasePathWithoutLeadingOrTrailingSlash()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $router->setBasePath('base-path');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function testCanUpdateBasePathAfterMatchHasBeenCalled()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->setBasePath('/base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });

        $request1  = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $response1 = $router->match($request1);

        $router->setBasePath('/updated-base-path/');

        $request2  = new ServerRequest([], [], '/updated-base-path/prefix/all', 'GET');
        $response2 = $router->match($request2);

        Assert::assertSame(2, $count);
        Assert::assertSame('abc123', $response1->getBody()->getContents());
        Assert::assertSame('abc123', $response2->getBody()->getContents());
    }

    /** @test */
    public function testCanAddMiddlewareAsaClosureToaRoute()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        })->middleware(function (ServerRequestInterface $request, callable $next) use (&$count) {
            $count++;

            $response = $next($request);
            return $response->withHeader('X-key', 'value');
        });
        $response = $router->match($request);

        Assert::assertSame(2, $count);
        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertTrue($response->hasHeader('X-key'));
        Assert::assertSame('value', $response->getHeader('X-key')[0]);
    }

    /** @test */
    public function testCanGetCurrentlyMatchedRoute()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });

        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame($route, $router->currentRoute());
    }

    /** @test */
    public function testCanGetCurrentlyMatchedRouteName()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);
        $count   = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        })->name('test123');

        $response = $router->match($request);

        Assert::assertSame(1, $count);
        Assert::assertSame('test123', $router->currentRouteName());
    }

    /** @test */
    public function testCurrentRouteNameReturnsNullWhenMatchNotYetCalled()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/test/123', function () {
            return 'abc123';
        })->name('test123');

        Assert::assertSame(null, $router->currentRouteName());
    }

    /** @test */
    public function testCurrentRouteNameReturnsNullWhenMatchedRouteHasNoName()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector(), $this->container);

        $route = $router->get('/test/123', function () {
            return 'abc123';
        });

        $response = $router->match($request);

        Assert::assertSame(null, $router->currentRouteName());
    }

    /** @test */
    public function testCanGetListOfRegisteredRoutes()
    {
        $router = new Router(new RouteCollector(), $this->container);
        $route1 = $router->get('/test/123', function () {
        });
        $route2 = $router->get('/test/456', function () {
        });

        $routes = $router->routes;

        Assert::assertCount(2, $routes);
        Assert::assertContains($route1, $routes);
        Assert::assertContains($route2, $routes);
    }

    /**
     * @test
     */
    public function testCanExtendPostBehaviorWithMacros()
    {
        Router::macro('testFunctionAddedByMacro', function () {
            return 'abc123';
        });

        $queryBuilder = new Router(new RouteCollector(), $this->container);

        Assert::assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        Assert::assertSame('abc123', Router::testFunctionAddedByMacro());
    }

    /**
     * @test
     */
    public function testCanExtendPostBehaviorWithMixin()
    {
        Router::mixin(new RouterMixin());

        $queryBuilder = new Router(new RouteCollector(), $this->container);

        Assert::assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
    }
}
