<?php

namespace spec\Xabbuh\XApi\Serializer;

use PhpSpec\ObjectBehavior;
use Xabbuh\XApi\Serializer\ActorSerializerInterface;
use Xabbuh\XApi\Serializer\DocumentDataSerializerInterface;
use Xabbuh\XApi\Serializer\StatementResultSerializerInterface;
use Xabbuh\XApi\Serializer\StatementSerializerInterface;

class SerializerRegistrySpec extends ObjectBehavior
{
    function it_is_a_serializer_registry()
    {
        $this->shouldHaveType('Xabbuh\XApi\Serializer\SerializerRegistryInterface');
    }

    function it_stores_a_statement_serializer_for_later_retrieval(StatementSerializerInterface $statementSerializer)
    {
        $this->setStatementSerializer($statementSerializer);
        $this->getStatementSerializer()->shouldReturn($statementSerializer);
    }

    function it_stores_a_statement_result_serializer_for_later_retrieval(StatementResultSerializerInterface $statementResultSerializer)
    {
        $this->setStatementResultSerializer($statementResultSerializer);
        $this->getStatementResultSerializer()->shouldReturn($statementResultSerializer);
    }

    function it_stores_an_actor_serializer_for_later_retrieval(ActorSerializerInterface $actorSerializer)
    {
        $this->setActorSerializer($actorSerializer);
        $this->getActorSerializer()->shouldReturn($actorSerializer);
    }

    function it_stores_a_document_data_serializer_for_later_retrieval(DocumentDataSerializerInterface $documentDataSerializer)
    {
        $this->setDocumentDataSerializer($documentDataSerializer);
        $this->getDocumentDataSerializer()->shouldReturn($documentDataSerializer);
    }
}
