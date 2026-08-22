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

use JsonException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\Request;
use Qubus\Inheritance\MacroAware;
use Qubus\Routing\Events\EventHandler;
use Qubus\Routing\Events\RoutingEventHandler;
use Qubus\Routing\Exceptions\NamedRouteNotFoundException;
use Qubus\Routing\Exceptions\RouteNameRedefinedException;
use Qubus\Routing\Exceptions\RouteParamFailedConstraintException;
use Qubus\Routing\Exceptions\TooLateToAddNewRouteException;
use Qubus\Routing\Interfaces\BootManager;
use Qubus\Routing\Interfaces\Collector;
use Qubus\Routing\Interfaces\Mappable;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Interfaces\Routable;
use Qubus\Routing\Route\InjectorMiddlewareResolver;
use Qubus\Routing\Route\Route;
use Qubus\Routing\Route\RouteAttributes;
use Qubus\Routing\Route\RouteCollector;
use Qubus\Routing\Route\RouteFileCache;
use Qubus\Routing\Route\RouteGroup;
use Qubus\Routing\Route\RouteParams;
use Qubus\Routing\Route\RouteResource;
use Qubus\Routing\Traits\RouteMapperAware;
use Relay\Relay;
use RuntimeException;
use Throwable;

use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function base64_decode;
use function base64_encode;
use function call_user_func;
use function count;
use function file_get_contents;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function ltrim;
use function Opis\Closure\serialize as opis_serialize;
use function Opis\Closure\unserialize as opis_unserialize;
use function preg_match;
use function preg_match_all;
use function str_ends_with;
use function str_replace;
use function strtoupper;
use function trim;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;

class Router implements Psr7Router, Mappable, MiddlewareInterface
{
    use MacroAware;
    use RouteMapperAware;

    private const int ROUTE_CACHE_VERSION = 2;

    //phpcs:disable
    public Request $request {
        get => $this->request;
    }

    public string $version = '4.3.0';

    /** @var array $routes */
    public array $routes = [] {
        &get => $this->routes;
    }

    protected Collector $routeCollector;

    protected bool $routesCreated = false {
        set(bool $value) => $this->routesCreated = $value;
    }

    protected int $routeCollectorMatchTypeId = 1;

    protected string $basePath = '';

    protected ?Route $currentRoute = null;

    protected ?ContainerInterface $container = null;

    protected ?ResponseFactoryInterface $responseFactory = null;

    protected ?MiddlewareResolver $middlewareResolver = null;

    protected ?Invoker $invoker = null;

    protected ?RouteFileCache $routeCache = null;

    /** @var array $baseMiddleware */
    public array $baseMiddleware = [] {
        set(array $value) => $this->baseMiddleware = $value;
    }

    protected ?string $defaultNamespace = null;

    protected string $namespace = '';

    /** @var array $bootManagers */
    protected array $bootManagers = [];

    /** @var array $eventHandlers */
    protected array $eventHandlers = [];
    //phpcs:enable

    public function __construct(
        Collector $routeCollector,
        ContainerInterface $container,
        ?ResponseFactoryInterface $responseFactory = null,
        ?MiddlewareResolver $resolver = null
    ) {

        $this->setContainer(container: $container);

        if (isset($responseFactory)) {
            $this->responseFactory = $responseFactory;
        }

        $this->middlewareResolver = $resolver ?? new InjectorMiddlewareResolver($container);

        $this->request = new Request();
        /**
         * Set route collector instance.
         */
        $this->routeCollector = $routeCollector;
        $this->setBasePath(basePath: '/');
    }

    public function prependUrl(string $url): void
    {
        $this->setBasePath($url);
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
        if ($this->routesCreated && $this->routeCollector instanceof RouteCollector) {
            $this->routeCollector->clearRoutes();
        }

        $this->basePath = Formatting::addLeadingSlash(input: Formatting::addTrailingSlash(input: $basePath));
        /**
         * Force the router to rebuild next time we need it.
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
     * Use this method to enable route caching.
     *
     * @param string $file
     * @return void
     */
    public function enableRouteCache(string $file): void
    {
        $this->routeCache = new RouteFileCache($file);
    }

    /**
     * Disable route caching for this router instance.
     */
    public function disableRouteCache(): void
    {
        $this->routeCache = null;
    }

    public function hasRouteCache(): bool
    {
        return $this->routeCache !== null;
    }

    public function routeIsCached(): bool
    {
        return $this->routeCache?->exists() ?? false;
    }

    public function clearRouteCache(): void
    {
        $this->routeCache?->clear();
    }

    public function getRouteCachePath(): ?string
    {
        return $this->routeCache?->path();
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
        $output = $route->uri;

        preg_match_all('/{\s*([a-zA-Z0-9]+\??)\s*}/s', $route->uri, $matches);

        $paramConstraints = $route->paramConstraints;

        for ($i = 0; $i < count($matches[0]); $i++) {
            $match    = $matches[0][$i];
            $paramKey = $matches[1][$i];

            $optional = str_ends_with($paramKey, '?');
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
    public function map(array $verbs, string $uri, array|callable|string $callback): Routable
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
     * @param string $name
     * @param string $controller
     * @param array $options
     * @return Routable
     */
    public function apiResource(string $name, string $controller, array $options = []): Routable
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
     * @throws TooLateToAddNewRouteException
     * @throws TypeException
     * @throws JsonException
     */
    public function loadRoutesFromJson(string $path): void
    {
        $content = file_get_contents(filename: $path);

        if ($content === false) {
            throw new RuntimeException("Unable to read routes file [{$path}].");
        }

        $json = json_decode(json: $content, associative: true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($json) || ! isset($json['routes']) || ! is_array($json['routes'])) {
            throw new RuntimeException("Routes file [{$path}] must contain a routes array.");
        }

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
     * @throws TooLateToAddNewRouteException
     * @throws TypeException
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
     * @throws TooLateToAddNewRouteException
     * @throws TypeException
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

    protected function buildRoutes(): void
    {
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

        $this->registerRoutesWithCollector();

        $this->fireEvents(name: RoutingEventHandler::EVENT_LOAD, arguments: [
            'loadedRoutes' => $this->routes,
        ]);
    }

    private function registerRoutesWithCollector(): void
    {
        $this->routeCollector->basePath = $this->basePath;

        foreach ($this->routes as $route) {
            $uri = $this->convertRouteToRouteCollectorRouterUri(route: $route, routeCollector: $this->routeCollector);
            $this->routeCollector->domain = $route->getDomain();
            /**
             * Canonical URI with trailing slash - becomes named route
             * if name is provided
             */
            $this->routeCollector->map(
                implode(separator: '|', array: $route->methods),
                $route->getSubDomain() ?? null,
                Formatting::addTrailingSlash($uri),
                $route,
                $route->name ?? null
            );
            /**
             * Also register URI without trailing slash
             */
            $this->routeCollector->map(
                implode(separator: '|', array: $route->methods),
                $route->getSubDomain() ?? null,
                Formatting::removeTrailingSlash($uri),
                $route
            );
        }
    }

    protected function createRoutes(): void
    {
        if ($this->routesCreated) {
            return;
        }

        if ($this->routeCache !== null) {
            if ($this->routeCache->exists()) {
                $compiled = $this->routeCache->read();

                if ($this->isCurrentRouteCache($compiled)) {
                    $this->importCompiledRoutes($compiled['routes']);
                    $this->routesCreated = true;
                    return;
                }
            }

            $this->buildRoutes();
            $this->routeCache->put($this->exportCompiledRoutes());
            $this->routesCreated = true;
            return;
        }

        $this->buildRoutes();
        $this->routesCreated = true;
    }

    private function isCurrentRouteCache(array $compiled): bool
    {
        if (($compiled['version'] ?? null) !== self::ROUTE_CACHE_VERSION) {
            return false;
        }

        return isset($compiled['routes']) && is_array($compiled['routes']);
    }

    protected function exportCompiledRoutes(): array
    {
        $compiled = [];

        foreach ($this->routes as $route) {
            $compiled[] = [
                'methods'     => $route->methods,
                'uri'         => $route->uri,
                'action'      => $this->serializeRouteCacheValue(
                    $route->getRouteAction()->getAction(),
                    "action for route [{$route->uri}]"
                ),
                'name'        => $route->name,
                'domain'      => $route->getDomain(),
                'subdomain'   => $route->getSubDomain(),
                'schemes'     => $route->getSchemes(),
                'constraints' => $route->paramConstraints,
                'namespace'   => $route->getNamespace(),
                'middleware'  => $this->serializeRouteCacheValue(
                    $route->getMiddlewares(),
                    "middleware for route [{$route->uri}]"
                ),
            ];
        }

        return [
            'version' => self::ROUTE_CACHE_VERSION,
            'routes'  => $compiled,
        ];
    }

    private function serializeRouteCacheValue(mixed $value, string $description): string
    {
        try {
            return base64_encode(opis_serialize($value));
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to cache {$description}: {$exception->getMessage()}",
                previous: $exception
            );
        }
    }

    private function unserializeRouteCacheValue(string $value, string $description): mixed
    {
        $serialized = base64_decode($value, true);

        if ($serialized === false) {
            throw new RuntimeException("Invalid encoded {$description} in the route cache.");
        }

        try {
            return opis_unserialize($serialized);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Unable to restore {$description} from the route cache: {$exception->getMessage()}",
                previous: $exception
            );
        }
    }

    /**
     * @throws TypeException
     * @throws RouteNameRedefinedException
     */
    protected function importCompiledRoutes(array $compiled): void
    {
        $routes = [];

        foreach ($compiled as $index => $definition) {
            if (! is_array($definition)) {
                throw new RuntimeException("Invalid route cache definition at index [{$index}].");
            }

            foreach (['methods', 'uri', 'action', 'middleware'] as $key) {
                if (! array_key_exists($key, $definition)) {
                    throw new RuntimeException(
                        "Route cache definition at index [{$index}] is missing [{$key}]."
                    );
                }
            }

            if (
                ! is_array($definition['methods'])
                || ! is_string($definition['uri'])
                || ! is_string($definition['action'])
                || ! is_string($definition['middleware'])
            ) {
                throw new RuntimeException("Invalid route cache definition at index [{$index}].");
            }

            $action = $this->unserializeRouteCacheValue(
                $definition['action'],
                "action at route cache index [{$index}]"
            );

            $route = new Route(
                methods: $definition['methods'],
                uri: $definition['uri'],
                action: $action,
                defaultNamespace: $definition['namespace'] ?? null,
                invoker: $this->invoker,
                middlewareResolver: $this->middlewareResolver
            );

            if (($definition['name'] ?? null) !== null) {
                $route->name($definition['name']);
            }

            if (array_key_exists('domain', $definition)) {
                $route->domain($definition['domain']);
            }

            if (array_key_exists('subdomain', $definition)) {
                $route->subDomain($definition['subdomain']);
            }

            if (! empty($definition['schemes'])) {
                $route->setScheme(...$definition['schemes']);
            }

            if (! empty($definition['constraints'])) {
                $route->where($definition['constraints']);
            }

            $middleware = $this->unserializeRouteCacheValue(
                $definition['middleware'],
                "middleware at route cache index [{$index}]"
            );

            if (! is_array($middleware)) {
                throw new RuntimeException("Invalid middleware at route cache index [{$index}].");
            }

            if ($middleware !== []) {
                $route->middleware($middleware);
            }

            $routes[] = $route;
        }

        $this->routes = $routes;

        if ($this->routeCollector instanceof RouteCollector) {
            $this->routeCollector->clearRoutes();
        }

        $this->registerRoutesWithCollector();

        $this->fireEvents(name: RoutingEventHandler::EVENT_LOAD, arguments: [
            'loadedCacheRoutes' => $this->routes,
        ]);
    }

    /**
     * Method to override/normalize the HTTP method before match/dispatch.
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    protected function normalizeHttpMethod(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($request->getMethod() !== 'POST') {
            return $request;
        }

        $override = $request->getHeaderLine('X-HTTP-Method-Override');

        if ($override === '') {
            $body = $request->getParsedBody();
            if (is_array($body) && isset($body['_method']) && is_string($body['_method'])) {
                $override = $body['_method'];
            }
        }

        $override = trim($override);

        if ($override !== '' && preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/D", $override) === 1) {
            return $request->withMethod(strtoupper($override));
        }

        return $request;
    }

    /**
     * Add route.
     *
     * @param Route $route The route.
     * @return void Add route to routes array.
     * @throws TooLateToAddNewRouteException
     */
    public function hydrateRoute(Routable $route): void
    {
        $this->addRoute($route);
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    public function match(ServerRequestInterface $serverRequest): ResponseInterface
    {
        $serverRequest = $this->normalizeHttpMethod($serverRequest);
        $this->currentRoute = null;

        $this->fireEvents(name: RoutingEventHandler::EVENT_INIT);
        $this->createRoutes();

        $uri = $this->request->getRewriteUrl() ?? $serverRequest->getUri()->getPath();

        if ($this->routeCollector instanceof RouteCollector) {
            $collectorRoute = $this->routeCollector->match(
                requestHost: $serverRequest->getUri()->getHost(),
                requestUrl: $uri,
                requestMethod: $serverRequest->getMethod(),
                requestScheme: $serverRequest->getUri()->getScheme()
            );
        } else {
            $collectorRoute = $this->routeCollector->match(
                requestHost: $serverRequest->getUri()->getHost(),
                requestUrl: $uri,
                requestMethod: $serverRequest->getMethod()
            );
        }

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
        $serverRequest = $serverRequest
            ->withAttribute(self::class, $route)
            ->withAttribute(RouteAttributes::ROUTE, $route)
            ->withAttribute(RouteAttributes::PARAMS, $params)
            ->withAttribute(RouteAttributes::URI, $route->uri)
            ->withAttribute(RouteAttributes::METHODS, $route->methods)
            ->withAttribute(RouteAttributes::NAME, $route->name);

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
         */
        $dispatcher = new Relay(queue: $middlewares, resolver: function ($name) {
            if (! isset($this->middlewareResolver)) {
                return $name;
            }
            return $this->middlewareResolver->resolve(definition: $name);
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
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        $this->fireEvents(name: RoutingEventHandler::EVENT_FIND_ROUTE, arguments: [
            'name' => $name,
        ]);

        $routes = array_filter(array: $this->routes, callback: function ($route) use ($name) {
            return $route->name === $name;
        });
        return count($routes) > 0;
    }

    /**
     * {@inheritDoc}
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
            if ($route->name === $name) {
                $matchedRoute = $route;
            }
        }
        if ($matchedRoute) {
            $paramConstraints = $matchedRoute->paramConstraints;

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
     * {@inheritDoc}
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
     * Get the basepath.
     *
     * @return string The basepath.
     */
    public function getBasePath(): string
    {
        return $this->basePath ?? $this->routeCollector->basePath;
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
     * Get the current route name.
     *
     * @return null|string Current route name.
     */
    public function currentRouteName(): ?string
    {
        return $this->currentRoute?->name;
    }

    /**
     * Register event handler
     */
    public function setEventHandlers(EventHandler $handler): void
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

    /**
     * @throws \Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute(self::class, $this->currentRoute);
        $response = $this->match($request);

        if ($this->currentRoute === null) {
            return $handler->handle($request->withAttribute(self::class, 'Not Found'));
        }

        return $response;
    }
}
