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

namespace Qubus\Routing\Route;

use Iterator;

use function array_keys;
use function count;

class RouteParams implements Iterator
{
    protected int $position = 0;
    protected array $params   = [];

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function __get(mixed $key): mixed
    {
        if (! isset($this->params[$key])) {
            return null;
        }

        return $this->params[$key];
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->params[$this->key()];
    }

    public function key(): string|int
    {
        $keys = array_keys($this->params);
        return $keys[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function valid(): bool
    {
        return $this->position < count($this->params);
    }

    public function toArray(): array
    {
        return $this->params;
    }
}
