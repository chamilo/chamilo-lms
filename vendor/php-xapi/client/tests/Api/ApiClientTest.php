<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client\Tests\Api;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Xabbuh\XApi\Client\Request\HandlerInterface;
use Xabbuh\XApi\Common\Exception\NotFoundException;
use Xabbuh\XApi\Serializer\SerializerRegistry;
use Xabbuh\XApi\Serializer\Symfony\ActorSerializer;
use Xabbuh\XApi\Serializer\Symfony\DocumentDataSerializer;
use Xabbuh\XApi\Serializer\Symfony\StatementResultSerializer;
use Xabbuh\XApi\Serializer\Symfony\StatementSerializer;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
abstract class ApiClientTest extends TestCase
{
    /**
     * @var HandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestHandler;

    /**
     * @var SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $serializer;

    /**
     * @var SerializerRegistry
     */
    protected $serializerRegistry;

    protected function setUp(): void
    {
        $this->requestHandler = $this->getMockBuilder(HandlerInterface::class)->getMock();
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $this->serializerRegistry = $this->createSerializerRegistry();
    }

    protected function createSerializerRegistry()
    {
        $registry = new SerializerRegistry();
        $registry->setStatementSerializer(new StatementSerializer($this->serializer));
        $registry->setStatementResultSerializer(new StatementResultSerializer($this->serializer));
        $registry->setActorSerializer(new ActorSerializer($this->serializer));
        $registry->setDocumentDataSerializer(new DocumentDataSerializer($this->serializer));

        return $registry;
    }

    protected function validateSerializer(array $serializerMap)
    {
        $this
            ->serializer
            ->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(function ($data) use ($serializerMap) {
                foreach ($serializerMap as $entry) {
                    if ($data == $entry['data']) {
                        return $entry['result'];
                    }
                }

                return '';
            });
    }

    protected function validateRequest($method, $uri, array $urlParameters, $body = null)
    {
        $request = $this->getMockBuilder(RequestInterface::class)->getMock();
        $this
            ->requestHandler
            ->expects($this->once())
            ->method('createRequest')
            ->with($method, $uri, $urlParameters, $body)
            ->willReturn($request);

        return $request;
    }

    protected function validateRetrieveApiCall($method, $uri, array $urlParameters, $statusCode, $type, $transformedResult, array $serializerMap = array())
    {
        $rawResponse = 'the-server-response';
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->expects($this->any())->method('getStatusCode')->willReturn($statusCode);
        $response->expects($this->any())->method('getHeader')->with('Content-Type')->willReturn(array('application/json'));
        $response->expects($this->any())->method('getBody')->willReturn($rawResponse);
        $request = $this->validateRequest($method, $uri, $urlParameters);

        if (404 === $statusCode) {
            $this
                ->requestHandler
                ->expects($this->once())
                ->method('executeRequest')
                ->with($request)
                ->willThrowException(new NotFoundException('Not found'));
        } else {
            $this
                ->requestHandler
                ->expects($this->once())
                ->method('executeRequest')
                ->with($request)
                ->willReturn($response);
        }

        $this->validateSerializer($serializerMap);

        if ($statusCode < 400) {
            $this->serializer
                ->expects($this->once())
                ->method('deserialize')
                ->with($rawResponse, 'Xabbuh\XApi\Model\\'.$type, 'json')
                ->willReturn($transformedResult);
        }
    }

    protected function validateStoreApiCall($method, $uri, array $urlParameters, $statusCode, $rawResponse, $object, array $serializerMap = array())
    {
        $rawRequest = 'the-request-body';
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->expects($this->any())->method('getStatusCode')->willReturn($statusCode);
        $response->expects($this->any())->method('getBody')->willReturn($rawResponse);
        $request = $this->validateRequest($method, $uri, $urlParameters, $rawRequest);
        $this
            ->requestHandler
            ->expects($this->once())
            ->method('executeRequest')
            ->with($request, array($statusCode))
            ->willReturn($response);
        $serializerMap[] = array('data' => $object, 'result' => $rawRequest);
        $this->validateSerializer($serializerMap);
    }
}
