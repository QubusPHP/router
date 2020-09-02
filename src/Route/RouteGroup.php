<?php

declare(strict_types=1);

namespace Qubus\Router\Route;

use Qubus\Router\Interfaces\RoutableInterface;
use Qubus\Router\Interfaces\RouteInterface;
use Qubus\Router\Router;
use Qubus\Router\Traits\RouteCollectionTrait;
use Spatie\Macroable\Macroable;

use function call_user_func;
use function is_array;
use function is_string;
use function ltrim;
use function trim;

class RouteGroup implements RoutableInterface
{
    use Macroable;
    use RouteCollectionTrait;

    protected $router;
    protected $prefix;
    protected $domain;
    protected $subDomain;
    protected $namespace;
    protected $middlewares = [];

    public function __construct($params, $router)
    {
        $prefix     = '';
        $domain     = '';
        $subdomain  = '';
        $namespace  = '';
        $middleware = [];

        if (is_string($params)) {
            $prefix = $params;
        }

        if (is_array($params)) {
            $prefix     = $params['prefix'] ?? '';
            $domain     = $params['domain'] ?? '';
            $subdomain  = $params['subdomain'] ?? '';
            $namespace  = $params['namespace'] ?? '';
            $middleware = $params['middleware'] ?? [];

            if (! is_array($middleware)) {
                $middleware = [$middleware];
            }

            $this->domain       = $domain;
            $this->subDomain    = $subdomain;
            $this->namespace    = $namespace;
            $this->middlewares += $middleware;
        }

        $this->prefix = trim($prefix, ' /');
        $this->router = $router;
    }

    private function appendPrefixToUri(string $uri): string
    {
        return $this->prefix . '/' . ltrim($uri, '/');
    }

    public function map(array $verbs, string $uri, $callback): RouteInterface
    {
        return $this->router->map(
            $verbs,
            $this->appendPrefixToUri($uri),
            $callback
        )
        ->namespace($this->namespace)
        ->middleware($this->middlewares)
        ->domain($this->domain)
        ->subDomain($this->subDomain);
    }

    public function group($params, callable $callback): Router
    {
        if (is_string($params)) {
            $params = $this->appendPrefixToUri($params);
        } elseif (is_array($params)) {
            $params['prefix'] = $params['prefix'] ? $this->appendPrefixToUri($params['prefix']) : '';
        }

        $group = new RouteGroup($params, $this->router);

        call_user_func($callback, $group);

        return $this;
    }
}
