<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer\Normalizer;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
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

        $data = $this->normalizer->normalize($object, $format, $context);

        $data['accessVisibility'] = $this->getSessionAccessVisiblity($object);

        return $data;
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

    private function getSessionAccessVisiblity(Session $session): int
    {
        return $session->checkAccessVisibilityByUser(
            $this->userHelper->getCurrent()
        );
    }
}
