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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Qubus\Routing\Factories\ResponseFactory;
use Qubus\Routing\Interfaces\Responsable;

class ResponseFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = new ServerRequest([], [], '/test/123', 'GET');
    }

    /** @test */
    public function whenPassedaResponseInstanceTheSameObjectIsReturned()
    {
        $response = new TextResponse('Testing', 200);

        Assert::assertSame($response, ResponseFactory::create($this->request, $response));
    }

    /** @test */
    public function whenPassedaNonResponseInstanceaResponseObjectIsReturned()
    {
        $response = ResponseFactory::create($this->request, 'Testing');

        Assert::assertInstanceOf(ResponseInterface::class, $response);
        Assert::assertSame('Testing', $response->getBody()->getContents());
    }

    /** @test */
    public function whenNothingIsPassedAnEmptyResponseObjectIsReturned()
    {
        $response = ResponseFactory::create($this->request, '');

        Assert::assertInstanceOf(EmptyResponse::class, $response);
    }

    /** @test */
    public function whenaResponsableObjectIsPassedTheResponseObjectIsReturned()
    {
        $textResponse = new TextResponse('testing123');
        $object       = Mockery::mock(ResponsableObject::class);
        $object->shouldReceive('toResponse')->with($this->request)->once()->andReturn($textResponse);

        $response = ResponseFactory::create($this->request, $object);

        Assert::assertInstanceOf(TextResponse::class, $response);
        Assert::assertSame('testing123', $response->getBody()->getContents());
    }
}

class ResponsableObject implements Responsable
{
    public function toResponse(RequestInterface $request): ResponseInterface
    {
        return new TextResponse('testing123');
    }
}
