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

namespace Qubus\Routing;

use Exception as PHPException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Data\TypeException;
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

    public string $version = '2.0.0';

    /** @var array $routes */
    protected array $routes = [];

    protected Collector $routeCollector;

    protected bool $routesCreated = false;

    protected int $routeCollectorMatchTypeId = 1;

    protected string $basePath = '';

    protected ?Route $currentRoute = null;

    protected ?ContainerInterface $container = null;

    protected ?ResponseFactoryInterface $responseFactory = null;

    protected ?MiddlewareResolver $middlewareResolver = null;

    protected ?Invoker $invoker = null;

    /** @var array $baseMiddleware */
    protected array $baseMiddleware = [];

    protected ?string $defaultNamespace = null;

    protected string $namespace = '';

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
            $this->setContainer(container: $container);
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
        $this->setBasePath(basePath: '/');
    }

    public function prependUrl(string $url): void
    {
        $this->routeCollector->setBasePath(basePath: $url);
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
        $this->invoker = new Invoker(container: $this->container);
    }

    /**
     * Set the basepath.
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = Formatting::addLeadingSlash(input: Formatting::addTrailingSlash(input: $basePath));
        /**
         * Force the router to rebuild next time we need it.
         *
         * @var bool
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
    protected function addRoute(Routable $route): void
    {
        $this->fireEvents(name: RoutingEventHandler::EVENT_ADD_ROUTE, arguments: [
            'route' => $route,
        ]);

        if ($this->routesCreated) {
            throw new TooLateToAddNewRouteException(message: 'Routes already created.');
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

            $optional = substr(string: $paramKey, offset: -1) === '?';
            $paramKey = trim(string: $paramKey, characters: '?');

            $regex       = $paramConstraints[$paramKey] ?? null;
            $matchTypeId = '';

            if (! empty($regex)) {
                $matchTypeId = 'rare' . $this->routeCollectorMatchTypeId++;
                $routeCollector->addMatchTypes(matchTypes: [
                    $matchTypeId => $regex,
                ]);
            }

            $replacement = '[' . $matchTypeId . ':' . $paramKey . ']';

            if ($optional) {
                $replacement .= '?';
            }

            $output = str_replace(search: $match, replace: $replacement, subject: $output);
        }

        return ltrim(string: $output, characters: ' /');
    }

    /**
     * @param array $verbs HTTP methods.
     * @param string $uri Route path.
     * @return Route
     * @throws TooLateToAddNewRouteException
     */
    public function map(array $verbs, string $uri, callable|string $callback): Routable
    {
        /**
         * Force all verbs to be uppercase.
         */
        $verbs = array_map(callback: 'strtoupper', array: $verbs);

        $route = new Route(
            methods: $verbs,
            uri: $uri,
            action: $callback,
            defaultNamespace: $this->defaultNamespace,
            invoker: $this->invoker,
            middlewareResolver: $this->middlewareResolver
        );
        $this->addRoute(route: $route);
        return $route;
    }

    /**
     * Register an array of resource controllers.
     */
    public function resources(array $resources, array $options = []): void
    {
        foreach ($resources as $name => $controller) {
            $this->resource(name: $name, controller: $controller, options: $options);
        }
    }

    /**
     * Route a resource to a controller.
     */
    public function resource(string $name, string $controller, array $options = []): mixed
    {
        $resource = new RouteResource(router: $this);
        return $resource->register(name: $name, controller: $controller, options: $options);
    }

    /**
     * Register an array of API resource controllers.
     */
    public function apiResources(array $resources, array $options = []): void
    {
        foreach ($resources as $name => $controller) {
            $this->apiResource(name: $name, controller: $controller, options: $options);
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

        return $this->resource(name: $name, controller: $controller, options: array_merge([
            'only' => $only,
        ], $options));
    }

    /**
     * Load routes from a JSON file.
     *
     * @param string $path Path to the JSON routes file.
     * @throws TooLateToAddNewRouteException|TypeException
     */
    public function loadRoutesFromJson(string $path): void
    {
        $content = file_get_contents(filename: $path);
        $json    = json_decode(json: $content, associative: true);

        foreach ($json['routes'] as $route) {
            if (! empty($route['group'])) {
                $this->handleGroupJsonRoutes(route: $route);
                continue;
            }
            $this->handleSimpleJsonRoutes(route: $route);
        }
    }

    /**
     * Converts JSON routes to a route object.
     *
     * @param array $route Array from JSON file.
     * @throws TooLateToAddNewRouteException|TypeException
     */
    public function handleSimpleJsonRoutes(array $route): void
    {
        $map = $this->map(verbs: $route['method'], uri: $route['path'], callback: $route['callback']);

        $this->setExtrasOfSimpleJsonRoute(extras: $route, route: $map);
    }

    /**
     * Converts JSON group routes to a route object.
     *
     * @param array $route Array form JSON file.
     * @throws TooLateToAddNewRouteException|TypeException
     */
    public function handleGroupJsonRoutes($route): void
    {
        foreach ($route['group']['routes'] as $routeGroup) {
            if (isset($routeGroup['group'])) {
                $this->handleGroupJsonRoutes(route: $routeGroup);
                continue;
            }
            $this->handleSimpleJsonRoutes(route: $routeGroup);
        }
    }

    protected function createRoutes(): void
    {
        if ($this->routesCreated) {
            return;
        }

        $this->routeCollector->setBasePath(basePath: $this->basePath);

        $this->fireEvents(name: RoutingEventHandler::EVENT_BOOT, arguments: [
            'bootmanagers' => $this->bootManagers,
        ]);

        /* Initialize boot-managers */
        foreach ($this->bootManagers as $manager) {
            $this->fireEvents(name: RoutingEventHandler::EVENT_RENDER_BOOTMANAGER, arguments: [
                'bootmanagers' => $this->bootManagers,
                'bootmanager'  => $manager,
            ]);

            /* Render bootmanager */
            $manager->boot($this, $this->request);
        }

        $this->routesCreated = true;

        $this->fireEvents(name: RoutingEventHandler::EVENT_LOAD_ROUTES, arguments:[
            'routes' => $this->routes,
        ]);

        foreach ($this->routes as $route) {
            $uri = $this->convertRouteToRouteCollectorRouterUri(route: $route, routeCollector: $this->routeCollector);
            $this->routeCollector->setDomain(domain: $route->getDomain());
            /**
             * Canonical URI with trailing slash - becomes named route
             * if name is provided
             */
            $this->routeCollector->map(
                implode(separator: '|', array: $route->getMethods()),
                $route->getSubDomain() ?? null,
                Formatting::addTrailingSlash($uri),
                $route,
                $route->getName() ?? null
            );
            /**
             * Also register URI without trailing slash
             */
            $this->routeCollector->map(
                implode(separator: '|', array: $route->getMethods()),
                $route->getSubDomain() ?? null,
                Formatting::removeTrailingSlash($uri),
                $route
            );
        }

        $this->fireEvents(name: RoutingEventHandler::EVENT_LOAD, arguments: [
            'loadedRoutes' => $this->getRoutes(),
        ]);
    }

    /**
     * @return mixed
     * @throws PHPException
     */
    public function match(ServerRequestInterface $serverRequest): ResponseInterface
    {
        $this->fireEvents(name: RoutingEventHandler::EVENT_INIT);

        $this->createRoutes();

        $uri = $this->request->getRewriteUrl() ?? $serverRequest->getUri()->getPath();

        $collectorRoute = $this->routeCollector->match(
            requestHost: $serverRequest->getUri()->getHost(),
            requestUrl: $uri,
            requestMethod: $serverRequest->getMethod()
        );

        $route  = $collectorRoute['target'] ?? null;
        $params = new RouteParams(params: $collectorRoute['params'] ?? []);

        if (! $route) {
            return JsonResponseFactory::create(
                data: 'Resource not found.',
                status: 404,
                headers: ['Content-Type' => ['application/hal+json']],
                encodingOptions: JSON_PRETTY_PRINT
            );
        }

        $this->fireEvents(name: RoutingEventHandler::EVENT_MATCH_ROUTE, arguments: [
            'route' => $route,
        ]);

        $this->currentRoute = $route;
        return $this->handle(route: $route, serverRequest: $serverRequest, params: $params);
    }

    /**
     * @param object $route
     * @param ServerRequestInterface $serverRequest
     * @param RouteParams $params
     * @return ResponseInterface
     */
    protected function handle(
        object $route,
        ServerRequestInterface $serverRequest,
        RouteParams $params
    ): ResponseInterface {
        if (count($this->baseMiddleware) === 0) {
            $this->fireEvents(name: RoutingEventHandler::EVENT_RENDER_MIDDLEWARES, arguments: [
                'route'       => $route,
                'middlewares' => $route->gatherMiddlewares(),
            ]);
            return $route->handle($serverRequest, $params);
        }

        $this->fireEvents(name: RoutingEventHandler::EVENT_RENDER_MIDDLEWARES, arguments: [
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
        $dispatcher = new Relay(queue: $middlewares, resolver: function ($name) {
            if (! isset($this->middlewareResolver)) {
                return $name;
            }
            return $this->middlewareResolver->resolve(name: $name);
        });
        return $dispatcher->handle(request: $serverRequest);
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
     * Check if a route exists based on its name.
     *
     * @param  string $name The name of the route.
     * @return bool True if the named routed exists, false otherwise.
     */
    public function has(string $name): bool
    {
        $this->fireEvents(name: RoutingEventHandler::EVENT_FIND_ROUTE, arguments: [
            'name' => $name,
        ]);

        $routes = array_filter(array: $this->routes, callback: function ($route) use ($name) {
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
    public function url(string $name, array $params = []): string
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
                            message: 'Value `' . $value . '` for param `' . $key . '` fails constraint `' . $regex . '`'
                        );
                    }
                }
            }
        }

        try {
            $this->fireEvents(name: RoutingEventHandler::EVENT_GET_URL, arguments: [
                'name'       => $name,
                'parameters' => $params,
            ]);

            return $this->routeCollector->generateUri(routeName: $name, params: $params);
        } catch (Exception $e) {
            throw new NamedRouteNotFoundException(message: $name, code: 0);
        }
    }

    /**
     * Redirect one route to another.
     *
     * @param string $from Originating route.
     * @param string $to Destination route.
     * @param int $status HTTP status code.
     * @return Routable
     * @throws TooLateToAddNewRouteException
     */
    public function redirect(string $from, string $to, int $status = 302): Routable
    {
        $responseFactory = $this->responseFactory;
        $handler         = function () use ($to, $status, $responseFactory) {
            $response = $responseFactory->createResponse(code: $status);
            return $response->withHeader('Location', (string) $to);
        };
        return $this->get(uri: $from, callback: $handler);
    }

    /**
     * Create a permanent redirect from one URI to another.
     *
     * @param string $uri
     * @param string $destination
     * @return Routable|Mappable
     * @throws TooLateToAddNewRouteException
     */
    public function permanentRedirect(string $uri, string $destination): Routable|Mappable
    {
        return $this->redirect(from: $uri, to: $destination, status: 301);
    }

    /**
     * {@inheritdoc}
     */
    public function group(array|string $params, callable $callback): self
    {
        $group = new RouteGroup(params: $params, router: $this);
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
     * @return Route|null Current route.
     */
    public function currentRoute(): ?Route
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
        return $this->currentRoute?->getName();
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
     */
    public function getRequest(): RequestInterface
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
     * @param array $arguments
     */
    protected function fireEvents(string $name, array $arguments = []): void
    {
        if (count($this->eventHandlers) === 0) {
            return;
        }
        /** @var EventHandler $eventHandler */
        foreach ($this->eventHandlers as $eventHandler) {
            $eventHandler->fireEvents(router: $this, name: $name, eventArgs: $arguments);
        }
    }

    /**
     * Sets other router methods.
     *
     * @param array $extras Router attributes.
     * @param Routable $route Route object.
     * @throws TypeException
     */
    private function setExtrasOfSimpleJsonRoute(array $extras, Routable $route): void
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
