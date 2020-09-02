<?php

namespace Qubus\Router\Test;

use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Qubus\Router\Factories\ResponseFactory;
use Qubus\Router\Interfaces\ResponsableInterface;

class ResponseFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function setUp()
    {
        parent::setUp();

        $this->request = new ServerRequest([], [], '/test/123', 'GET');
    }

    /** @test */
    public function whenPassedaResponseInstanceTheSameObjectIsReturned()
    {
        $response = new TextResponse('Testing', 200);

        $this->assertSame($response, ResponseFactory::create($this->request, $response));
    }

    /** @test */
    public function whenPassedaNonResponseInstanceaResponseObjectIsReturned()
    {
        $response = ResponseFactory::create($this->request, 'Testing');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('Testing', $response->getBody()->getContents());
    }

    /** @test */
    public function whenNothingIsPassedAnEmptyResponseObjectIsReturned()
    {
        $response = ResponseFactory::create($this->request, '');

        $this->assertInstanceOf(EmptyResponse::class, $response);
    }

    /** @test */
    public function whenaResponsableObjectIsPassedTheResponseObjectIsReturned()
    {
        $textResponse = new TextResponse('testing123');
        $object       = Mockery::mock(ResponsableObject::class);
        $object->shouldReceive('toResponse')->with($this->request)->once()->andReturn($textResponse);

        $response = ResponseFactory::create($this->request, $object);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertSame('testing123', $response->getBody()->getContents());
    }
}

class ResponsableObject implements ResponsableInterface
{
    public function toResponse(RequestInterface $request): ResponseInterface
    {
        return new TextResponse('testing123');
    }
}
