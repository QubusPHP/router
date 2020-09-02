<?php

namespace Qubus\Router;

use Qubus\Router\Interfaces\ControllerMiddlewareInterface;
use Qubus\Router\Traits\ControllerMiddlewareTrait;

abstract class Controller implements ControllerMiddlewareInterface
{
    use ControllerMiddlewareTrait;
}
