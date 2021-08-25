<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Serializer\Symfony\Normalizer;

use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\StatementResult;

/**
 * Normalizes and denormalizes xAPI statement collections.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class StatementResultNormalizer extends Normalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!$object instanceof StatementResult) {
            return null;
        }

        $data = array(
            'statements' => array(),
        );

        foreach ($object->getStatements() as $statement) {
            $data['statements'][] = $this->normalizeAttribute($statement, $format, $context);
        }

        if (null !== $moreUrlPath = $object->getMoreUrlPath()) {
            $data['more'] = $moreUrlPath->getValue();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof StatementResult;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $statements = $this->denormalizeData($data['statements'], 'Xabbuh\XApi\Model\Statement[]', $format, $context);
        $moreUrlPath = null;

        if (isset($data['more'])) {
            $moreUrlPath = IRL::fromString($data['more']);
        }

        return new StatementResult($statements, $moreUrlPath);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return 'Xabbuh\XApi\Model\StatementResult' === $type;
    }
}
