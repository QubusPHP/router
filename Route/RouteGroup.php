<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Routing\Route;

use Qubus\Inheritance\MacroAware;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Interfaces\Mappable;
use Qubus\Routing\Interfaces\Routable;
use Qubus\Routing\Router;
use Qubus\Routing\Traits\RouteMapper;

use function call_user_func;
use function is_array;
use function is_string;
use function ltrim;
use function trim;

class RouteGroup implements Mappable
{
    use MacroAware;
    use RouteMapper;

    protected Router $router;
    protected string $prefix;
    protected string $domain;
    protected string $subDomain;
    protected string $namespace;
    protected array $middlewares = [];

    public function __construct(string|array $params, Router $router)
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

        $this->prefix = trim(string: $prefix, characters: ' /');
        $this->router = $router;
    }

    private function appendPrefixToUri(string $uri): string
    {
        return $this->prefix . '/' . ltrim(string: $uri, characters: '/');
    }

    /**
     * @throws TooLateToAddNewRouteException
     */
    public function map(array $verbs, string $uri, callable|string $callback): Routable
    {
        return $this->router->map(
            verbs: $verbs,
            uri: $this->appendPrefixToUri($uri),
            callback: $callback
        )
        ->namespace($this->namespace)
        ->middleware($this->middlewares)
        ->domain($this->domain)
        ->subDomain($this->subDomain);
    }

    public function group(array|string $params, callable $callback): RouteGroup
    {
        if (is_string(value: $params)) {
            $params = $this->appendPrefixToUri(uri: $params);
        } elseif (is_array(value: $params)) {
            $params['prefix'] = $params['prefix'] ? $this->appendPrefixToUri(uri: $params['prefix']) : '';
        }

        $group = new RouteGroup(params: $params, router: $this->router);

        call_user_func($callback, $group);

        return $this;
    }
}
