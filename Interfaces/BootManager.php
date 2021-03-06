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

namespace Qubus\Routing\Interfaces;

use Psr\Http\Message\RequestInterface;
use Qubus\Routing\Router;

interface BootManager
{
    /**
     * Called when router loads it's routes
     */
    public function boot(Router $router, RequestInterface $request): void;
}
