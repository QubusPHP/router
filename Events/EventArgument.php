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
use Qubus\Http\Request;
use Qubus\Routing\Router;

interface EventArgument
{
    /**
     * Get event name.
     */
    public function getEventName(): string;

    /**
     * Set event name.
     */
    public function setEventName(string $name): void;

    /**
     * Get router instance.
     */
    public function getRouter(): Router;

    /**
     * Get request instance.
     */
    public function getRequest(): Request|RequestInterface;

    /**
     * Get all event arguments.
     */
    public function getArguments(): array;
}
