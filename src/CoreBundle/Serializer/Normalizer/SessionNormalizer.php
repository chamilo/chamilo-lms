<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer\Normalizer;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\UserHelper;
use LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SessionNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public function __construct(
        private readonly UserHelper $userHelper,
    ) {}

    private const ALREADY_CALLED = 'SESSION_NORMALIZER_ALREADY_CALLED';

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        \assert($object instanceof Session);

        try {
            $object->getAccessVisibility();
        } catch (LogicException) {
            $object->setAccessVisibilityByUser(
                $this->userHelper->getCurrent()
            );
        }

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Session;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Session::class => false];
    }
}
