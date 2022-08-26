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

use Qubus\Exception\Exception;
use Qubus\Routing\Exceptions\NamedRouteNotFoundException;
use Qubus\Routing\Interfaces\Collector;
use RuntimeException;
use Traversable;

use function array_merge;
use function call_user_func_array;
use function is_array;
use function is_numeric;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function strcmp;
use function stripos;
use function strlen;
use function strncmp;
use function strpos;
use function substr;

use const PREG_SET_ORDER;

class RouteCollector implements Collector
{
    /** @var array Array of all routes (incl. named routes). */
    protected array $routes = [];
    /** @var array Array of all named routes. */
    protected array $namedRoutes = [];
    /** @var string domain */
    protected string $domain;
    /** @var string subdomain */
    protected string $subdomain;
    /**
     * @var string Can be used to ignore leading part of the Request
     * URL (if main file lives in subdirectory of host)
     */
    protected string $basePath = '';
    /** @var array Array of default match types (regex helpers) */
    protected array $matchTypes = [
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.+?',
        '**' => '.++',
        ''   => '[^/\.]++',
    ];

    /**
     * Create router.
     *
     * @param string $basePath Set the basepath.
     * @param array  $matchTypes Set regexes.
     * @throws RuntimeException
     */
    public function __construct(array $routes = [], string $basePath = '', array $matchTypes = [])
    {
        $this->addRoutes($routes);
        $this->setBasePath($basePath);
        $this->addMatchTypes($matchTypes);
    }

    /**
     * Retrieves all routes.
     *
     * Useful if you want to process or display routes.
     *
     * @return array All routes.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Add multiple routes at once from array using the following format:
     *
     *   $routes = [
     *      [$method, $route, $target, $name]
     *   ];
     *
     * @throws RuntimeException
     */
    public function addRoutes(array $routes): void
    {
        if (! is_array(value: $routes) && ! $routes instanceof Traversable) {
            throw new RuntimeException(message: 'Routes should be an array or an instance of Traversable');
        }
        foreach ($routes as $route) {
            call_user_func_array(callback: [$this, 'map'], args: $route);
        }
    }

    /**
     * Set the base path.
     *
     * Useful if you are running your application from a subdirectory.
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    /**
     * Set the domain.
     *
     * Useful for api routing.
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * Add named match types. It uses array_merge so keys can be overwritten.
     *
     * @param array $matchTypes The key is the name and the value is the regex.
     */
    public function addMatchTypes(array $matchTypes): void
    {
        $this->matchTypes = array_merge($this->matchTypes, $matchTypes);
    }

    /**
     * Map a route to a target
     *
     * @param string      $method One of 5 HTTP Methods, or a pipe-separated list
     *                            of multiple HTTP Methods (GET|POST|PATCH|PUT|DELETE)
     * @param ?string     $subdomain Domain of route.
     * @param string      $route The route regex, custom regex must start with an @.
     *                            You can use multiple pre-set regex filters, like [i:id]
     * @param mixed       $target The target where this route should point to. Can be anything.
     * @param null|string $name Optional name of this route. Supply if you want to
     *                     reverse route this url in your application.
     * @throws RuntimeException
     */
    public function map(string $method, ?string $subdomain, string $route, mixed $target, ?string $name = null)
    {
        $this->subdomain = $subdomain;

        $this->routes[] = [$method, $subdomain, $route, $target, $name];
        if ($name) {
            if (isset($this->namedRoutes[$name])) {
                throw new RuntimeException(message: "Can not redeclare route '{$name}'");
            }
            $this->namedRoutes[$name] = $route;
        }
        return;
    }

    /**
     * Reversed routing
     *
     * Generate the URL for a named route. Replace regexes with supplied parameters
     *
     * @param string $routeName The name of the route.
     * @param array  $params     Associative array of parameters to replace placeholders with.
     * @return string The URL of the route with named parameters in place.
     * @throws NamedRouteNotFoundException
     */
    public function generateUri(string $routeName, array $params = []): string
    {
        if (null !== $this->domain) {
            $domain = $this->domain;
        } elseif (null !== $this->subdomain) {
            $domain = $this->subdomain;
        } else {
            $domain = '';
        }
        // Check if named route exists
        if (! isset($this->namedRoutes[$routeName])) {
            throw new NamedRouteNotFoundException(message: "Route '{$routeName}' does not exist.");
        }
        // Replace named parameters
        $route = $this->namedRoutes[$routeName];
        // prepend base path to route url again
        $url = $this->basePath . $route;
        /**
         * Prepend domain/subdomain if set.
         */
        if (null !== $domain) {
            $url = $domain . $url;
        }

        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $index => $match) {
                [$block, $pre, $type, $param, $optional] = $match;

                if ($pre) {
                    $block = substr(string: $block, offset: 1);
                }

                if (isset($params[$param])) {
                    // Part is found, replace for param value
                    $url = str_replace(search: $block, replace: $params[$param], subject: $url);
                } elseif ($optional && $index !== 0) {
                    // Only strip preceding slash if it's not at the base
                    $url = str_replace(search: $pre . $block, replace: '', subject: $url);
                } else {
                    // Strip match block
                    $url = str_replace(search: $block, replace: '', subject: $url);
                }
            }
        }
        return $url;
    }

    /**
     * Match a given Request Url against stored routes
     *
     * @return array|bool Array with route information on success, false on failure (no match).
     * @throws Exception
     */
    public function match(?string $requestHost = null, ?string $requestUrl = null, ?string $requestMethod = null): array|bool
    {
        $params = [];
        /**
         * Set Request Host if it isn't passed as parameter.
         */
        if (null !== $this->domain && null === $requestHost) {
            if (! isset($_SERVER['HTTP_HOST'])) {
                throw new Exception(message: 'Subdomain matching active but no host is specified.');
            }
            $requestHost = $_SERVER['HTTP_HOST'];
        }

        /**
         * Set Request Url if it isn't passed as parameter.
         */
        if (null === $requestUrl) {
            $requestUrl = $_SERVER['REQUEST_URI'] ?? '/';
        }
        /**
         * Strip base path from request url.
         */
        $requestUrl = substr(string: $requestUrl, offset: strlen(string: $this->basePath));
        /**
         * Strip query string (?a=b) from Request Url.
         */
        if (($strpos = strpos(haystack: $requestUrl, needle: '?')) !== false) {
            $requestUrl = substr(string: $requestUrl, offset: 0, length: $strpos);
        }

        $lastRequestUrlChar = $requestUrl ? $requestUrl[strlen(string: $requestUrl) - 1] : '';
        /**
         * Set Request Method if it isn't passed as a parameter
         */
        if (null === $requestMethod) {
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }
        foreach ($this->routes as $handler) {
            [$methods, $subdomain, $route, $target, $name] = $handler;
            /**
             * Check if request domain matches. If not, abandon early. (CHEAPER).
             */
            if ($this->domain != null && $subdomain != null && $requestHost != $subdomain . '.' . $this->domain) {
                continue;
            }

            $method_match = stripos(haystack: $methods, needle: $requestMethod) !== false;
            /**
             * Method did not match, continue to next route.
             */
            if (! $method_match) {
                continue;
            }
            /**
             * '*' wildcard (matches all)
             *
             * @var string
             */
            if ($route === '*') {
                $match = true;
            } elseif (isset($route[0]) && $route[0] === '@') {
                /**
                 * @ regex delimiter
                 *
                 * @var string
                 */
                $pattern = '`' . substr(string: $route, offset: 1) . '`u';
                $match   = preg_match($pattern, $requestUrl, $params) === 1;
            } elseif (($position = strpos(haystack: $route, needle: '[')) === false) {
                /**
                 * No params in url, do string comparison.
                 */
                $match = strcmp(string1: $requestUrl, string2: $route) === 0;
            } else {
                /**
                 * Compare longest non-param string with url before moving on to
                 * regex.
                 *
                 * Check if last character before param is a slash, because it
                 * could be optional if param is optional too
                 * (see https://github.com/dannyvankooten/AltoRouter/issues/241).
                 */
                if (strncmp(string1: $requestUrl, string2: $route, length: $position) !== 0 && ($lastRequestUrlChar === '/' || $route[$position - 1] !== '/')) {
                    continue;
                }

                $regex = $this->compileRoute(route: $route);
                $match = preg_match($regex, $requestUrl, $params) === 1;
            }

            if ($match) {
                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric(value: $key)) {
                            unset($params[$key]);
                        }
                    }
                }

                return [
                    'target' => $target,
                    'params' => $params,
                    'name'   => $name,
                ];
            }
        }
        return false;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Adds a path at the beginning of a url.
     *
     * Useful if you are running your application from a subdirectory.
     */
    public function prependUrl(string $basePath): void
    {
        $this->setBasePath($basePath);
    }

    /**
     * Compile the regex for a given route.
     *
     * @param $route
     */
    protected function compileRoute($route): string
    {
        if (preg_match_all('`(/|\.|)\[([^:\]]*+)(?::([^:\]]*+))?\](\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                [$block, $pre, $type, $param, $optional] = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                $optional = $optional !== '' ? '?' : null;

                /**
                 * Older versions of PCRE require the 'P' in (?P<named>).
                 *
                 * @var string
                 */
                $pattern = '(?:'
                    . ($pre !== '' ? $pre : null)
                    . '('
                    . ($param !== '' ? "?P<$param>" : null)
                    . $type
                    . ')'
                    . $optional
                    . ')'
                    . $optional;

                $route = str_replace(search: $block, replace: $pattern, subject: $route);
            }
        }
        return "`^$route$`u";
    }
}
