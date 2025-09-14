<?php

declare(strict_types=1);

namespace Qubus\Routing\Tests\Fixtures;

class RouteMixin
{
    public function testFunctionAddedByMixin(): \Closure
    {
        return function () {
            return 'abc123';
        };
    }
}
