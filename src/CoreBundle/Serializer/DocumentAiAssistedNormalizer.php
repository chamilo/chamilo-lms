<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Serializer;

use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

final class DocumentAiAssistedNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'chamilo_document_ai_assisted_normalizer_called';

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

    public function normalize($object, ?string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        if (!\is_array($data) || !$object instanceof CDocument) {
            return $data;
        }

        $docId = (int) ($object->getIid() ?? 0);
        if ($docId <= 0) {
            $data['ai_assisted'] = false;
            $data['ai_assisted_raw'] = false;

            return $data;
        }

        $raw = $this->aiDisclosureHelper->isAiAssistedExtraField('document', $docId);
        $data['ai_assisted_raw'] = (bool) $raw;
        $data['ai_assisted'] = $this->aiDisclosureHelper->isDisclosureEnabled() && $raw;

        return $data;
    }
}
