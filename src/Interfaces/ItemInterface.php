<?php

declare(strict_types=1);

namespace Qubus\Router\Interfaces;

interface ItemInterface
{
    public function getIndex(): string;

    public function setIndex(string $index): self;

    public function getName(): ?string;

    public function setName(string $name): self;

    public function getValue(): ?string;

    public function setValue(string $value): self;

    public function __toString(): string;
}
