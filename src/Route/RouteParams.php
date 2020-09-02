<?php

declare(strict_types=1);

namespace Qubus\Router\Route;

use Iterator;

use function array_keys;
use function count;

class RouteParams implements Iterator
{
    protected $position = 0;
    protected $params   = [];

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function __get($key)
    {
        if (! isset($this->params[$key])) {
            return null;
        }

        return $this->params[$key];
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->params[$this->key()];
    }

    public function key()
    {
        $keys = array_keys($this->params);
        return $keys[$this->position];
    }

    public function next()
    {
        $this->position++;
    }

    public function valid()
    {
        return $this->position < count($this->params);
    }

    public function toArray()
    {
        return $this->params;
    }
}
