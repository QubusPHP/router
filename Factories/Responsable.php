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

namespace Qubus\Routing\Factories;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Responsable
{
    public function toResponse(RequestInterface $request): ResponseInterface;
}
