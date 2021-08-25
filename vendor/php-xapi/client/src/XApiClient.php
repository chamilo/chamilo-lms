<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Client;

use Xabbuh\XApi\Client\Api\ActivityProfileApiClient;
use Xabbuh\XApi\Client\Api\AgentProfileApiClient;
use Xabbuh\XApi\Client\Api\ApiClient;
use Xabbuh\XApi\Client\Api\StateApiClient;
use Xabbuh\XApi\Client\Api\StatementsApiClient;
use Xabbuh\XApi\Client\Request\HandlerInterface;
use Xabbuh\XApi\Serializer\SerializerRegistryInterface;

/**
 * An Experience API client.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class XApiClient implements XApiClientInterface
{
    /**
     * @var SerializerRegistryInterface
     */
    private $serializerRegistry;

    /**
     * @param HandlerInterface            $requestHandler     The HTTP request handler
     * @param SerializerRegistryInterface $serializerRegistry The serializer registry
     * @param string                      $version            The xAPI version
     */
    public function __construct(HandlerInterface $requestHandler, SerializerRegistryInterface $serializerRegistry, $version)
    {
        $this->requestHandler = $requestHandler;
        $this->serializerRegistry = $serializerRegistry;
        $this->version = $version;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatementsApiClient()
    {
        return new StatementsApiClient(
            $this->requestHandler,
            $this->version,
            $this->serializerRegistry->getStatementSerializer(),
            $this->serializerRegistry->getStatementResultSerializer(),
            $this->serializerRegistry->getActorSerializer()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getStateApiClient()
    {
        return new StateApiClient(
            $this->requestHandler,
            $this->version,
            $this->serializerRegistry->getDocumentDataSerializer(),
            $this->serializerRegistry->getActorSerializer()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getActivityProfileApiClient()
    {
        return new ActivityProfileApiClient(
            $this->requestHandler,
            $this->version,
            $this->serializerRegistry->getDocumentDataSerializer()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAgentProfileApiClient()
    {
        return new AgentProfileApiClient(
            $this->requestHandler,
            $this->version,
            $this->serializerRegistry->getDocumentDataSerializer(),
            $this->serializerRegistry->getActorSerializer()
        );
    }
}
