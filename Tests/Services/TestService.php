<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing\Services;

class TestService
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
