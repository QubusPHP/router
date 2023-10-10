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

namespace Qubus\Routing\Interfaces;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareResolver
{
    /**
     * Resolves a middleware
     *
     * @param mixed $name The key to look up a middleware
     * @return MiddlewareInterface|callable
     */
    public function resolve(mixed $name): MiddlewareInterface|callable;
}
