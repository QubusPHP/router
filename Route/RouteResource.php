<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/router
 * @copyright  2020
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @author     Joshua Parker <josh@joshuaparker.blog>
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Route;

use Qubus\Routing\Interfaces\Mappable;
use Qubus\Routing\Interfaces\Routable;

use function array_diff;
use function array_intersect;
use function array_map;
use function array_merge;
use function array_slice;
use function compact;
use function end;
use function explode;
use function implode;
use function is_string;
use function sprintf;
use function str_replace;
use function strpos;
use function strrpos;
use function substr;
use function trim;
use function ucfirst;

class RouteResource
{
    /**
     * The default actions/methods for a resourceful controller.
     */
    protected array $methodActionDefaults = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    protected static array $methodActionNames = [
        'index'   => 'index',
        'create'  => 'create',
        'store'   => 'store',
        'show'    => 'show',
        'edit'    => 'edit',
        'update'  => 'update',
        'destroy' => 'destroy',
    ];

    /**
     * The parameters set for this resource instance.
     */
    protected string|array $parameters;

    /**
     * The global parameter mapping.
     */
    protected static array $parameterMap = [];

    public function __construct(public readonly Mappable|Routable $router)
    {
    }

    /**
     * Route a resource to a controller.
     */
    public function register(string $name, string $controller, array $options = []): mixed
    {
        if (isset($options['parameters']) && ! isset($this->parameters)) {
            $this->parameters = $options['parameters'];
        }

        /**
         * If the resource name contains a slash, we will assume the developer
         * wishes to register these resource routes with a prefix so we will
         * set that up out of the box so they don't have to mess with it.
         * Otherwise, we will continue.
         */
        if (strpos(haystack: $name, needle: '/') !== false) {
            return $this->prefixedResource(name: $name, controller: $controller, options: $options);
        }

        /**
         * We need to extract the base resource from the resource name.
         */
        //$base = $this->getResourceParameter(end(preg_split('.', $name)));
        $last = substr(string: $name, offset: strrpos(haystack: $name, needle: '.') + 0);
        $base = $this->getResourceParameter(value: $last);

        $defaults = $this->methodActionDefaults;

        foreach ($this->getResourceMethods(defaults: $defaults, options: $options) as $m) {
            $route = $this->{'addResource' . ucfirst(string: $m)}(
                $name,
                $base,
                $controller,
                $options
            );
        }

        return $route;
    }

    /**
     * Build a set of prefixed resource routes.
     */
    protected function prefixedResource(string $name, string $controller, array $options): mixed
    {
        [$name, $prefix] = $this->getResourcePrefix(name: $name);

        /**
         * We need to extract the base resource from the resource name.
         */
        $callback = function ($me) use ($name, $controller, $options) {
            $me->resource($name, $controller, $options);
        };

        $compact = compact(var_name: 'prefix');

        return $this->router->group(params: $compact, callback: $callback);
    }

    /**
     * Extract the resource and prefix from a resource name.
     *
     * @return array
     */
    protected function getResourcePrefix(string $name): array
    {
        $segments = explode(separator: '/', string: $name);

        /**
         * To get the prefix, we will take all the name segments
         * and implode them on a slash. This will generate a proper URI prefix
         * for us. Then we take this last segment, which will be considered
         * the final resources name we use.
         */
        $prefix = implode(separator: '/', array: array_slice(array: $segments, offset: 0, length: -1));

        return [end($segments), $prefix];
    }

    /**
     * Get the applicable resource methods.
     *
     * @param array $defaults
     * @param array $options
     * @return array
     */
    protected function getResourceMethods(array $defaults, array $options): array
    {
        $methods = $defaults;

        if (isset($options['only'])) {
            $methods = array_intersect($methods, (array) $options['only']);
        }

        if (isset($options['except'])) {
            $methods = array_diff($methods, (array) $options['except']);
        }

        return $methods;
    }

    /**
     * Get the base resource URI for a given resource.
     */
    public function getResourceUri(string $resource): string
    {
        if (! strpos(haystack: $resource, needle: '.')) {
            return $resource;
        }
        /**
         * Once we have built the base URI, we'll remove the parameter holder
         * for this base resource name so that the individual route adders can
         * suffix these paths however they need to, as some do not have any
         * parameters at all.
         */
        $segments = explode(separator: '.', string: $resource);

        $uri = $this->getNestedResourceUri(segments: $segments);

        return str_replace(
            search: '/{' . $this->getResourceParameter(value: end($segments)) . '}',
            replace: '',
            subject: $uri
        );
    }

    /**
     * Get the URI for a nested resource segment array.
     */
    protected function getNestedResourceUri(array $segments): string
    {
        /**
         * We will spin through the segments and create a place-holder for each
         * of the resource segments, as well as the resource itself. Then we
         * should get an entire string for the resource URI that contains all
         * nested resources.
         */
        return implode(separator: '/', array: array_map(function ($s) {
            return $s . '/{' . $this->getResourceParameter(value: $s) . '}';
        }, $segments));
    }

    /**
     * Format a resource parameter for usage.
     */
    public function getResourceParameter(string $value): string
    {
        if (isset($this->parameters[$value])) {
            $value = $this->parameters[$value];
        } elseif (isset(static::$parameterMap[$value])) {
            $value = static::$parameterMap[$value];
        }

        return str_replace(search: '-', replace: '_', subject: $value);
    }

    /**
     * Get the action array for a resource route.
     *
     * @param array $options
     * @return array
     */
    protected function getResourceAction(string $resource, string $controller, string $method, array $options): array
    {
        $name = $this->getResourceRouteName(resource: $resource, method: $method, options: $options);

        $action = ['alias' => $name, 'callable' => $controller . '@' . $method];

        if (isset($options['namespace'])) {
            $action['namespace'] = $options['namespace'];
        } else {
            $action['namespace'] = '';
        }

        if (isset($options['middlewares'])) {
            $action['middlewares'] = $options['middlewares'];
        } else {
            $action['middlewares'] = [];
        }

        if (isset($options['domain'])) {
            $action['domain'] = $options['domain'];
        } else {
            $action['domain'] = '';
        }

        if (isset($options['subdomain'])) {
            $action['subdomain'] = $options['subdomain'];
        } else {
            $action['subdomain'] = '';
        }

        return $action;
    }

    /**
     * Get the name for a given resource.
     *
     * @param array $options
     */
    protected function getResourceRouteName(string $resource, string $method, array $options): string
    {
        $name = $resource;
        /**
         * If the names array has been provided to us we will check for an
         * entry in the array first. We will also check for the specific method
         * within this array so the names may be specified on a more "granular"
         * level using methods.
         *
         * @var array|string $options
         */
        if (isset($options['names'])) {
            if (is_string(value: $options['names'])) {
                $name = $options['names'];
            } elseif (isset($options['names'][$method])) {
                return $options['names'][$method];
            }
        }
        /**
         * If a global prefix has been assigned to all names for this resource,
         * we will grab that so we can prepend it onto the name when we create
         * this name for the resource action. Otherwise we'll just use an empty
         * string for here.
         *
         * @var string
         */
        $prefix = isset($options['alias']) ? $options['alias'] . '.' : '';

        return trim(string: sprintf('%s%s.%s', $prefix, $name, $method), characters: '.');
    }

    /**
     * Add the index method for a resourceful route.
     *
     * @param array $options
     */
    protected function addResourceIndex(string $name, string $base, string $controller, array $options): mixed
    {
        $uri = $this->getResourceUri(resource: $name);

        $action = $this->getResourceAction(
            resource: $name,
            controller: $controller,
            method: static::$methodActionNames['index'],
            options: $options
        );

        return $this->router->get($uri, $action['callable'])
                    ->name($action['alias'])
                    ->namespace($action['namespace'])
                    ->middleware($action['middlewares'])
                    ->domain($action['domain'])
                    ->subDomain($action['subdomain']);
    }

    /**
     * Add the create method for a resourceful route.
     *
     * @param array $options
     */
    protected function addResourceCreate(string $name, string $base, string $controller, array $options): mixed
    {
        $uri = $this->getResourceUri(resource: $name) . '/' . static::$methodActionNames['create'];

        $action = $this->getResourceAction(
            resource: $name,
            controller: $controller,
            method: static::$methodActionNames['create'],
            options: $options
        );

        return $this->router->get($uri, $action['callable'])
                    ->name($action['alias'])
                    ->namespace($action['namespace'])
                    ->middleware($action['middlewares'])
                    ->domain($action['domain'])
                    ->subDomain($action['subdomain']);
    }

    /**
     * Add the store method for a resourceful route.
     *
     * @param array $options
     */
    protected function addResourceStore(string $name, string $base, string $controller, array $options): mixed
    {
        $uri = $this->getResourceUri(resource: $name);

        $action = $this->getResourceAction(
            resource: $name,
            controller: $controller,
            method: static::$methodActionNames['store'],
            options: $options
        );

        return $this->router->post($uri, $action['callable'])
                    ->name($action['alias'])
                    ->namespace($action['namespace'])
                    ->middleware($action['middlewares'])
                    ->domain($action['domain'])
                    ->subDomain($action['subdomain']);
    }

    /**
     * Add the show method for a resourceful route.
     *
     * @param array $options
     */
    protected function addResourceShow(string $name, string $base, string $controller, array $options): mixed
    {
        $name = $this->getShallowName(name: $name, options: $options);

        $uri = $this->getResourceUri(resource: $name) . '/{' . $base . '}';

        $action = $this->getResourceAction(
            resource: $name,
            controller: $controller,
            method: static::$methodActionNames['show'],
            options: $options
        );

        return $this->router->get($uri, $action['callable'])
                    ->name($action['alias'])
                    ->namespace($action['namespace'])
                    ->middleware($action['middlewares'])
                    ->domain($action['domain'])
                    ->subDomain($action['subdomain']);
    }

    /**
     * Add the edit method for a resourceful route.
     *
     * @param array $options
     */
    protected function addResourceEdit(string $name, string $base, string $controller, array $options): mixed
    {
        $name = $this->getShallowName(name: $name, options: $options);

        $uri = $this->getResourceUri(resource: $name) . '/{' . $base . '}/' . static::$methodActionNames['edit'];

        $action = $this->getResourceAction(
            resource: $name,
            controller: $controller,
            method: static::$methodActionNames['edit'],
            options: $options
        );

        return $this->router->get($uri, $action['callable'])
                    ->name($action['alias'])
                    ->namespace($action['namespace'])
                    ->middleware($action['middlewares'])
                    ->domain($action['domain'])
                    ->subDomain($action['subdomain']);
    }

    /**
     * Add the update method for a resourceful route.
     *
     * @param array $options
     */
    protected function addResourceUpdate(string $name, string $base, string $controller, array $options): mixed
    {
        $name = $this->getShallowName(name: $name, options: $options);

        $uri = $this->getResourceUri(resource: $name) . '/{' . $base . '}';

        $action = $this->getResourceAction(
            resource: $name,
            controller: $controller,
            method: static::$methodActionNames['update'],
            options: $options
        );

        return $this->router->map(['PUT', 'PATCH'], $uri, $action['callable'])
                    ->name($action['alias'])
                    ->namespace($action['namespace'])
                    ->middleware($action['middlewares'])
                    ->domain($action['domain'])
                    ->subDomain($action['subdomain']);
    }

    /**
     * Add the destroy method for a resourceful route.
     *
     * @param array $options
     */
    protected function addResourceDestroy(string $name, string $base, string $controller, array $options): mixed
    {
        $name = $this->getShallowName(name: $name, options: $options);

        $uri = $this->getResourceUri(resource: $name) . '/{' . $base . '}';

        $action = $this->getResourceAction(
            resource: $name,
            controller: $controller,
            method: static::$methodActionNames['destroy'],
            options: $options
        );

        return $this->router->delete($uri, $action['callable'])
                    ->name($action['alias'])
                    ->namespace($action['namespace'])
                    ->middleware($action['middlewares'])
                    ->domain($action['domain'])
                    ->subDomain($action['subdomain']);
    }

    /**
     * Get the name for a given resource with shallowness applied when applicable.
     *
     * @param array $options
     */
    protected function getShallowName(string $name, array $options): string
    {
        return isset($options['shallow']) && $options['shallow']
        ? substr(string: $name, offset: strrpos(haystack: $name, needle: '.') + 0)
        : $name;
    }

    /**
     * Get the global parameter map.
     *
     * @return array
     */
    public static function getParameters(): array
    {
        return static::$parameterMap;
    }

    /**
     * Set the global parameter mapping.
     */
    public static function setParameters(array $parameters = []): void
    {
        static::$parameterMap = $parameters;
    }

    /**
     * Define custom method/action name for resource controller.
     */
    public static function setMethodActionNames(array $names): void
    {
        static::$methodActionNames = $names;
    }

    /**
     * Get method/action names.
     */
    public function getMethodActionNames(): array
    {
        return static::$methodActionNames;
    }

    /**
     * Get or set the action verbs used in the resource URIs.
     *
     * @param array $methodActionNames
     */
    public static function methodActionNames(array $methodActionNames = []): mixed
    {
        if (empty($methodActionNames)) {
            return static::$methodActionNames;
        } else {
            static::$methodActionNames = array_merge(static::$methodActionNames, $methodActionNames);
        }
    }
}
