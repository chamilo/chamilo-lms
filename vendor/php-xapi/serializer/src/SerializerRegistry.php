<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer;

/**
 * Registry containing all the serializers.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class SerializerRegistry implements SerializerRegistryInterface
{
    /**
     * @var StatementSerializerInterface The statement serializer
     */
    private $statementSerializer;

    /**
     * @var StatementResultSerializerInterface The statement result serializer
     */
    private $statementResultSerializer;

    /**
     * @var ActorSerializerInterface The actor serializer
     */
    private $actorSerializer;

    /**
     * @var DocumentDataSerializerInterface The document data serializer
     */
    private $documentDataSerializer;

    /**
     * {@inheritDoc}
     */
    public function setStatementSerializer(StatementSerializerInterface $serializer)
    {
        $this->statementSerializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatementSerializer()
    {
        return $this->statementSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function setStatementResultSerializer(StatementResultSerializerInterface $serializer)
    {
        $this->statementResultSerializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatementResultSerializer()
    {
        return $this->statementResultSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function setActorSerializer(ActorSerializerInterface $serializer)
    {
        $this->actorSerializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function getActorSerializer()
    {
        return $this->actorSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function setDocumentDataSerializer(DocumentDataSerializerInterface $serializer)
    {
        $this->documentDataSerializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function getDocumentDataSerializer()
    {
        return $this->documentDataSerializer;
    }
}
