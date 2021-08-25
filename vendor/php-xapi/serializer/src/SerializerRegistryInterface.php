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
interface SerializerRegistryInterface
{
    /**
     * Sets the {@link StatementSerializerInterface statement serializer}.
     *
     * @param StatementSerializerInterface $serializer The serializer
     */
    public function setStatementSerializer(StatementSerializerInterface $serializer);

    /**
     * Returns the {@link StatementSerializerInterface statement serializer}.
     *
     * @return StatementSerializerInterface|null The serializer
     */
    public function getStatementSerializer();

    /**
     * Sets the {@link StatementResultSerializerInterface statement result serializer}.
     *
     * @param StatementResultSerializerInterface $serializer The serializer
     */
    public function setStatementResultSerializer(StatementResultSerializerInterface $serializer);

    /**
     * Returns the {@link StatementResultSerializerInterface statement result serializer}.
     *
     * @return StatementResultSerializerInterface|null The serializer
     */
    public function getStatementResultSerializer();

    /**
     * Sets the {@link ActorSerializerInterface actor serializer}.
     *
     * @param ActorSerializerInterface $serializer The serializer
     */
    public function setActorSerializer(ActorSerializerInterface $serializer);

    /**
     * Returns the {@link ActorSerializerInterface actor serializer}.
     *
     * @return ActorSerializerInterface|null The serializer
     */
    public function getActorSerializer();

    /**
     * Sets the {@link DocumentDataSerializerInterface document data serializer}.
     *
     * @param DocumentDataSerializerInterface $serializer The serializer
     */
    public function setDocumentDataSerializer(DocumentDataSerializerInterface $serializer);

    /**
     * Returns the {@link DocumentDataSerializerInterface document data serializer}.
     *
     * @return DocumentDataSerializerInterface|null The serializer
     */
    public function getDocumentDataSerializer();
}
