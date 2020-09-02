<?php

declare(strict_types=1);

namespace Qubus\Router\Route;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Router\ControllerMiddleware;
use Qubus\Router\Exceptions\RouteControllerNotFoundException;
use Qubus\Router\Exceptions\RouteMethodNotFoundException;
use Qubus\Router\Exceptions\RouteParseException;
use Qubus\Router\Interfaces\ControllerMiddlewareInterface;
use Qubus\Router\Invoker;

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
use function stripos;

class RouteAction
{
    protected $callable;
    protected $controller;
    protected $invoker;
    protected $controllerName;
    protected $controllerMethod;
    protected $namespace;

    /**
     * Constructor
     *
     * Actions created with a Controller string (e.g. `MyController@myMethod`) are lazy loaded
     * and the Controller class will only be instantiated when required.
     *
     * @param mixed                $action
     * @param \Qubus\Router\Invoker $invoker
     */
    public function __construct($action, ?string $namespace = null, ?Invoker $invoker = null)
    {
        $this->namespace = $namespace;
        $this->invoker   = $invoker;
        $this->callable  = $this->createCallableFromAction($action);
    }

    /**
     * Invoke the action.
     *
     * @return mixed
     */
    public function invoke(ServerRequestInterface $request, RouteParams $params)
    {
        $callable = $this->callable;
        /**
         * Controller Actions are lazy loaded so we need to call the factory
         * to get the callable.
         */
        if ($this->isControllerAction()) {
            $callable = call_user_func($this->callable);
        }
        /**
         * Call the target action with any provided params.
         */
        if ($this->invoker) {
            return $this->invoker->setRequest($request)->call($callable, $params->toArray());
        } else {
            return call_user_func($callable, $params);
        }
    }

    /**
     * If the action is a Controller string, a factory callable is
     * returned to allow for lazy loading.
     *
     * @param  mixed $action
     */
    private function createCallableFromAction($action): callable
    {
        /**
         * Check if this looks like it could be a class/method string.
         */
        if (! is_callable($action) && is_string($action)) {
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
     * @return string|null Returns null if this is not a Controller based action.
     */
    private function getController()
    {
        if (empty($this->controllerName)) {
            return null;
        }
        if (isset($this->controller)) {
            return $this->controller;
        }
        $this->controller = $this->createControllerFromClassName($this->controllerName);
        return $this->controller;
    }

    /**
     * Instantiate a Controller object from the provided class name.
     *
     * @param  string $className
     * @return mixed
     */
    private function createControllerFromClassName($className)
    {
        /**
         * If we can, use the container to build the Controller so that
         * Constructor params can be injected where possible.
         */
        if ($this->invoker) {
            return $this->invoker->getContainer()->get($className);
        }
        return new $className();
    }

    /**
     * Can this action provide Middleware.
     */
    private function providesMiddleware(): bool
    {
        $controller = $this->getController();
        if ($controller && $controller instanceof ControllerMiddlewareInterface) {
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
            $this->getController()->getControllerMiddleware(),
            function (ControllerMiddleware $middleware) {
                return ! $middleware->excludedForMethod($this->controllerMethod);
            }
        );
        return array_map(
            function ($controllerMiddleware) {
                return $controllerMiddleware->middleware();
            },
            $allControllerMiddleware
        );
    }

    /**
     * Create a factory Closure for the given Controller string.
     *
     * @param  string $string e.g. `MyController@myMethod`
     */
    private function convertClassStringToFactory(string $string): Closure
    {
        $this->controllerName   = null;
        $this->controllerMethod = null;

        $string = $this->resolveController($string);

        @[$className, $method] = explode('@', $string);

        if (! isset($className) || ! isset($method)) {
            throw new RouteParseException('Could not parse route controller from string: `' . $string . '`');
        }

        if (! class_exists($className)) {
            throw new RouteControllerNotFoundException(
                'Could not find route controller class: `' . $className . '`'
            );
        }
        if (! method_exists($className, $method)) {
            throw new RouteMethodNotFoundException(
                'Route controller class: `' . $className . '` does not have a `' . $method . '` method'
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
     * Get the human readable name of this action.
     */
    public function getActionName(): string
    {
        $callableName = null;
        if ($this->isControllerAction()) {
            return $this->controllerName . '@' . $this->controllerMethod;
        }
        if (is_callable($this->callable, false, $callableName)) {
            [$controller, $method] = explode('::', $callableName);

            if ($controller === 'Closure') {
                return $controller;
            }

            return $controller . '@' . $method;
        }
    }

    /**
     * @param callable|object|string|string[] $controller
     * @return callable|object|string|string[]
     */
    private function resolveController($controller)
    {
        if (null !== $this->namespace && (is_string($controller) || ! $controller instanceof Closure)) {
            if (
                is_string($controller) &&
                ! class_exists($controller) &&
                false === stripos($controller, $this->namespace)
            ) {
                $controller = is_callable($controller) ? $controller : $this->namespace . '\\' . $controller;
            }

            if (is_array($controller) && (! is_object($controller[0]) && ! class_exists($controller[0]))) {
                $controller[0] = $this->namespace . '\\' . $controller[0];
            }
        }

        return $controller;
    }
}
