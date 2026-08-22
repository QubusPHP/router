<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response\TextResponse;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Http\Request;
use Qubus\Http\Response;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Injector;
use Qubus\Injector\Psr11\Container;
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Router;
use Qubus\Routing\Tests\Fixtures\TestCallableController;
use Qubus\Routing\Tests\Fixtures\TestInvokableController;

final class AdvancedRouterTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = new Container(InjectorFactory::create([
            Injector::STANDARD_ALIASES => [
                RequestInterface::class => Request::class,
                ResponseInterface::class => Response::class,
                ResponseFactoryInterface::class => ResponseFactory::class,
            ],
        ]));
    }

    public function testNonStaticControllerArrayIsSupported(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->get('/array-controller', [Controllers\TestController::class, 'returnHelloWorld']);

        $response = $router->match(new ServerRequest([], [], '/array-controller', 'GET'));

        Assert::assertSame('Hello World!', $response->getBody()->getContents());
    }

    public function testInvokableControllerClassIsSupported(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->get('/invokable', TestInvokableController::class);

        $response = $router->match(new ServerRequest([], [], '/invokable', 'GET'));

        Assert::assertSame('Invoked', $response->getBody()->getContents());
    }

    public function testGroupNamespaceIsAppliedBeforeControllerValidation(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->group(['namespace' => 'Qubus\\Tests\\Routing\\Controllers'], function ($group): void {
            $group->get('/namespaced', 'TestController@returnHelloWorld');
        });

        $response = $router->match(new ServerRequest([], [], '/namespaced', 'GET'));

        Assert::assertSame('Hello World!', $response->getBody()->getContents());
    }

    public function testHttpMethodMatchingRequiresAnExactMethod(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->get('/method', static fn (): string => 'matched');

        $response = $router->match(new ServerRequest([], [], '/method', 'ET'));

        Assert::assertSame(404, $response->getStatusCode());
    }

    public function testMethodOverrideIgnoresNonStringBodyValues(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->post('/method', static fn (): string => 'post');

        $request = new ServerRequest(
            uri: '/method',
            method: 'POST',
            parsedBody: ['_method' => ['DELETE']]
        );
        $response = $router->match($request);

        Assert::assertSame('post', $response->getBody()->getContents());
    }

    public function testValidMethodOverrideIsApplied(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->delete('/method', static fn (): string => 'deleted');

        $request = new ServerRequest(
            uri: '/method',
            method: 'POST',
            parsedBody: ['_method' => 'delete']
        );
        $response = $router->match($request);

        Assert::assertSame('deleted', $response->getBody()->getContents());
    }

    public function testRequestOutsideConfiguredBasePathDoesNotMatch(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->setBasePath('/api');
        $router->get('/users', static fn (): string => 'users');

        $response = $router->match(new ServerRequest([], [], '/other/users', 'GET'));

        Assert::assertSame(404, $response->getStatusCode());
    }

    public function testFailedMatchClearsThePreviouslyMatchedRoute(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->get('/matched', static fn (): string => 'matched')->name('matched');

        $router->match(new ServerRequest([], [], '/matched', 'GET'));
        Assert::assertSame('matched', $router->currentRouteName());

        $router->match(new ServerRequest([], [], '/missing', 'GET'));

        Assert::assertNull($router->currentRoute());
        Assert::assertNull($router->currentRouteName());
    }

    public function testPsrMiddlewareDelegatesWhenNoRouteMatches(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $handler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                return new TextResponse((string) $request->getAttribute(Router::class));
            }
        };

        $response = $router->process(new ServerRequest([], [], '/missing', 'GET'), $handler);

        Assert::assertSame('Not Found', $response->getBody()->getContents());
    }

    public function testRouteDomainIsEnforcedPerRoute(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->get('/tenant', static fn (): string => 'first')->domain('first.example.com');
        $router->get('/tenant', static fn (): string => 'second')->domain('second.example.com');

        $first = $router->match(new ServerRequest([], [], 'https://first.example.com/tenant', 'GET'));
        $second = $router->match(new ServerRequest([], [], 'https://second.example.com/tenant', 'GET'));
        $unknown = $router->match(new ServerRequest([], [], 'https://unknown.example.com/tenant', 'GET'));

        Assert::assertSame('first', $first->getBody()->getContents());
        Assert::assertSame('second', $second->getBody()->getContents());
        Assert::assertSame(404, $unknown->getStatusCode());
    }

    public function testRouteSchemeIsEnforced(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->get('/secure', static fn (): string => 'secure')->setScheme('https');

        $http = $router->match(new ServerRequest([], [], 'http://example.com/secure', 'GET'));
        $https = $router->match(new ServerRequest([], [], 'https://example.com/secure', 'GET'));

        Assert::assertSame(404, $http->getStatusCode());
        Assert::assertSame('secure', $https->getBody()->getContents());
    }

    public function testNamedDomainRouteIncludesItsScheme(): void
    {
        $router = new Router(new RouteCollector(), $this->container);
        $router->get('/secure', static fn (): string => 'secure')
            ->domain('https://secure.example.com')
            ->name('secure');

        Assert::assertSame('https://secure.example.com/secure/', $router->url('secure'));
    }
}
