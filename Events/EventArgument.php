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

namespace Qubus\Routing\Events;

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
    public function getRequest(): Request;

    /**
     * Get all event arguments.
     */
    public function getArguments(): array;
}
