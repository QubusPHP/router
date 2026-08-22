<?php

declare(strict_types=1);

namespace Qubus\Tests\Routing;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Tests\Fixtures\ResponsableObject;

class ResponseFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new ServerRequest([], [], '/test/123', 'GET');
    }

    /** @test */
    public function testWhenPassedaResponseInstanceTheSameObjectIsReturned()
    {
        $response = new TextResponse('Testing', 200);

        Assert::assertSame($response, ResponseFactory::create($this->request, $response));
    }

    /** @test */
    public function testWhenPassedaNonResponseInstanceaResponseObjectIsReturned()
    {
        $response = ResponseFactory::create($this->request, 'Testing');

        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertSame('Testing', $response->getBody()->getContents());
    }

    /** @test */
    public function testWhenNothingIsPassedAnEmptyResponseObjectIsReturned()
    {
        $response = ResponseFactory::create($this->request, '');

        Assert::assertInstanceOf(EmptyResponse::class, $response);
    }

    /** @test */
    public function testWhenaResponsableObjectIsPassedTheResponseObjectIsReturned()
    {
        $textResponse = new TextResponse('testing123');
        $object       = Mockery::mock(ResponsableObject::class);
        $object->shouldReceive('toResponse')->with($this->request)->once()->andReturn($textResponse);

        $response = ResponseFactory::create($this->request, $object);

        Assert::assertInstanceOf(TextResponse::class, $response);
        Assert::assertSame('testing123', $response->getBody()->getContents());
    }
}
