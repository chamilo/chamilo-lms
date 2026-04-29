<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer\Normalizer;

use Chamilo\CoreBundle\Entity\Message;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MessageNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'MESSAGE_NORMALIZER_ALREADY_CALLED';

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var array<string, mixed> $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        if (isset($data['content']) && \is_string($data['content'])) {
            $data['content'] = \Security::remove_XSS($data['content'], STUDENT);
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Message;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Message::class => false];
    }

}