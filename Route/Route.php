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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Routing\Exceptions\RouteNameRedefinedException;
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Interfaces\Routable;
use Qubus\Routing\Invoker;
use Relay\Relay;
use Spatie\Macroable\Macroable;

use function array_merge;
use function count;
use function func_get_args;
use function is_array;
use function preg_match;
use function rtrim;
use function str_replace;
use function trim;

final class Route implements Routable
{
    use Macroable;

    protected $uri;
    protected $methods = [];
    protected $routeAction;
    protected $name;
    protected $domain;
    protected $subDomain;
    protected $schemes = [];
    protected $invoker;
    protected $middlewareResolver;
    protected $middlewares      = [];
    protected $paramConstraints = [];
    protected $controllerName;
    protected $controllerMethod;
    protected $defaultNamespace;
    protected $namespace;

    /**
     * @param mixed $action
     */
    public function __construct(
        array $methods,
        string $uri,
        $action,
        ?string $defaultNamespace = null,
        ?Invoker $invoker = null,
        ?MiddlewareResolver $middlewareResolver = null
    ) {
        $this->defaultNamespace   = $defaultNamespace;
        $this->invoker            = $invoker;
        $this->middlewareResolver = $middlewareResolver;
        $this->methods            = $methods;
        $this->setUri($uri);
        $this->setAction($action);
    }

    protected function setUri(string $uri)
    {
        $this->uri = rtrim($uri, ' /');
    }

    protected function setAction($action)
    {
        $this->routeAction = new RouteAction($action, $this->getNamespace(), $this->invoker);
    }

    /**
     * Prepend url
     *
     * @return string
     */
    public function prependUrl(string $uri)
    {
        return $this->setUri(rtrim($uri, '/') . $this->uri);
    }

    public function handle(RequestInterface $request, RouteParams $params): ResponseInterface
    {
        /**
         * Get all the middleware registered for this route
         */
        $middlewares = $this->gatherMiddlewares();
        /**
         * Add our route handler as the last item.
         */
        $middlewares[] = function ($request) use ($params) {
            $output = $this->routeAction->invoke($request, $params);
            return ResponseFactory::create($request, $output);
        };
        /**
         * Create and process the dispatcher.
         */
        $dispatcher = new Relay($middlewares, function ($name) {
            if (! isset($this->middlewareResolver)) {
                return $name;
            }
            return $this->middlewareResolver->resolve($name);
        });

        return $dispatcher->handle($request);
    }

    public function gatherMiddlewares(): array
    {
        return array_merge([], $this->middlewares, $this->routeAction->getMiddlewares());
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function name(?string $name): Routable
    {
        if (isset($this->name)) {
            throw new RouteNameRedefinedException();
        }
        $this->name = $name;
        return $this;
    }

    public function domain(?string $domain): Routable
    {
        if (false !== preg_match('@^(?:(https?):)?(\/\/[^/]+)@i', $domain, $matches)) {
            if (empty($matches)) {
                $matches = [$domain, null, $domain];
            }

            [, $scheme, $domain] = $matches;

            if (! empty($scheme)) {
                $this->setScheme($scheme);
            }
        }
        $this->domain = trim($domain, '//');
        return $this;
    }

    public function subDomain(?string $subdomain): Routable
    {
        if (false !== preg_match('@^(?:(https?):)?(\/\/[^/]+)@i', $subdomain, $matches)) {
            if (empty($matches)) {
                $matches = [$subdomain, null, $subdomain];
            }

            [, $scheme, $subdomain] = $matches;

            if (! empty($scheme)) {
                $this->setScheme($scheme);
            }
        }
        $this->subDomain = trim($subdomain, '//');
        return $this;
    }

    public function namespace(?string $namespace): Routable
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function getSchemes(): array
    {
        return $this->schemes;
    }

    public function setScheme(string ...$schemes): self
    {
        foreach ($schemes as $scheme) {
            $this->schemes[] = $scheme;
        }
        return $this;
    }

    public function where(): self
    {
        $args = func_get_args();
        if (count($args) === 0) {
            throw new TypeException();
        }
        if (is_array($args[0])) {
            foreach ($args[0] as $key => $value) {
                $this->paramConstraints[$key] = $value;
            }
        } else {
            $this->paramConstraints[$args[0]] = $args[1];
        }
        return $this;
    }

    public function getParamConstraints(): array
    {
        return $this->paramConstraints;
    }

    public function middleware(): Routable
    {
        $args = func_get_args();
        foreach ($args as $middleware) {
            if (is_array($middleware)) {
                $this->middlewares += $middleware;
            } else {
                $this->middlewares[] = $middleware;
            }
        }
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDomain(): ?string
    {
        return str_replace(['http://', 'https://'], '', (string) $this->domain);
    }

    public function getSubDomain(): ?string
    {
        return str_replace(['http://', 'https://'], '', (string) $this->subDomain);
    }

    public function getNamespace(): ?string
    {
        return $this->namespace ?? $this->defaultNamespace;
    }

    public function getActionName(): string
    {
        return $this->routeAction->getActionName();
    }
}
