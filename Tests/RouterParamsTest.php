<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Routing\Route\RouteParams;

class RouterParamsTest extends TestCase
{
    /** @test */
    public function canGetParamByKey()
    {
        $params = new RouteParams(['key' => 'value']);

        Assert::assertSame('value', $params->key);
    }

    /** @test */
    public function canIterateAllKeysAndValues()
    {
        $params = new RouteParams([
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ]);

        $keys   = [];
        $values = [];

        foreach ($params as $key => $value) {
            $keys[]   = $key;
            $values[] = $value;
        }

        Assert::assertSame(['key1', 'key2', 'key3'], $keys);
        Assert::assertSame(['value1', 'value2', 'value3'], $values);
    }

    /** @test */
    public function returnNullWhenaKeyIsNotFound()
    {
        $params = new RouteParams(['key' => 'value']);

        Assert::assertNull($params->invalid);
    }

    /** @test */
    public function canGetParamsAsArray()
    {
        $data   = ['key1' => 'value1', 'key2' => 'value2'];
        $params = new RouteParams($data);

        Assert::assertSame($data, $params->toArray());
    }
}
