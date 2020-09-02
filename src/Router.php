<?php

declare(strict_types=1);

namespace Qubus\Router;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Exception;
use Qubus\Router\Exceptions\NamedRouteNotFoundException;
use Qubus\Router\Exceptions\RouteParamFailedConstraintException;
use Qubus\Router\Exceptions\TooLateToAddNewRouteException;
use Qubus\Router\Handlers\EventHandler;
use Qubus\Router\Http\Request as SystemRequest;
use Qubus\Router\Interfaces\EventHandlerInterface;
use Qubus\Router\Interfaces\MiddlewareResolverInterface;
use Qubus\Router\Interfaces\RoutableInterface;
use Qubus\Router\Interfaces\RouteCollectorInterface;
use Qubus\Router\Interfaces\RouteInterface;
use Qubus\Router\Interfaces\RouterBootManagerInterface;
use Qubus\Router\Route\Route;
use Qubus\Router\Route\RouteCollector;
use Qubus\Router\Route\RouteGroup;
use Qubus\Router\Route\RouteParams;
use Qubus\Router\Route\RouteResource;
use Qubus\Router\Traits\RouteCollectionTrait;
use Relay\Relay;
use Spatie\Macroable\Macroable;

use function array_diff;
use function array_filter;
use function array_map;
use function array_merge;
use function call_user_func;
use function count;
use function file_get_contents;
use function implode;
use function json_decode;
use function ltrim;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function substr;
use function trim;

use const JSON_PRETTY_PRINT;

final class Router implements RoutableInterface
{
    use Macroable;
    use RouteCollectionTrait;

    /**
     * Current request
     *
     * @var Request
     */
    protected $qr;
    public $version   = '1.0.0';
    protected $routes = [];
    protected $routeCollector;
    protected $routesCreated             = false;
    protected $routeCollectorMatchTypeId = 1;
    protected $basePath;
    protected $currentRoute;
    protected $container;
    protected $responseFactory;
    protected $middlewareResolver;
    protected $invoker;
    protected $baseMiddleware = [];
    protected $defaultNamespace;
    protected $namespace;

    /**
     * List of added bootmanagers
     *
     * @var array
     */
    protected $bootManagers = [];

    /**
     * Contains any registered event-handler.
     *
     * @var array
     */
    protected $eventHandlers = [];

    public function __construct(
        RouteCollectorInterface $routeCollector,
        ?ContainerInterface $container = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?MiddlewareResolverInterface $resolver = null
    ) {
        if (isset($container)) {
            $this->setContainer($container);
        }
        if (isset($responseFactory)) {
            $this->responseFactory = $responseFactory;
        }
        if (isset($resolver)) {
            $this->middlewareResolver = $resolver;
        }

        $this->qr = new SystemRequest();
        /**
         * Set route collector instance.
         */
        $this->routeCollector = $routeCollector;
        $this->setBasePath('/');
    }

    public function prependUrl(string $url): string
    {
        return $this->routeCollector->setBasePath($url);
    }

    /**
     * Set a container.
     */
    protected function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
        /**
         * Create an invoker for this container. This allows us to use the
         * call()` method even if the container doesn't support it natively.
         */
        $this->invoker = new Invoker($this->container);
    }

    /**
     * Set the basepath.
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = Formatting::addLeadingSlash(Formatting::addTrailingSlash($basePath));
        /**
         * Force the router to rebuild next time we need it.
         *
         * @var boolean
         */
        $this->routesCreated = false;
    }

    /**
     * Set the default namespace for controllers.
     */
    public function setDefaultNamespace(string $namespace): void
    {
        $this->defaultNamespace = $namespace;
    }

    /**
     * Add route.
     *
     * @param Route The route.
     * @return array Add route to routes array.
     * @throws TooLateToAddNewRouteException
     */
    protected function addRoute(RouteInterface $route)
    {
        $this->fireEvents(EventHandler::EVENT_ADD_ROUTE, [
            'route' => $route,
        ]);

        if ($this->routesCreated) {
            throw new TooLateToAddNewRouteException();
        }
        $this->routes[] = $route;
    }

    protected function convertRouteToRouteCollectorRouterUri(
        RouteInterface $route,
        RouteCollector $routeCollector
    ): string {
        $output = $route->getUri();

        preg_match_all('/{\s*([a-zA-Z0-9]+\??)\s*}/s', $route->getUri(), $matches);

        $paramConstraints = $route->getParamConstraints();

        for ($i = 0; $i < count($matches[0]); $i++) {
            $match    = $matches[0][$i];
            $paramKey = $matches[1][$i];

            $optional = substr($paramKey, -1) === '?';
            $paramKey = trim($paramKey, '?');

            $regex       = $paramConstraints[$paramKey] ?? null;
            $matchTypeId = '';

            if (! empty($regex)) {
                $matchTypeId = 'rare' . $this->routeCollectorMatchTypeId++;
                $routeCollector->addMatchTypes([
                    $matchTypeId => $regex,
                ]);
            }

            $replacement = '[' . $matchTypeId . ':' . $paramKey . ']';

            if ($optional) {
                $replacement .= '?';
            }

            $output = str_replace($match, $replacement, $output);
        }

        return ltrim($output, ' /');
    }

    /**
     * @param  array    $verbs    HTTP methods.
     * @param  string   $uri      Route path.
     * @param  callable $callback
     * @return Route
     */
    public function map(array $verbs, string $uri, $callback): RouteInterface
    {
        /**
         * Force all verbs to be uppercase.
         */
        $verbs = array_map('strtoupper', $verbs);

        $route = new Route(
            $verbs,
            $uri,
            $callback,
            $this->defaultNamespace,
            $this->invoker,
            $this->middlewareResolver
        );
        $this->addRoute($route);
        return $route;
    }

    /**
     * Register an array of resource controllers.
     */
    public function resources(array $resources, array $options = []): void
    {
        foreach ($resources as $name => $controller) {
            $this->resource($name, $controller, $options);
        }
    }

    /**
     * Route a resource to a controller.
     *
     * @param  string $name
     * @param  string $controller
     */
    public function resource($name, $controller, array $options = []): RouteInterface
    {
        $resource = new RouteResource($this);
        return $resource->register($name, $controller, $options);
    }

    /**
     * Register an array of API resource controllers.
     */
    public function apiResources(array $resources, array $options = []): void
    {
        foreach ($resources as $name => $controller) {
            $this->apiResource($name, $controller, $options);
        }
    }

    /**
     * Route an API resource to a controller.
     *
     * @param  string $name
     * @param  string $controller
     */
    public function apiResource($name, $controller, array $options = []): RouteInterface
    {
        $only = ['index', 'show', 'store', 'update', 'destroy'];

        if (isset($options['except'])) {
            $only = array_diff($only, (array) $options['except']);
        }

        return $this->resource($name, $controller, array_merge([
            'only' => $only,
        ], $options));
    }

    /**
     * Load routes from a JSON file.
     *
     * @param string $path Path to the JSON routes file.
     * @return mixed Routes
     */
    public function loadRoutesFromJson(string $path): void
    {
        $content = file_get_contents($path);
        $json    = json_decode($content, true);

        foreach ($json['routes'] as $route) {
            if (! empty($route['group'])) {
                $this->handleGroupJsonRoutes($route);
                continue;
            }
            $this->handleSimpleJsonRoutes($route);
        }
    }

    /**
     * Converts JSON routes to a route object.
     *
     * @param  array $routes Array from JSON file.
     * @return mixed Routes.
     */
    public function handleSimpleJsonRoutes($route): void
    {
        $map = $this->map($route['method'], $route['path'], $route['callback']);

        $this->setExtrasOfSimpleJsonRoute($route, $map);
    }

    /**
     * Converts JSON group routes to a route object.
     *
     * @param  array $route Array form JSON file
     * @return mixed Routes.
     */
    public function handleGroupJsonRoutes($route): void
    {
        foreach ($route['group']['routes'] as $routeGroup) {
            if (isset($routeGroup['group'])) {
                $this->handleGroupJsonRoutes($routeGroup);
                continue;
            }
            $this->handleSimpleJsonRoutes($routeGroup);
        }
    }

    protected function createRoutes(): void
    {
        if ($this->routesCreated) {
            return;
        }

        $this->routeCollector->setBasePath($this->basePath);

        $this->fireEvents(EventHandler::EVENT_BOOT, [
            'bootmanagers' => $this->bootManagers,
        ]);

        /* Initialize boot-managers */
        foreach ($this->bootManagers as $manager) {
            $this->fireEvents(EventHandler::EVENT_RENDER_BOOTMANAGER, [
                'bootmanagers' => $this->bootManagers,
                'bootmanager'  => $manager,
            ]);

            /* Render bootmanager */
            $manager->boot($this, $this->qr);
        }

        $this->routesCreated = true;

        $this->fireEvents(EventHandler::EVENT_LOAD_ROUTES, [
            'routes' => $this->routes,
        ]);

        foreach ($this->routes as $route) {
            $uri = $this->convertRouteToRouteCollectorRouterUri($route, $this->routeCollector);
            $this->routeCollector->setDomain($route->getDomain());
            /**
             * Canonical URI with trailing slash - becomes named route
             * if name is provided
             */
            $this->routeCollector->map(
                implode('|', $route->getMethods()),
                $route->getSubDomain() ?? null,
                Formatting::addTrailingSlash($uri),
                $route,
                $route->getName() ?? null
            );
            /**
             * Also register URI without trailing slash
             */
            $this->routeCollector->map(
                implode('|', $route->getMethods()),
                $route->getSubDomain() ?? null,
                Formatting::removeTrailingSlash($uri),
                $route
            );
        }

        $this->fireEvents(EventHandler::EVENT_LOAD, [
            'loadedRoutes' => $this->getRoutes(),
        ]);
    }

    /**
     * @return mixed
     */
    public function match(ServerRequestInterface $request): ResponseInterface
    {
        $this->fireEvents(EventHandler::EVENT_INIT);

        $this->createRoutes();

        $uri = $this->qr->getRewriteUrl() ?? $request->getUri()->getPath();

        $collectorRoute = $this->routeCollector->match(
            $request->getUri()->getHost(),
            $uri,
            $request->getMethod()
        );

        $route  = $collectorRoute['target'] ?? null;
        $params = new RouteParams($collectorRoute['params'] ?? []);

        if (! $route) {
            return new JsonResponse(
                'Resource not found.',
                404,
                ['Content-Type' => ['application/hal+json']],
                JSON_PRETTY_PRINT
            );
        }

        $this->fireEvents(EventHandler::EVENT_MATCH_ROUTE, [
            'route' => $route,
        ]);

        $this->currentRoute = $route;
        return $this->handle($route, $request, $params);
    }

    /**
     * @param  object $route
     * @param  array  $params
     */
    protected function handle($route, ServerRequestInterface $request, $params): ResponseInterface
    {
        if (count($this->baseMiddleware) === 0) {
            $this->fireEvents(EventHandler::EVENT_RENDER_MIDDLEWARES, [
                'route'       => $route,
                'middlewares' => $route->gatherMiddlewares(),
            ]);
            return $route->handle($request, $params);
        }

        $this->fireEvents(EventHandler::EVENT_RENDER_MIDDLEWARES, [
            'route'       => $route,
            'middlewares' => $route->gatherMiddlewares(),
        ]);
        /**
         * Apply all the base middleware and trigger the route handler as the
         * last in the chain
         */
        $middlewares = array_merge($this->baseMiddleware, [
            function ($request) use ($route, $params) {
                return $route->handle($request, $params);
            },
        ]);
        /**
         * Create and process the dispatcher.
         *
         * @var Relay $dispatcher
         */
        $dispatcher = new Relay($middlewares, function ($name) {
            if (! isset($this->middlewareResolver)) {
                return $name;
            }
            return $this->middlewareResolver->resolve($name);
        });
        return $dispatcher->handle($request);
    }

    /**
     * Add BootManager
     *
     * @return static
     */
    public function addBootManager(RouterBootManagerInterface $bootManager): self
    {
        $this->bootManagers[] = $bootManager;
        return $this;
    }

    /**
     * Check if a route exists based on it's name.
     *
     * @param  string $name The name of the route.
     * @return bool True if the named routed exists, false otherwise.
     */
    public function has(string $name): bool
    {
        $this->fireEvents(EventHandler::EVENT_FIND_ROUTE, [
            'name' => $name,
        ]);

        $routes = array_filter($this->routes, function ($route) use ($name) {
            return $route->getName() === $name;
        });
        return count($routes) > 0;
    }

    /**
     * Generate url's from named routes.
     *
     * @param  string $name   Name of the route.
     * @param  array  $params Data parameters.
     * @return string The url.
     * @throws RouteParamFailedConstraintException
     * @throws NamedRouteNotFoundException
     */
    public function url(string $name, array $params = [])
    {
        $this->createRoutes();
        /**
         * Find the correct route by name so that we can check if the passed in
         * parameters match any constraints that might have been applied.
         */
        $matchedRoute = null;
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                $matchedRoute = $route;
            }
        }
        if ($matchedRoute) {
            $paramConstraints = $matchedRoute->getParamConstraints();

            foreach ($params as $key => $value) {
                $regex = $paramConstraints[$key] ?? false;

                if ($regex) {
                    if (! preg_match('/' . $regex . '/', (string) $value)) {
                        throw new RouteParamFailedConstraintException(
                            'Value `' . $value . '` for param `' . $key . '` fails constraint `' . $regex . '`'
                        );
                    }
                }
            }
        }

        try {
            $this->fireEvents(EventHandler::EVENT_GET_URL, [
                'name'       => $name,
                'parameters' => $params,
            ]);

            return $this->routeCollector->generateUri($name, $params);
        } catch (Exception $e) {
            throw new NamedRouteNotFoundException($name, null);
        }
    }

    /**
     * Redirect one route to another.
     *
     * @param  string $from   Originating route.
     * @param  string $to     Destination route.
     * @param  int    $status HTTP status code.
     * @return RoutableInterface
     */
    public function redirect(string $from, string $to, int $status = 302)
    {
        $responseFactory = $this->responseFactory;
        $handler         = function () use ($to, $status, $responseFactory) {
            $response = $responseFactory->createResponse($status);
            return $response->withHeader('Location', (string) $to);
        };
        return $this->get($from, $handler);
    }

    /**
     * Create a permanent redirect from one URI to another.
     *
     * @param  string $uri
     * @param  string $destination
     * @return RoutableInterface
     */
    public function permanentRedirect($uri, $destination)
    {
        return $this->redirect($uri, $destination, 301);
    }

    /**
     * {@inheritdoc}
     */
    public function group($params, callable $callback): self
    {
        $group = new RouteGroup($params, $this);
        call_user_func($callback, $group);
        return $this;
    }

    /**
     * Set global middleware.
     */
    public function setBaseMiddleware(array $middleware): void
    {
        $this->baseMiddleware = $middleware;
    }

    /**
     * Get the basepath.
     *
     * @return string The basepath.
     */
    public function getBasePath(): string
    {
        return $this->basePath ?? $this->routeCollector->getBasePath();
    }

    /**
     * Get current route.
     *
     * @return mixed Current route.
     */
    public function currentRoute()
    {
        return $this->currentRoute;
    }

    /**
     * Get current route name.
     *
     * @return null|string Current route name.
     */
    public function currentRouteName(): ?string
    {
        return $this->currentRoute ? $this->currentRoute->getName() : null;
    }

    /**
     * Get routes.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get current request
     *
     * @return Qubus\Router\Http\Request
     */
    public function getRequest(): SystemRequest
    {
        return $this->qr;
    }

    /**
     * Register event handler
     */
    public function addEventHandler(EventHandlerInterface $handler): void
    {
        $this->eventHandlers[] = $handler;
    }

    /**
     * Get registered event-handler.
     */
    public function getEventHandlers(): array
    {
        return $this->eventHandlers;
    }

    /**
     * Fire event in event-handler.
     *
     * @param string $name
     */
    protected function fireEvents($name, array $arguments = []): void
    {
        if (count($this->eventHandlers) === 0) {
            return;
        }
        /** @var EventHandlerInterface $eventHandler */
        foreach ($this->eventHandlers as $eventHandler) {
            $eventHandler->fireEvents($this, $name, $arguments);
        }
    }

    /**
     * Sets other router methods.
     *
     * @param array $extras Router attributes.
     * @param Route $route Route object.
     */
    private function setExtrasOfSimpleJsonRoute($extras, RouteInterface $route): void
    {
        if (! empty($extras['name'])) {
            $route->name($extras['name']);
        }

        if (! empty($extras['middlewares'])) {
            $route->middleware($extras['middlewares']);
        }

        if (! empty($extras['domain'])) {
            $route->domain($extras['domain']);
        }

        if (! empty($extras['subDomain'])) {
            $route->subDomain($extras['subDomain']);
        }

        if (! empty($extras['namespace'])) {
            $route->namespace($extras['namespace']);
        }

        if (! empty($extras['where'])) {
            $route->where(...$extras['where']);
        }
    }
}
