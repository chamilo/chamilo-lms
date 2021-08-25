<?php

namespace Xabbuh\XApi\Serializer\Symfony\Normalizer;

use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\Verb;

/**
 * Denormalizes PHP arrays to {@link Verb} objects.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class VerbNormalizer extends Normalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!$object instanceof Verb) {
            return;
        }

        $data = array(
            'id' => $object->getId()->getValue(),
        );

        if (null !== $display = $object->getDisplay()) {
            $data['display'] = $this->normalizeAttribute($display, $format, $context);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Verb;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $id = IRI::fromString($data['id']);
        $display = null;

        if (isset($data['display'])) {
            $display = $this->denormalizeData($data['display'], 'Xabbuh\XApi\Model\LanguageMap', $format, $context);
        }

        return new Verb($id, $display);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return 'Xabbuh\XApi\Model\Verb' === $type;
    }
}
