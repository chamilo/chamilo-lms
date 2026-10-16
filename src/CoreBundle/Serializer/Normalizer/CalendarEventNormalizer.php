<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer\Normalizer;

use Chamilo\CoreBundle\ApiResource\CalendarEvent;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Security;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CalendarEventNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'CALENDAR_EVENT_NORMALIZER_ALREADY_CALLED';

    public function normalize($data, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var array<string, mixed> $result */
        $result = $this->normalizer->normalize($data, $format, $context);

        if (isset($result['content']) && \is_string($result['content'])) {
            $result['content'] = Security::remove_XSS($result['content'], STUDENT);
        }

        return $result;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof CCalendarEvent || $data instanceof CalendarEvent;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            CCalendarEvent::class => false,
            CalendarEvent::class => false,
        ];
    }
}
