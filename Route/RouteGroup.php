<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Route;

use Qubus\Routing\Interfaces\Mappable;
use Qubus\Routing\Interfaces\Routable;
use Qubus\Routing\Router;
use Qubus\Routing\Traits\RouteMapper;
use Spatie\Macroable\Macroable;

use function call_user_func;
use function is_array;
use function is_string;
use function ltrim;
use function trim;

class RouteGroup implements Mappable
{
    use Macroable;
    use RouteMapper;

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

    public function map(array $verbs, string $uri, $callback): Routable
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
