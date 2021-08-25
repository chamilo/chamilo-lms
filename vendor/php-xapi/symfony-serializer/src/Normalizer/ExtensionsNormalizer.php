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
use Xabbuh\XApi\Model\Extensions;
use Xabbuh\XApi\Model\IRI;

/**
 * Normalizes and denormalizes xAPI extensions.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class ExtensionsNormalizer implements DenormalizerInterface, NormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!$object instanceof Extensions) {
            return;
        }

        $extensions = $object->getExtensions();

        if (count($extensions) === 0) {
            return new \stdClass();
        }

        $data = array();

        foreach ($extensions as $iri) {
            $data[$iri->getValue()] = $extensions[$iri];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
       return $data instanceof Extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $extensions = new \SplObjectStorage();

        foreach ($data as $iri => $value) {
            $extensions->attach(IRI::fromString($iri), $value);
        }

        return new Extensions($extensions);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return 'Xabbuh\XApi\Model\Extensions' === $type;
    }
}
