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

use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\Object as LegacyStatementObject;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\StatementObject;
use Xabbuh\XApi\Model\StatementReference;
use Xabbuh\XApi\Model\SubStatement;

/**
 * Normalizes and denormalizes xAPI statement objects.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class ObjectNormalizer extends Normalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if ($object instanceof Activity) {
            $activityData = array(
                'objectType' => 'Activity',
                'id' => $object->getId()->getValue(),
            );

            if (null !== $definition = $object->getDefinition()) {
                $activityData['definition'] = $this->normalizeAttribute($definition, $format, $context);
            }

            return $activityData;
        }

        if ($object instanceof StatementReference) {
            return array(
                'objectType' => 'StatementRef',
                'id' => $object->getStatementId()->getValue(),
            );
        }

        if ($object instanceof SubStatement) {
            $data = array(
                'objectType' => 'SubStatement',
                'actor' => $this->normalizeAttribute($object->getActor(), $format, $context),
                'verb' => $this->normalizeAttribute($object->getVerb(), $format, $context),
                'object' => $this->normalizeAttribute($object->getObject(), $format, $context),
            );

            if (null !== $result = $object->getResult()) {
                $data['result'] = $this->normalizeAttribute($result, $format, $context);
            }

            if (null !== $statementContext = $object->getContext()) {
                $data['context'] = $this->normalizeAttribute($statementContext, $format, $context);
            }

            return $data;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof LegacyStatementObject || $data instanceof StatementObject;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (!isset($data['objectType']) || 'Activity' === $data['objectType']) {
            return $this->denormalizeActivity($data, $format, $context);
        }

        if (isset($data['objectType']) && ('Agent' === $data['objectType'] || 'Group' === $data['objectType'])) {
            return $this->denormalizeData($data, 'Xabbuh\XApi\Model\Actor', $format, $context);
        }

        if (isset($data['objectType']) && 'SubStatement' === $data['objectType']) {
            return $this->denormalizeSubStatement($data, $format, $context);
        }

        if (isset($data['objectType']) && 'StatementRef' === $data['objectType']) {
            return new StatementReference(StatementId::fromString($data['id']));
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return in_array($type, array(Activity::class, LegacyStatementObject::class, StatementObject::class, StatementReference::class, SubStatement::class), true);
    }

    private function denormalizeActivity(array $data, $format = null, array $context = array())
    {
        $definition = null;

        if (isset($data['definition'])) {
            $definition = $this->denormalizeData($data['definition'], 'Xabbuh\XApi\Model\Definition', $format, $context);
        }

        return new Activity(IRI::fromString($data['id']), $definition);
    }

    private function denormalizeSubStatement(array  $data, $format = null, array $context = array())
    {
        $actor = $this->denormalizeData($data['actor'], 'Xabbuh\XApi\Model\Actor', $format, $context);
        $verb = $this->denormalizeData($data['verb'], 'Xabbuh\XApi\Model\Verb', $format, $context);

        if (class_exists(StatementObject::class)) {
            $object = $this->denormalizeData($data['object'], StatementObject::class, $format, $context);
        } else {
            $object = $this->denormalizeData($data['object'], LegacyStatementObject::class, $format, $context);
        }

        $result = null;
        $statementContext = null;

        if (isset($data['result'])) {
            $result = $this->denormalizeData($data['result'], 'Xabbuh\XApi\Model\Result', $format, $context);
        }

        if (isset($data['context'])) {
            $statementContext = $this->denormalizeData($data['context'], 'Xabbuh\XApi\Model\Context', $format, $context);
        }

        return new SubStatement($actor, $verb, $object, $result, $statementContext);
    }
}
