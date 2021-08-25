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

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Xabbuh\XApi\Model\Interaction\InteractionComponent;

/**
 * Denormalizes xAPI statement activity {@link InteractionComponent interaction components}.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class InteractionComponentNormalizer extends Normalizer implements DenormalizerInterface, NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!$object instanceof InteractionComponent) {
            return;
        }

        $data = array(
            'id' => $object->getId(),
        );

        if (null !== $description = $object->getDescription()) {
            $data['description'] = $this->normalizeAttribute($description, $format, $context);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof InteractionComponent;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $description = null;

        if (isset($data['description'])) {
            $description = $this->denormalizeData($data['description'], 'Xabbuh\XApi\Model\LanguageMap', $format, $context);
        }

        return new InteractionComponent($data['id'], $description);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return 'Xabbuh\XApi\Model\Interaction\InteractionComponent' === $type;
    }
}
