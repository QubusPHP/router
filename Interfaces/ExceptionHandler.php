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

use Qubus\Exception\Exception;
use Qubus\Http\Request;

interface ExceptionHandler
{
    public function handleError(Request $request, Exception $error): void;
}
