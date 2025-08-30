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

namespace Qubus\Routing\Events;

use Psr\Http\Message\RequestInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Http\Request;
use Qubus\Routing\Router;

use function array_key_exists;

class RoutingEventArgument implements EventArgument
{
    /**
     * Event name
     */
    public string $eventName {
        get => $this->eventName;
    }

    public Router $router {
        get => $this->router;
    }

    /** @var array $arguments */
    public array $arguments = [] {
        get => $this->arguments;
    }

    public function __construct(string $eventName, Router $router, array $arguments = [])
    {
        $this->eventName = $eventName;
        $this->router    = $router;
        $this->arguments = $arguments;
    }

    /**
     * Get the request instance.
     */
    public function getRequest(): Request|RequestInterface
    {
        return $this->router->request;
    }

    public function __get(string $name): mixed
    {
        return $this->arguments[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->arguments);
    }

    /**
     * @throws TypeException
     */
    public function __set(string $name, mixed $value)
    {
        throw new TypeException('Not supported');
    }
}
