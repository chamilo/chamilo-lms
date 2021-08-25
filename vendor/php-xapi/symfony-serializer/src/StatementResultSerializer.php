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
use Xabbuh\XApi\Model\StatementResult;
use Xabbuh\XApi\Serializer\StatementResultSerializerInterface;

/**
 * Serializes and deserializes {@link StatementResult statement results} using the Symfony Serializer component.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementResultSerializer implements StatementResultSerializerInterface
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
    public function serializeStatementResult(StatementResult $statementResult)
    {
        return $this->serializer->serialize($statementResult, 'json');
    }

    /**
     * {@inheritDoc}
     */
    public function deserializeStatementResult($data, array $attachments = array())
    {
        return $this->serializer->deserialize(
            $data,
            'Xabbuh\XApi\Model\StatementResult',
            'json',
            array(
                'xapi_attachments' => $attachments,
            )
        );
    }
}
