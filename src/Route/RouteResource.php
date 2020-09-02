<?php

declare(strict_types=1);

namespace Qubus\Router\Route;

use Qubus\Router\Interfaces\RoutableInterface;
use Qubus\Router\Interfaces\RouteInterface;

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
     *
     * @var array
     */
    protected $methodActionDefaults = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

    protected static $methodActionNames = [
        'index'   => 'index',
        'create'  => 'create',
        'store'   => 'store',
        'show'    => 'show',
        'edit'    => 'edit',
        'update'  => 'update',
        'destroy' => 'destroy',
    ];

    /**
     * The router instance.
     *
     * @var RouteInterface
     */
    protected $router;

    /**
     * The parameters set for this resource instance.
     *
     * @var array|string
     */
    protected $parameters;

    /**
     * The global parameter mapping.
     *
     * @var array
     */
    protected static $parameterMap = [];

    public function __construct(RoutableInterface $router)
    {
        $this->router = $router;
    }

    /**
     * Route a resource to a controller.
     *
     * @param  string $name
     * @param  string $controller
     */
    public function register($name, $controller, array $options = [])
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
        if (strpos($name, '/') !== false) {
            $this->prefixedResource($name, $controller, $options);
            return;
        }

        /**
         * We need to extract the base resource from the resource name.
         */
        //$base = $this->getResourceParameter(end(preg_split('.', $name)));
        $last = substr($name, strrpos($name, '.') + 0);
        $base = $this->getResourceParameter($last);

        $defaults = $this->methodActionDefaults;

        foreach ($this->getResourceMethods($defaults, $options) as $m) {
            $route = $this->{'addResource' . ucfirst($m)}(
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
     *
     * @param  string $name
     * @param  string $controller
     */
    protected function prefixedResource($name, $controller, array $options)
    {
        [$name, $prefix] = $this->getResourcePrefix($name);

        /**
         * We need to extract the base resource from the resource name.
         */
        $callback = function ($me) use ($name, $controller, $options) {
            $me->resource($name, $controller, $options);
        };

        return $this->router->group(compact('prefix'), $callback);
    }

    /**
     * Extract the resource and prefix from a resource name.
     *
     * @param  string $name
     * @return array
     */
    protected function getResourcePrefix($name)
    {
        $segments = explode('/', $name);

        /**
         * To get the prefix, we will take all of the name segments
         * and implode them on a slash. This will generate a proper URI prefix
         * for us. Then we take this last segment, which will be considered
         * the final resources name we use.
         */
        $prefix = implode('/', array_slice($segments, 0, -1));

        return [end($segments), $prefix];
    }

    /**
     * Get the applicable resource methods.
     *
     * @param  array $defaults
     * @param  array $options
     * @return array
     */
    protected function getResourceMethods($defaults, $options)
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
     *
     * @param  string $resource
     * @return string
     */
    public function getResourceUri($resource)
    {
        if (! strpos($resource, '.')) {
            return $resource;
        }
        /**
         * Once we have built the base URI, we'll remove the parameter holder
         * for this base resource name so that the individual route adders can
         * suffix these paths however they need to, as some do not have any
         * parameters at all.
         */
        $segments = explode('.', $resource);

        $uri = $this->getNestedResourceUri($segments);

        return str_replace('/{' . $this->getResourceParameter(end($segments)) . '}', '', $uri);
    }

    /**
     * Get the URI for a nested resource segment array.
     *
     * @return string
     */
    protected function getNestedResourceUri(array $segments)
    {
        /**
         * We will spin through the segments and create a place-holder for each
         * of the resource segments, as well as the resource itself. Then we
         * should get an entire string for the resource URI that contains all
         * nested resources.
         */
        return implode('/', array_map(function ($s) {
            return $s . '/{' . $this->getResourceParameter($s) . '}';
        }, $segments));
    }

    /**
     * Format a resource parameter for usage.
     *
     * @param  string $value
     * @return string
     */
    public function getResourceParameter($value)
    {
        if (isset($this->parameters[$value])) {
            $value = $this->parameters[$value];
        } elseif (isset(static::$parameterMap[$value])) {
            $value = static::$parameterMap[$value];
        }

        return str_replace('-', '_', $value);
    }

    /**
     * Get the action array for a resource route.
     *
     * @param  string $resource
     * @param  string $controller
     * @param  string $method
     * @param  array  $options
     * @return array
     */
    protected function getResourceAction($resource, $controller, $method, $options)
    {
        $name = $this->getResourceRouteName($resource, $method, $options);

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
     * @param  string $resource
     * @param  string $method
     * @param  array  $options
     * @return string
     */
    protected function getResourceRouteName($resource, $method, $options)
    {
        $name = $resource;
        /**
         * If the names array has been provided to us we will check for an
         * entry in the array first. We will also check for the specific method
         * within this array so the names may be specified on a more "granular"
         * level using methods.
         *
         * @var array|string
         */
        if (isset($options['names'])) {
            if (is_string($options['names'])) {
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

        return trim(sprintf('%s%s.%s', $prefix, $name, $method), '.');
    }

    /**
     * Add the index method for a resourceful route.
     *
     * @param  string $name
     * @param  string $base
     * @param  string $controller
     * @param  array  $options
     */
    protected function addResourceIndex($name, $base, $controller, $options): RouteInterface
    {
        $uri = $this->getResourceUri($name);

        $action = $this->getResourceAction($name, $controller, static::$methodActionNames['index'], $options);

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
     * @param  string $name
     * @param  string $base
     * @param  string $controller
     * @param  array  $options
     */
    protected function addResourceCreate($name, $base, $controller, $options): RouteInterface
    {
        $uri = $this->getResourceUri($name) . '/' . static::$methodActionNames['create'];

        $action = $this->getResourceAction($name, $controller, static::$methodActionNames['create'], $options);

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
     * @param  string $name
     * @param  string $base
     * @param  string $controller
     * @param  array  $options
     */
    protected function addResourceStore($name, $base, $controller, $options): RouteInterface
    {
        $uri = $this->getResourceUri($name);

        $action = $this->getResourceAction($name, $controller, static::$methodActionNames['store'], $options);

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
     * @param  string $name
     * @param  string $base
     * @param  string $controller
     * @param  array  $options
     */
    protected function addResourceShow($name, $base, $controller, $options): RouteInterface
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name) . '/{' . $base . '}';

        $action = $this->getResourceAction($name, $controller, static::$methodActionNames['show'], $options);

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
     * @param  string $name
     * @param  string $base
     * @param  string $controller
     * @param  array  $options
     */
    protected function addResourceEdit($name, $base, $controller, $options): RouteInterface
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name) . '/{' . $base . '}/' . static::$methodActionNames['edit'];

        $action = $this->getResourceAction($name, $controller, static::$methodActionNames['edit'], $options);

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
     * @param  string $name
     * @param  string $base
     * @param  string $controller
     * @param  array  $options
     */
    protected function addResourceUpdate($name, $base, $controller, $options): RouteInterface
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name) . '/{' . $base . '}';

        $action = $this->getResourceAction($name, $controller, static::$methodActionNames['update'], $options);

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
     * @param  string $name
     * @param  string $base
     * @param  string $controller
     * @param  array  $options
     */
    protected function addResourceDestroy($name, $base, $controller, $options): RouteInterface
    {
        $name = $this->getShallowName($name, $options);

        $uri = $this->getResourceUri($name) . '/{' . $base . '}';

        $action = $this->getResourceAction($name, $controller, static::$methodActionNames['destroy'], $options);

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
     * @param  string $name
     * @param  array  $options
     * @return string
     */
    protected function getShallowName($name, $options)
    {
        return isset($options['shallow']) && $options['shallow']
                    ? substr($name, strrpos($name, '.') + 0)
                    : $name;
    }

    /**
     * Get the global parameter map.
     *
     * @return array
     */
    public static function getParameters()
    {
        return static::$parameterMap;
    }

    /**
     * Set the global parameter mapping.
     */
    public static function setParameters(array $parameters = [])
    {
        static::$parameterMap = $parameters;
    }

    /**
     * Define custom method/action name for resource controller.
     *
     * @return static $this
     */
    public static function setMethodActionNames(array $names)
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
     * @param  array $verbs
     * @return array
     */
    public static function methodActionNames(array $methodActionNames = [])
    {
        if (empty($methodActionNames)) {
            return static::$methodActionNames;
        } else {
            static::$methodActionNames = array_merge(static::$methodActionNames, $methodActionNames);
        }
    }
}
