<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

use Qubus\Exception\Exception;
use Qubus\Router\Http\Request;

interface ExceptionHandlerInterface
{
    public function handleError(Request $request, Exception $error): void;
}
