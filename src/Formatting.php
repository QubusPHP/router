<?php

namespace Qubus\Router;

use function ltrim;
use function rtrim;

final class Formatting
{
    public static function removeTrailingSlash($input)
    {
        return rtrim($input, '/\\');
    }

    public static function addTrailingSlash($input)
    {
        return static::removeTrailingSlash($input) . '/';
    }

    public static function removeLeadingSlash($input)
    {
        return ltrim($input, '/\\');
    }

    public static function addLeadingSlash($input)
    {
        return '/' . static::removeLeadingSlash($input);
    }
}
