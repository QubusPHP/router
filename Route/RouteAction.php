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

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Routing\Controller\ControllerMiddlewareDelegate;
use Qubus\Routing\Controller\ControllerMiddlewarePipe;
use Qubus\Routing\Exceptions\RouteControllerNotFoundException;
use Qubus\Routing\Exceptions\RouteMethodNotFoundException;
use Qubus\Routing\Exceptions\RouteParseException;
use Qubus\Routing\Invoker;

use function array_filter;
use function array_map;
use function call_user_func;
use function class_exists;
use function explode;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function method_exists;
use function sprintf;
use function stripos;

class RouteAction
{
    protected mixed $callable;
    protected mixed $controller;
    protected ?Invoker $invoker = null;
    protected ?string $controllerName = null;
    protected ?string $controllerMethod = null;
    protected ?string $namespace = null;

    /**
     * Constructor
     *
     * Actions created with a Controller string (e.g. `MyController@myMethod`) are lazy loaded
     * and the Controller class will only be instantiated when required.
     */
    public function __construct(mixed $action, ?string $namespace = null, ?Invoker $invoker = null)
    {
        $this->namespace = $namespace;
        $this->invoker   = $invoker;
        $this->callable  = $this->createCallableFromAction($action);
    }

    /**
     * Invoke the action.
     */
    public function invoke(ServerRequestInterface $request, RouteParams $params): mixed
    {
        $callable = $this->callable;
        /**
         * Controller Actions are lazy loaded so we need to call the factory
         * to get the callable.
         */
        if ($this->isControllerAction()) {
            $callable = call_user_func(callback: $this->callable);
        }
        /**
         * Call the target action with any provided params.
         */
        if ($this->invoker) {
            return $this->invoker->setRequest(
                request: $request
            )->call(callable: $callable, parameters: $params->toArray());
        } else {
            return call_user_func($callable, $params);
        }
    }

    /**
     * If the action is a Controller string, a factory callable is
     * returned to allow for lazy loading.
     */
    private function createCallableFromAction(mixed $action): callable
    {
        /**
         * Check if this looks like it could be a class/method string.
         */
        if (! is_callable(value: $action) && is_string(value: $action)) {
            return $this->convertClassStringToFactory($action);
        }
        return $action;
    }

    /**
     * Is this a known Controller based action?
     */
    private function isControllerAction(): bool
    {
        return ! empty($this->controllerName) && ! empty($this->controllerMethod);
    }

    /**
     * Get the Controller for this action. The Controller will only be created once.
     *
     * @return string|object|null Returns null if this is not a Controller based action.
     */
    private function getController(): string|null|object
    {
        if (empty($this->controllerName)) {
            return null;
        }
        if (isset($this->controller)) {
            return $this->controller;
        }
        $this->controller = $this->createControllerFromClassName(className: $this->controllerName);
        return $this->controller;
    }

    /**
     * Instantiate a Controller object from the provided class name.
     */
    private function createControllerFromClassName(string $className): mixed
    {
        /**
         * If we can, use the container to build the Controller so that
         * Constructor params can be injected where possible.
         */
        if ($this->invoker) {
            return $this->invoker->getContainer()->get(id: $className);
        }
        return new $className();
    }

    /**
     * Can this action provide Middleware.
     */
    private function providesMiddleware(): bool
    {
        $controller = $this->getController();
        if ($controller && $controller instanceof ControllerMiddlewareDelegate) {
            return true;
        }
        return false;
    }

    /**
     * Get an array of Middleware.
     */
    public function getMiddlewares(): array
    {
        if (! $this->providesMiddleware()) {
            return [];
        }
        $allControllerMiddleware = array_filter(
            array: $this->getController()->getControllerMiddleware(),
            callback: function (ControllerMiddlewarePipe $middleware) {
                return ! $middleware->excludedForMethod(method: $this->controllerMethod);
            }
        );
        return array_map(
            callback: function ($controllerMiddleware) {
                return $controllerMiddleware->middleware();
            },
            array: $allControllerMiddleware
        );
    }

    /**
     * Create a factory Closure for the given Controller string.
     *
     * @param string $string e.g. `MyController@myMethod`
     * @throws RouteParseException
     * @throws RouteControllerNotFoundException
     * @throws RouteMethodNotFoundException
     */
    private function convertClassStringToFactory(string $string): Closure
    {
        $this->controllerName   = null;
        $this->controllerMethod = null;

        $string = $this->resolveController(controller: $string);

        @[$className, $method] = explode(separator: '@', string: $string);

        if (! isset($className) || ! isset($method)) {
            throw new RouteParseException(
                message: sprintf(
                    'Could not parse route controller from string: `%s`',
                    $string
                )
            );
        }

        if (! class_exists($className)) {
            throw new RouteControllerNotFoundException(
                message: sprintf('Could not find route controller class: `%s`', $className)
            );
        }
        if (! method_exists($className, $method)) {
            throw new RouteMethodNotFoundException(
                message: sprintf('Route controller class: `%s` does not have a `%s` method', $className, $method)
            );
        }

        $this->controllerName   = $className;
        $this->controllerMethod = $method;

        return function () {
            $controller = $this->getController();
            $method     = $this->controllerMethod;

            if ($this->invoker) {
                return [$controller, $method];
            }

            return function ($params = null) use ($controller, $method) {
                return $controller->$method($params);
            };
        };
    }

    /**
     * Get the human-readable name of this action.
     */
    public function getActionName(): string
    {
        $callableName = null;
        if ($this->isControllerAction()) {
            return $this->controllerName . '@' . $this->controllerMethod;
        }
        if (is_callable($this->callable, false, $callableName)) {
            [$controller, $method] = explode(separator: '::', string: (string) $callableName);

            if ($controller === 'Closure') {
                return $controller;
            }

            return $controller . '@' . $method;
        }

        return '';
    }

    /**
     * @param callable|object|string|string[] $controller
     * @return callable|object|string|string[]
     */
    private function resolveController(array|callable|object|string $controller): array|callable|object|string
    {
        if (null !== $this->namespace && (! $controller instanceof Closure)) {
            if (
                is_string(value: $controller) &&
                ! class_exists(class: $controller) &&
                false === stripos(haystack: $controller, needle: $this->namespace)
            ) {
                $controller = is_callable(value: $controller) ? $controller : $this->namespace . '\\' . $controller;
            }

            if (
                is_array(value: $controller)
                && (
                    ! is_object(value: $controller[0])
                && ! class_exists(class: $controller[0])
                )
            ) {
                $controller[0] = $this->namespace . '\\' . $controller[0];
            }
        }

        return $controller;
    }
}
