<?php

declare(strict_types=1);

namespace Qubus\Router\Http\Input;

use Qubus\Router\Interfaces\ItemInterface;

use function str_replace;
use function strtolower;
use function ucfirst;

class Item implements ItemInterface
{
    public $index;
    public $name;
    public $value;

    public function __construct(string $index, ?string $value = null)
    {
        $this->index = $index;
        $this->value = $value;
        // Make the name human friendly, by replace _ with space
        $this->name = ucfirst(str_replace('_', ' ', strtolower($this->index)));
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function setIndex(string $index): ItemInterface
    {
        $this->index = $index;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set input name
     *
     * @return static
     */
    public function setName(string $name): ItemInterface
    {
        $this->name = $name;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set input value
     *
     * @return static
     */
    public function setValue(string $value): ItemInterface
    {
        $this->value = $value;
        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
