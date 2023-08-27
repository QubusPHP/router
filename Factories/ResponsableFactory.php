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

namespace Qubus\Routing\Factories;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Qubus\Routing\Interfaces\Responsable;

interface ResponsableFactory
{
    public static function create(
        RequestInterface $request,
        string|ResponseInterface|StreamInterface|Responsable|null $response = '',
    ): ResponseInterface;
}
