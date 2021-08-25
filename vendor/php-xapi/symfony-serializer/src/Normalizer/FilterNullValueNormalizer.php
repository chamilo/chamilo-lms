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

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Normalizer wrapping Symfony's PropertyNormalizer to filter null values.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class FilterNullValueNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    private $normalizer;

    public function __construct()
    {
        $this->normalizer = new PropertyNormalizer();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $data = $this->normalizer->normalize($object, $format, $context);
        $filteredData = new \ArrayObject();

        foreach ($data as $key => $value) {
            if (null !== $value) {
                $filteredData[$key] = $value;
            }
        }

        return $filteredData;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->normalizer->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->normalizer->setSerializer($serializer);
    }
}
