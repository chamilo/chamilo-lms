<?php

namespace spec\Xabbuh\XApi\Client;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Client\Api\ActivityProfileApiClientInterface;
use Xabbuh\XApi\Client\Api\AgentProfileApiClientInterface;
use Xabbuh\XApi\Client\Api\StateApiClientInterface;
use Xabbuh\XApi\Client\Api\StatementsApiClientInterface;
use Xabbuh\XApi\Client\Request\HandlerInterface;
use Xabbuh\XApi\Serializer\ActorSerializerInterface;
use Xabbuh\XApi\Serializer\DocumentDataSerializerInterface;
use Xabbuh\XApi\Serializer\SerializerRegistryInterface;
use Xabbuh\XApi\Serializer\StatementResultSerializerInterface;
use Xabbuh\XApi\Serializer\StatementSerializerInterface;

class XApiClientSpec extends ObjectBehavior
{
    function let(
        HandlerInterface $requestHandler,
        SerializerRegistryInterface $serializerRegistry,
        ActorSerializerInterface $actorSerializer,
        DocumentDataSerializerInterface $documentDataSerializer,
        StatementSerializerInterface $statementSerializer,
        StatementResultSerializerInterface $statementResultSerializer
    ) {
        $serializerRegistry->getActorSerializer()->willReturn($actorSerializer);
        $serializerRegistry->getDocumentDataSerializer()->willReturn($documentDataSerializer);
        $serializerRegistry->getStatementSerializer()->willReturn($statementSerializer);
        $serializerRegistry->getStatementResultSerializer()->willReturn($statementResultSerializer);

        $this->beConstructedWith($requestHandler, $serializerRegistry, '1.0.1');
    }

    function it_returns_a_statements_api_client_instance()
    {
        $this->getStatementsApiClient()->shouldBeAnInstanceOf(StatementsApiClientInterface::class);
    }

    function it_returns_an_activity_profile_api_client_instance()
    {
        $this->getActivityProfileApiClient()->shouldBeAnInstanceOf(ActivityProfileApiClientInterface::class);
    }

    function it_returns_an_agent_profile_api_client_instance()
    {
        $this->getAgentProfileApiClient()->shouldBeAnInstanceOf(AgentProfileApiClientInterface::class);
    }

    function it_returns_a_state_api_client_instance()
    {
        $this->getStateApiClient()->shouldBeAnInstanceOf(StateApiClientInterface::class);
    }
}
