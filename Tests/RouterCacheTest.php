<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\After;
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
use Qubus\Routing\Route\RouteFileCache;
use Qubus\Routing\Tests\Fixtures\NativeObjectContainer;
use Qubus\Routing\Tests\Fixtures\TestCallableController;
use Qubus\Routing\Router;

use function file_get_contents;
use function is_file;
use function random_bytes;
use function sys_get_temp_dir;
use function unlink;

final class RouterCacheTest extends TestCase
{
    private ?string $cachePath = null;

    #[After]
    public function removeCacheFile(): void
    {
        if ($this->cachePath !== null && is_file($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    public function testCacheStoresDefinitionsWithoutSerializingTheContainer(): void
    {
        $router = new Router(new RouteCollector(), new NativeObjectContainer());
        $router->enableRouteCache($this->newCachePath());
        $router->get('/controller', TestCallableController::class . '@testStatic');

        $router->match(new ServerRequest([], [], '/controller', 'GET'));

        $cache = file_get_contents($this->cachePath);

        Assert::assertNotFalse($cache);
        Assert::assertStringNotContainsString('ReflectionClass', $cache);
        Assert::assertStringNotContainsString('NativeObjectContainer', $cache);
        Assert::assertLessThan(10_000, strlen($cache));
        Assert::assertTrue($router->routeIsCached());
        Assert::assertSame($this->cachePath, $router->getRouteCachePath());

        $fromCache = new Router(new RouteCollector(), new NativeObjectContainer());
        $fromCache->enableRouteCache($this->cachePath);
        $fromCache->get('/replacement', static fn (): string => 'replacement');

        $response = $fromCache->match(new ServerRequest([], [], '/controller', 'GET'));

        Assert::assertSame(204, $response->getStatusCode());
        Assert::assertCount(1, $fromCache->routes);
    }

    public function testCachedClosureAndRouteMetadataAreRestored(): void
    {
        $path = $this->newCachePath();
        $router = new Router(new RouteCollector(), $this->container());
        $router->enableRouteCache($path);
        $router->get('/cached/{id}', static function (string $id): string {
            return 'cached-' . $id;
        })->name('cached.show')->where('id', '[0-9]+');

        $first = $router->match(new ServerRequest([], [], '/cached/12', 'GET'));

        Assert::assertSame('cached-12', $first->getBody()->getContents());
        Assert::assertCount(1, $router->routes);

        $fromCache = new Router(new RouteCollector(), $this->container());
        $fromCache->enableRouteCache($path);
        $fromCache->get('/replacement', static fn (): string => 'replacement');

        $second = $fromCache->match(new ServerRequest([], [], '/cached/34', 'GET'));

        Assert::assertSame('cached-34', $second->getBody()->getContents());
        Assert::assertSame('/cached/56/', $fromCache->url('cached.show', ['id' => 56]));
        Assert::assertCount(1, $fromCache->routes);
    }

    public function testLegacyCacheIsRebuiltUsingTheCurrentFormat(): void
    {
        $path = $this->newCachePath();
        new RouteFileCache($path)->put([['legacy']]);

        $router = new Router(new RouteCollector(), $this->container());
        $router->enableRouteCache($path);
        $router->get('/fresh', static fn (): string => 'fresh');

        $response = $router->match(new ServerRequest([], [], '/fresh', 'GET'));
        $cache = new RouteFileCache($path)->read();

        Assert::assertSame('fresh', $response->getBody()->getContents());
        Assert::assertSame(2, $cache['version']);
        Assert::assertCount(1, $cache['routes']);

        $router->clearRouteCache();
        Assert::assertFalse($router->routeIsCached());
    }

    private function newCachePath(): string
    {
        $this->cachePath = sys_get_temp_dir() . '/qubus-router-' . bin2hex(random_bytes(8)) . '.php';

        return $this->cachePath;
    }

    private function container(): ContainerInterface
    {
        return new Container(InjectorFactory::create([
            Injector::STANDARD_ALIASES => [
                RequestInterface::class => Request::class,
                ResponseInterface::class => Response::class,
                ResponseFactoryInterface::class => ResponseFactory::class,
            ],
        ]));
    }
}
