<?php

namespace spec\Xabbuh\XApi\Serializer;

use PhpSpec\ObjectBehavior;

abstract class SerializerFactoryInterfaceSpec extends ObjectBehavior
{
    function it_creates_a_statement_serializer()
    {
        $this->createStatementSerializer()->shouldHaveType('Xabbuh\XApi\Serializer\StatementSerializerInterface');
    }

    function it_creates_a_statement_result_serializer()
    {
        $this->createStatementResultSerializer()->shouldHaveType('Xabbuh\XApi\Serializer\StatementResultSerializerInterface');
    }

    function it_creates_an_actor_serializer()
    {
        $this->createActorSerializer()->shouldHaveType('Xabbuh\XApi\Serializer\ActorSerializerInterface');
    }

    function it_creates_a_document_data_serializer()
    {
        $this->createDocumentDataSerializer()->shouldHaveType('Xabbuh\XApi\Serializer\DocumentDataSerializerInterface');
    }
}
