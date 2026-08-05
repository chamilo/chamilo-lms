<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Serializer;

use ArrayObject;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DocumentAiAssistedNormalizer implements NormalizerAwareInterface, NormalizerInterface
{
    use NormalizerAwareTrait;

    private const string ALREADY_CALLED = 'chamilo_document_ai_assisted_normalizer_called';

    public function __construct(
        private readonly AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof CDocument;
    }

    public function normalize($data, ?string $format = null, array $context = []): array|ArrayObject|bool|float|int|string|null
    {
        $context[self::ALREADY_CALLED] = true;

        $result = $this->normalizer->normalize($data, $format, $context);

        if (!\is_array($result) || !$data instanceof CDocument) {
            return $result;
        }

        $docId = (int) ($data->getIid() ?? 0);
        if ($docId <= 0) {
            $result['ai_assisted'] = false;
            $result['ai_assisted_raw'] = false;

            return $result;
        }

        $raw = $this->aiDisclosureHelper->isAiAssistedExtraField('document', $docId);
        $result['ai_assisted_raw'] = (bool) $raw;
        $result['ai_assisted'] = $this->aiDisclosureHelper->isDisclosureEnabled() && $raw;

        return $result;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [CDocument::class => false];
    }
}
