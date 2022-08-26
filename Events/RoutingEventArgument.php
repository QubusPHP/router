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

namespace Qubus\Routing\Events;

use Qubus\Exception\Data\TypeException;
use Qubus\Http\Request;
use Qubus\Routing\Router;

use function array_key_exists;

class RoutingEventArgument implements EventArgument
{
    /**
     * Event name
     */
    protected string $eventName;

    protected Router $router;

    /** @var array $arguments */
    protected array $arguments = [];

    public function __construct(string $eventName, Router $router, array $arguments = [])
    {
        $this->eventName = $eventName;
        $this->router    = $router;
        $this->arguments = $arguments;
    }

    /**
     * Get event name
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * Set the event name
     */
    public function setEventName(string $name): void
    {
        $this->eventName = $name;
    }

    /**
     * Get the router instance
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the request instance
     */
    public function getRequest(): Request
    {
        return $this->getRouter()->getRequest();
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

    /**
     * Get arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
