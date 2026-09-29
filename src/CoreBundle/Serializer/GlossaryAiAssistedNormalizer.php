<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Serializer;

use ArrayObject;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CourseBundle\Entity\CGlossary;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class GlossaryAiAssistedNormalizer implements NormalizerAwareInterface, NormalizerInterface
{
    use NormalizerAwareTrait;

    private const string ALREADY_CALLED = 'chamilo_glossary_ai_assisted_normalizer_called';

    public function __construct(
        private readonly AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof CGlossary;
    }

    public function normalize($data, ?string $format = null, array $context = []): array|ArrayObject|bool|float|int|string|null
    {
        $context[self::ALREADY_CALLED] = true;

        $result = $this->normalizer->normalize($data, $format, $context);

        if (!\is_array($result) || !$data instanceof CGlossary) {
            return $result;
        }

        $iid = (int) ($data->getIid() ?? 0);
        if ($iid <= 0) {
            $result['ai_assisted_raw'] = false;
            $result['ai_assisted'] = false;

            return $result;
        }

        $raw = $this->aiDisclosureHelper->isAiAssistedExtraField('glossary', $iid);
        $result['ai_assisted_raw'] = (bool) $raw;
        $result['ai_assisted'] = $this->aiDisclosureHelper->isDisclosureEnabled() && (bool) $raw;

        return $result;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [CGlossary::class => false];
    }
}
