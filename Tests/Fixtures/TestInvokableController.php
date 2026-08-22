<?php

declare(strict_types=1);

namespace Qubus\Routing\Tests\Fixtures;

final class TestInvokableController
{
    public function __invoke(): string
    {
        return 'Invoked';
    }
}
