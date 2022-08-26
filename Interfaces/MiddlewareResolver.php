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

namespace Qubus\Routing\Interfaces;

interface MiddlewareResolver
{
    /**
     * Resolves a middleware
     *
     * @param  mixed $name The key to look up a middleware
     */
    public function resolve(mixed $name): mixed;
}
