<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/routing
 * @copyright  2020
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @author     Joshua Parker <josh@joshuaparker.blog>
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Helpers;

use Psr\Http\Message\ResponseInterface;
use Qubus\Http\Factories\RedirectResponseFactory;
use Qubus\Http\Input\Handler;
use Qubus\Http\Request;
use Qubus\Http\Response;

function response(): Response
{
    return new Response();
}

function request(): Request
{
    return new Request();
}

/**
 * Get inputs.
 *
 * @param string|null $index        Parameter index name.
 * @param string|null $defaultValue Default return value.
 * @param array       ...$methods   Default methods.
 */
function input(?string $index = null, ?string $defaultValue = null, ...$methods): Handler|array|string|null
{
    if ($index !== null) {
        return request()->handler()->value($index, $defaultValue, ...$methods);
    }

    return request()->handler();
}

function redirect(string $url, ?int $code = 302): ResponseInterface
{
    return RedirectResponseFactory::create($url, $code);
}
