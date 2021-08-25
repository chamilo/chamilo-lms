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
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Serializer\StatementSerializerInterface;

/**
 * Serializes and deserializes {@link Statement statements} using the Symfony Serializer component.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementSerializer implements StatementSerializerInterface
{
    /**
     * @var SerializerInterface The underlying serializer
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritDoc}
     */
    public function serializeStatement(Statement $statement)
    {
        return $this->serializer->serialize($statement, 'json');
    }

    /**
     * {@inheritDoc}
     */
    public function serializeStatements(array $statements)
    {
        return $this->serializer->serialize($statements, 'json');
    }

    /**
     * {@inheritDoc}
     */
    public function deserializeStatement($data, array $attachments = array())
    {
        return $this->serializer->deserialize(
            $data,
            'Xabbuh\XApi\Model\Statement',
            'json',
            array(
                'xapi_attachments' => $attachments,
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function deserializeStatements($data, array $attachments = array())
    {
        return $this->serializer->deserialize(
            $data,
            'Xabbuh\XApi\Model\Statement[]',
            'json',
            array(
                'xapi_attachments' => $attachments,
            )
        );
    }
}
