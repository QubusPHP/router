<?php

/**
 * Qubus\Routing
 *
 * @link       https://github.com/QubusPHP/routing
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Routing\Helpers;

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
 * @return Handler|array|string|null
 */
function input($index = null, $defaultValue = null, ...$methods)
{
    if ($index !== null) {
        return request()->handler()->value($index, $defaultValue, ...$methods);
    }

    return request()->handler();
}

/**
 * @param string $url
 */
function redirect($url, ?int $code = 302)
{
    return RedirectResponseFactory::create($url, $code);
}
