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

namespace Qubus\Routing;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\Request;
use Qubus\Routing\Events\EventHandler;
use Qubus\Routing\Events\RoutingEventHandler;
use Qubus\Routing\Exceptions\NamedRouteNotFoundException;
use Qubus\Routing\Exceptions\RouteParamFailedConstraintException;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Interfaces\BootManager;
use Qubus\Routing\Interfaces\Collector;
use Qubus\Routing\Interfaces\Mappable;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Interfaces\Routable;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Route\RouteGroup;
use Qubus\Routing\Route\RouteParams;
use Qubus\Routing\Route\RouteResource;
use Qubus\Routing\Traits\RouteMapper;
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

final class Router implements Mappable
{
    use Macroable;
    use RouteMapper;

    protected Request $request;

    public string $version = '1.1.1';

    /** @var array $routes */
    protected array $routes = [];

    protected Collector $routeCollector;

    protected bool $routesCreated = false;

    protected int $routeCollectorMatchTypeId = 1;

    protected string $basePath;

    protected ?Route $currentRoute = null;

    protected ?ContainerInterface $container = null;

    protected ?ResponseFactoryInterface $responseFactory = null;

    protected ?MiddlewareResolver $middlewareResolver = null;

    protected ?Invoker $invoker = null;

    /** @var array $baseMiddleware */
    protected array $baseMiddleware = [];

    protected ?string $defaultNamespace = null;

    protected string $namespace;

    /** @var array $bootManagers */
    protected array $bootManagers = [];

    /** @var array $eventHandlers */
    protected array $eventHandlers = [];

    public function __construct(
        Collector $routeCollector,
        ?ContainerInterface $container = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?MiddlewareResolver $resolver = null
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

        $this->request = new Request();
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
     * @param Route $route The route.
     * @return void Add route to routes array.
     * @throws TooLateToAddNewRouteException
     */
    protected function addRoute(Routable $route)
    {
        $this->fireEvents(RoutingEventHandler::EVENT_ADD_ROUTE, [
            'route' => $route,
        ]);

        if ($this->routesCreated) {
            throw new TooLateToAddNewRouteException();
        }
        $this->routes[] = $route;
    }

    protected function convertRouteToRouteCollectorRouterUri(
        Routable $route,
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
    public function map(array $verbs, string $uri, $callback): Routable
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
    public function resource($name, $controller, array $options = []): Routable
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
    public function apiResource($name, $controller, array $options = []): Routable
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
     * @param  array $route Array from JSON file.
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

        $this->fireEvents(RoutingEventHandler::EVENT_BOOT, [
            'bootmanagers' => $this->bootManagers,
        ]);

        /* Initialize boot-managers */
        foreach ($this->bootManagers as $manager) {
            $this->fireEvents(RoutingEventHandler::EVENT_RENDER_BOOTMANAGER, [
                'bootmanagers' => $this->bootManagers,
                'bootmanager'  => $manager,
            ]);

            /* Render bootmanager */
            $manager->boot($this, $this->request);
        }

        $this->routesCreated = true;

        $this->fireEvents(RoutingEventHandler::EVENT_LOAD_ROUTES, [
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

        $this->fireEvents(RoutingEventHandler::EVENT_LOAD, [
            'loadedRoutes' => $this->getRoutes(),
        ]);
    }

    /**
     * @return mixed
     */
    public function match(ServerRequestInterface $serverRequest): ResponseInterface
    {
        $this->fireEvents(RoutingEventHandler::EVENT_INIT);

        $this->createRoutes();

        $uri = $this->request->getRewriteUrl() ?? $serverRequest->getUri()->getPath();

        $collectorRoute = $this->routeCollector->match(
            $serverRequest->getUri()->getHost(),
            $uri,
            $serverRequest->getMethod()
        );

        $route  = $collectorRoute['target'] ?? null;
        $params = new RouteParams($collectorRoute['params'] ?? []);

        if (! $route) {
            return JsonResponseFactory::create(
                'Resource not found.',
                404,
                ['Content-Type' => ['application/hal+json']],
                JSON_PRETTY_PRINT
            );
        }

        $this->fireEvents(RoutingEventHandler::EVENT_MATCH_ROUTE, [
            'route' => $route,
        ]);

        $this->currentRoute = $route;
        return $this->handle($route, $serverRequest, $params);
    }

    /**
     * @param  object $route
     * @param  array  $params
     */
    protected function handle($route, ServerRequestInterface $serverRequest, $params): ResponseInterface
    {
        if (count($this->baseMiddleware) === 0) {
            $this->fireEvents(RoutingEventHandler::EVENT_RENDER_MIDDLEWARES, [
                'route'       => $route,
                'middlewares' => $route->gatherMiddlewares(),
            ]);
            return $route->handle($serverRequest, $params);
        }

        $this->fireEvents(RoutingEventHandler::EVENT_RENDER_MIDDLEWARES, [
            'route'       => $route,
            'middlewares' => $route->gatherMiddlewares(),
        ]);
        /**
         * Apply all the base middleware and trigger the route handler as the
         * last in the chain
         */
        $middlewares = array_merge($this->baseMiddleware, [
            function ($serverRequest) use ($route, $params) {
                return $route->handle($serverRequest, $params);
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
        return $dispatcher->handle($serverRequest);
    }

    /**
     * Add BootManager
     *
     * @return static
     */
    public function addBootManager(BootManager $bootManager): self
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
        $this->fireEvents(RoutingEventHandler::EVENT_FIND_ROUTE, [
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
            $this->fireEvents(RoutingEventHandler::EVENT_GET_URL, [
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
     * @return Mappable
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
     * @return Mappable
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
     * @return Qubus\Http\Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Register event handler
     */
    public function addEventHandler(EventHandler $handler): void
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
        /** @var EventHandler $eventHandler */
        foreach ($this->eventHandlers as $eventHandler) {
            $eventHandler->fireEvents($this, $name, $arguments);
        }
    }

    /**
     * Sets other router methods.
     *
     * @param array $extras Router attributes.
     * @param Routable $route Route object.
     */
    private function setExtrasOfSimpleJsonRoute($extras, Routable $route): void
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

        if (! empty($extras['subdomain'])) {
            $route->subDomain($extras['subdomain']);
        }

        if (! empty($extras['namespace'])) {
            $route->namespace($extras['namespace']);
        }

        if (! empty($extras['where'])) {
            $route->where(...$extras['where']);
        }
    }
}
