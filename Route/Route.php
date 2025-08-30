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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Inheritance\MacroAware;
use Qubus\Routing\Exceptions\RouteNameRedefinedException;
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Interfaces\MiddlewareResolver;
use Qubus\Routing\Interfaces\Routable;
use Qubus\Routing\Invoker;
use Relay\Relay;

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
    use MacroAware;

    public string $uri = '' {
        get => $this->uri;
        set(string $value) => $this->uri = rtrim(string: $value, characters: ' /');
    }
    public array $methods = [] {
        get => $this->methods;
    }
    protected RouteAction $routeAction;
    public ?string $name = null {
        get => $this->name;
    }
    protected ?string $domain = null;
    protected ?string $subDomain = null;
    protected array $schemes = [] {
        &get => $this->schemes;
    }
    protected ?Invoker $invoker = null;
    protected ?MiddlewareResolver $middlewareResolver = null;
    protected array $middlewares = [];
    public array $paramConstraints = [] {
        &get => $this->paramConstraints;
    }
    protected ?string $defaultNamespace = null;
    protected ?string $namespace = null;

    public function __construct(
        array $methods,
        string $uri,
        mixed $action,
        ?string $defaultNamespace = null,
        ?Invoker $invoker = null,
        ?MiddlewareResolver $middlewareResolver = null
    ) {
        $this->defaultNamespace   = $defaultNamespace;
        $this->invoker            = $invoker;
        $this->middlewareResolver = $middlewareResolver;
        $this->methods            = $methods;
        $this->uri = $uri;
        $this->setAction(action: $action);
    }

    protected function setAction(mixed $action): void
    {
        $this->routeAction = new RouteAction(
            action: $action,
            namespace: $this->getNamespace(),
            invoker: $this->invoker
        );
    }

    /**
     * Prepend url
     */
    public function prependUrl(string $uri): void
    {
        $this->uri = rtrim(string: $uri, characters: '/') . $this->uri;
    }

    public function handle(ServerRequestInterface $request, RouteParams $params): ResponseInterface
    {
        /**
         * Get all the middleware registered for this route
         */
        $middlewares = $this->gatherMiddlewares();
        /**
         * Add our route handler as the last item.
         */
        $middlewares[] = function ($request) use ($params) {
            $output = $this->routeAction->invoke(request: $request, params: $params);
            return ResponseFactory::create(request: $request, response: $output);
        };
        /**
         * Create and process the dispatcher.
         */
        $dispatcher = new Relay(queue: $middlewares, resolver: function ($name) {
            if (! isset($this->middlewareResolver)) {
                return $name;
            }
            return $this->middlewareResolver->resolve(name: $name);
        });

        return $dispatcher->handle(request: $request);
    }

    public function gatherMiddlewares(): array
    {
        return array_merge([], $this->middlewares, $this->routeAction->getMiddlewares());
    }

    /**
     * @throws RouteNameRedefinedException
     */
    public function name(?string $name): Routable
    {
        if (isset($this->name)) {
            throw new RouteNameRedefinedException(message: 'Route name is already defined.');
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
                $this->setScheme(schemes: $scheme);
            }
        }
        $this->domain = trim(string: $domain, characters: '//');
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
                $this->setScheme(schemes: $scheme);
            }
        }
        $this->subDomain = trim(string: $subdomain, characters: '//');
        return $this;
    }

    public function namespace(?string $namespace): Routable
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function setScheme(string ...$schemes): self
    {
        foreach ($schemes as $scheme) {
            $this->schemes[] = $scheme;
        }
        return $this;
    }

    /**
     * @throws TypeException
     */
    public function where(): self
    {
        $args = func_get_args();
        if (count($args) === 0) {
            throw new TypeException();
        }
        if (is_array(value: $args[0])) {
            foreach ($args[0] as $key => $value) {
                $this->paramConstraints[$key] = $value;
            }
        } else {
            $this->paramConstraints[$args[0]] = $args[1];
        }
        return $this;
    }

    public function middleware(): Routable
    {
        $args = func_get_args();
        foreach ($args as $middleware) {
            if (is_array(value: $middleware)) {
                $this->middlewares += $middleware;
            } else {
                $this->middlewares[] = $middleware;
            }
        }
        return $this;
    }

    public function getDomain(): ?string
    {
        return str_replace(search: ['http://', 'https://'], replace: '', subject: (string) $this->domain);
    }

    public function getSubDomain(): ?string
    {
        return str_replace(search: ['http://', 'https://'], replace: '', subject: (string) $this->subDomain);
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
