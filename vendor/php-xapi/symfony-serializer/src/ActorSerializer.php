<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer\Symfony;

use Symfony\Component\Serializer\SerializerInterface;
use Xabbuh\XApi\Model\Actor;
use Xabbuh\XApi\Serializer\ActorSerializerInterface;

/**
 * Serializes and deserializes {@link Actor actors} using the Symfony Serializer component.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class ActorSerializer implements ActorSerializerInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeActor(Actor $actor)
    {
        return $this->serializer->serialize($actor, 'json');
    }

    /**
     * {@inheritDoc}
     */
    public function deserializeActor($data)
    {
        return $this->serializer->deserialize($data, 'Xabbuh\XApi\Model\Actor', 'json');
    }
}
