<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Routing\Exceptions\NamedRouteNotFoundException;
use Qubus\Routing\Exceptions\RouteControllerNotFoundException;
use Qubus\Routing\Exceptions\RouteMethodNotFoundException;
use Qubus\Routing\Exceptions\RouteParamFailedConstraintException;
use Qubus\Routing\Exceptions\RouteParseException;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Route\RouteGroup;
use Qubus\Routing\Route\RouteParams;
use Qubus\Routing\Router;

class RouterTest extends TestCase
{
    /** @test */
    public function mapReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector());

        $route = $router->map([Router::HTTP_METHOD_GET], '/test/123', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function mapAcceptsLowercaseVerbs()
    {
        $router = new Router(new RouteCollector());

        $route = $router->map(['get', 'head', 'post', 'put', 'patch', 'delete', 'options'], '/test/123', function () {
        });

        $this->assertSame(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route->getMethods());
    }

    /** @test */
    public function getReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector());

        $route = $router->get('/test/123', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function headReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector());

        $route = $router->head('/test/123', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['HEAD'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function postReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector());

        $route = $router->post('/test/123', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['POST'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function patchReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector());

        $route = $router->patch('/test/123', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['PATCH'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function putReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector());

        $route = $router->put('/test/123', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['PUT'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function deleteReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector());

        $route = $router->delete('/test/123', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['DELETE'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function optionsReturnsaRouteObject()
    {
        $router = new Router(new RouteCollector());

        $route = $router->options('/test/123', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['OPTIONS'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function mapRemovesTrailingSlashFromUri()
    {
        $router = new Router(new RouteCollector());

        $route = $router->map([Router::HTTP_METHOD_GET], '/test/123/', function () {
        });

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame(['GET'], $route->getMethods());
        $this->assertSame('/test/123', $route->getUri());
    }

    /** @test */
    public function noReturnFromRouteActionResultsIna204StatusCode()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('test/123', function () use (&$count) {
            $count++;
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(204, $response->getStatusCode());
    }

    /** @test */
    public function leadingSlashIsOptionalWhenCreatingaRoute()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('test/123', function () use (&$count) {
            $count++;
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function matchReturnsaResponseObject()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /** @test */
    public function matchDoesNotMutateReturnedResponseObject()
    {
        $request  = new ServerRequest([], [], '/test/123', 'GET');
        $router   = new Router(new RouteCollector());
        $response = new TextResponse('This is a test', 202, ['content-type' => 'text/plain']);

        $route          = $router->get('/test/123', function () use (&$response) {
            return $response;
        });
        $routerResponse = $router->match($request);

        $this->assertSame($response, $routerResponse);
    }

    /** @test */
    public function matchReturnsa404ResponseObjectWhenRouteIsNotFound()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());

        $response = $router->match($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function matchWorksWithaClosure()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function matchUriWithTrailingWhenRouteHasBeenDefinedWithoutTrailingSlash()
    {
        $request = new ServerRequest([], [], '/test/123/', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function matchUriWithTrailingWhenRouteHasBeenDefinedWithTrailingSlash()
    {
        $request = new ServerRequest([], [], '/test/123/', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('/test/123/', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function matchUriWithoutTrailingWhenRouteHasBeenDefinedWithoutTrailingSlash()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function matchUriWithoutTrailingWhenRouteHasBeenDefinedWithTrailingSlash()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route    = $router->get('/test/123/', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function matchWorksWithaClassAndMethodString()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());

        $route    = $router->get('/test/123', 'Qubus\Tests\Routing\Controllers\TestController@returnHelloWorld');
        $response = $router->match($request);

        $this->assertSame('Hello World!', $response->getBody()->getContents());
    }

    /** @test */
    public function matchThrowsExceptionWithInvalidClassAndStringMethod()
    {
        $this->expectException(RouteParseException::class);

        $router = new Router(new RouteCollector());

        $route = $router->get('/test/123', 'Qubus\Tests\Routing\Controllers\TestController:returnHelloWorld');
    }

    /** @test */
    public function matchThrowsExceptionWhenClassAndStringMethodContainsAnUnfoundClass()
    {
        $this->expectException(RouteControllerNotFoundException::class);

        $router = new Router(new RouteCollector());

        $route = $router->get('/test/123', 'Qubus\Tests\Routing\Controllers\UndefinedController@returnHelloWorld');
    }

    /** @test */
    public function matchThrowsExceptionWhenClassAndStringMethodContainsAnUnfoundMethod()
    {
        $this->expectException(RouteMethodNotFoundException::class);

        $router = new Router(new RouteCollector());

        $route = $router->get('/test/123', 'Qubus\Tests\Routing\Controllers\TestController@undefinedMethod');
    }

    /** @test */
    public function paramsAreParsedAndPassedIntoCallbackFunction()
    {
        $request = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $router  = new Router(new RouteCollector());

        $route = $router->get('/posts/{postId}/comments/{commentId}', function ($params) use (&$count) {
            $count++;

            $this->assertInstanceOf(RouteParams::class, $params);
            $this->assertSame('123', $params->postId);
            $this->assertSame('abc', $params->commentId);
        });
        $router->match($request);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function paramsAreParsedAndPassedIntoCallbackFunctionWhenSurroundedByWhitespace()
    {
        $request = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $router  = new Router(new RouteCollector());

        $route = $router->get('/posts/{ postId }/comments/{ commentId }', function ($params) use (&$count) {
            $count++;

            $this->assertInstanceOf(RouteParams::class, $params);
            $this->assertSame('123', $params->postId);
            $this->assertSame('abc', $params->commentId);
        });
        $router->match($request);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function canAddRegexConstraintsOnParamsAsKeyValue()
    {
        $matchingRequest    = new ServerRequest([], [], '/posts/123/comments', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments', 'GET');
        $router             = new Router(new RouteCollector());

        $route = $router->get('/posts/{postId}/comments', function () use (&$count) {
            $count++;
        })->where('postId', '[0-9]+');

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function canAddMultipleRegexConstraintsOnParamsAsKeyValue()
    {
        $matchingRequest    = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments/123', 'GET');
        $router             = new Router(new RouteCollector());

        $route = $router->get('/posts/{postId}/comments/{commentId}', function () use (&$count) {
            $count++;
        })->where('postId', '[0-9]+')->where('commentId', '[a-z]+');

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function canAddRegexConstraintsOnParamsAsArray()
    {
        $matchingRequest    = new ServerRequest([], [], '/posts/123/comments', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments', 'GET');
        $router             = new Router(new RouteCollector());

        $route = $router->get('/posts/{postId}/comments', function () use (&$count) {
            $count++;
        })->where(['postId' => '[0-9]+']);

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function canAddMultipleRegexConstraintsOnParamsAsArray()
    {
        $matchingRequest    = new ServerRequest([], [], '/posts/123/comments/abc', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments/123', 'GET');
        $router             = new Router(new RouteCollector());

        $route = $router->get('/posts/{postId}/comments/{commentId}', function () use (&$count) {
            $count++;
        })->where([
            'postId'    => '[0-9]+',
            'commentId' => '[a-z]+',
        ]);

        $router->match($matchingRequest);
        $router->match($nonMatchingRequest);

        $this->assertSame(1, $count);
    }

    /** @test */
    public function canProvideOptionalParams()
    {
        $matchingRequest1   = new ServerRequest([], [], '/posts/123', 'GET');
        $matchingRequest2   = new ServerRequest([], [], '/posts', 'GET');
        $nonMatchingRequest = new ServerRequest([], [], '/posts/abc/comments', 'GET');
        $router             = new Router(new RouteCollector());

        $count = 0;

        $route = $router->get('/posts/{postId?}', function ($postId) use (&$count) {
            $count++;
        });

        $router->match($matchingRequest1);
        $router->match($matchingRequest2);
        $router->match($nonMatchingRequest);

        $this->assertSame(2, $count);
    }

    /** @test */
    public function canGenerateCanonicalUriWithTrailingSlashForNamedRoute()
    {
        $router = new Router(new RouteCollector());

        $route = $router->get('/posts/all', function () {
        })->name('test.name');

        $this->assertSame('/posts/all/', $router->url('test.name'));
    }

    /** @test */
    public function canGenerateCanonicalUriWithTrailingSlashForNamedRouteWithParams()
    {
        $router = new Router(new RouteCollector());

        $route = $router->get('/posts/{id}/comments', function () {
        })->name('test.name');

        $this->assertSame('/posts/123/comments/', $router->url('test.name', ['id' => 123]));
    }

    /** @test */
    public function urlThrowsExceptionWhenProvidedParamsFailTheRegexConstraints()
    {
        $this->expectException(RouteParamFailedConstraintException::class);

        $router = new Router(new RouteCollector());

        $route = $router->get('/posts/{id}/comments', function () {
        })
            ->name('test.name')
            ->where('id', '[a-z]+');

        $router->url('test.name', ['id' => 123]);
    }

    /** @test */
    public function generatingaUrlForaNamedRouteThatDoesntExistThrowsAnException()
    {
        $this->expectException(NamedRouteNotFoundException::class);

        $router = new Router(new RouteCollector());

        $router->url('test.name');
    }

    /** @test */
    public function canGenerateCanonicalUriAfterMatchHasBeenCalled()
    {
        $router = new Router(new RouteCollector());

        $route   = $router->get('/posts/all', function () {
        })->name('test.name');
        $request = new ServerRequest([], [], '/does/not/match', 'GET');
        $router->match($request, 'GET');

        $this->assertSame('/posts/all/', $router->url('test.name'));
    }

    /** @test */
    public function addingRoutesAfterCallingUrlThrowsAnException()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $router = new Router(new RouteCollector());

        $route = $router->get('posts/all', function () {
        })->name('test.name');
        $router->url('test.name');

        $route = $router->get('another/url', function () {
        });
    }

    /** @test */
    public function addingRoutesAfterCallingMatchThrowsAnException()
    {
        $this->expectException(TooLateToAddNewRouteException::class);

        $request = new ServerRequest([], [], '/posts/all', 'GET');
        $router  = new Router(new RouteCollector());

        $route = $router->get('posts/all', function () {
        });
        $router->match($request);

        $route = $router->get('another/url', function () {
        });
    }

    /** @test */
    public function canAddRoutesInaGroup()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $router->group(['prefix' => 'prefix'], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canAddRoutesInaGroupUsingArrayAsFirstParam()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $router->group(['prefix' => 'prefix'], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canAddRoutesInaGroupUsingArrayAsFirstParamWithNoPrefix()
    {
        $request = new ServerRequest([], [], '/all', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $router->group([], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function groupPrefixesWorkWithLeadingSlash()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $router->group(['prefix' => '/prefix'], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function groupPrefixesWorkWithTrailingSlash()
    {
        $request = new ServerRequest([], [], '/prefix/all', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $router->group(['prefix' => 'prefix/'], function ($group) use (&$count) {
            $count++;
            $this->assertInstanceOf(RouteGroup::class, $group);

            $group->get('all', function () {
                return 'abc123';
            });
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canSetBasePath()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router  = new Router(new RouteCollector());
        $router->setBasePath('/base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canSetBasePathWithoutTrailingSlash()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router  = new Router(new RouteCollector());
        $router->setBasePath('/base-path');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canSetBasePathWithoutLeadingSlash()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router  = new Router(new RouteCollector());
        $router->setBasePath('base-path/');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canSetBasePathWithoutLeadingOrTrailingSlash()
    {
        $request = new ServerRequest([], [], '/base-path/prefix/all', 'GET');
        $router  = new Router(new RouteCollector());
        $router->setBasePath('base-path');
        $count = 0;

        $router->get('prefix/all', function () use (&$count) {
            $count++;

            return 'abc123';
        });
        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('abc123', $response->getBody()->getContents());
    }

    /** @test */
    public function canUpdateBasePathAfterMatchHasBeenCalled()
    {
        $router = new Router(new RouteCollector());
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

        $this->assertSame(2, $count);
        $this->assertSame('abc123', $response1->getBody()->getContents());
        $this->assertSame('abc123', $response2->getBody()->getContents());
    }

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
            return $response->withHeader('X-key', 'value');
        });
        $response = $router->match($request);

        $this->assertSame(2, $count);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->hasHeader('X-key'));
        $this->assertSame('value', $response->getHeader('X-key')[0]);
    }

    /** @test */
    public function canGetCurrentlyMatchedRoute()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        });

        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame($route, $router->currentRoute());
    }

    /** @test */
    public function canGetCurrentlyMatchedRouteName()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());
        $count   = 0;

        $route = $router->get('/test/123', function () use (&$count) {
            $count++;

            return 'abc123';
        })->name('test123');

        $response = $router->match($request);

        $this->assertSame(1, $count);
        $this->assertSame('test123', $router->currentRouteName());
    }

    /** @test */
    public function currentRouteNameReturnsNullWhenMatchNotYetCalled()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());

        $route = $router->get('/test/123', function () {
            return 'abc123';
        })->name('test123');

        $this->assertSame(null, $router->currentRouteName());
    }

    /** @test */
    public function currentRouteNameReturnsNullWhenMatchedRouteHasNoName()
    {
        $request = new ServerRequest([], [], '/test/123', 'GET');
        $router  = new Router(new RouteCollector());

        $route = $router->get('/test/123', function () {
            return 'abc123';
        });

        $response = $router->match($request);

        $this->assertSame(null, $router->currentRouteName());
    }

    /** @test */
    public function canGetListOfRegisteredRoutes()
    {
        $router = new Router(new RouteCollector());
        $route1 = $router->get('/test/123', function () {
        });
        $route2 = $router->get('/test/456', function () {
        });

        $routes = $router->getRoutes();

        $this->assertCount(2, $routes);
        $this->assertContains($route1, $routes);
        $this->assertContains($route2, $routes);
    }

    /**
     * @test
     */
    public function canExtendPostBehaviorWithMacros()
    {
        Router::macro('testFunctionAddedByMacro', function () {
            return 'abc123';
        });

        $queryBuilder = new Router(new RouteCollector());

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMacro());
        $this->assertSame('abc123', Router::testFunctionAddedByMacro());
    }

    /**
     * @test
     */
    public function canExtendPostBehaviorWithMixin()
    {
        Router::mixin(new RouterMixin());

        $queryBuilder = new Router(new RouteCollector());

        $this->assertSame('abc123', $queryBuilder->testFunctionAddedByMixin());
    }
}

class RouterMixin
{
    public function testFunctionAddedByMixin()
    {
        return function () {
            return 'abc123';
        };
    }
}
