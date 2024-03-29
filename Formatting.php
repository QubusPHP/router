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

namespace Qubus\Routing;

use function ltrim;
use function rtrim;

final class Formatting
{
    public static function removeTrailingSlash(string $input): string
    {
        return rtrim(string: $input, characters: '/\\');
    }

    public static function addTrailingSlash(string $input): string
    {
        return self::removeTrailingSlash(input: $input) . '/';
    }

    public static function removeLeadingSlash(string $input): string
    {
        return ltrim(string: $input, characters: '/\\');
    }

    public static function addLeadingSlash(string $input): string
    {
        return '/' . self::removeLeadingSlash(input: $input);
    }
}
