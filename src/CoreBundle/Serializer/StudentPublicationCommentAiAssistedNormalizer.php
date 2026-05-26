<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Serializer;

use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

final class StudentPublicationCommentAiAssistedNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'chamilo_work_comment_ai_assisted_normalizer_called';
    private const HANDLER = 'work_corrections_comment';

    public function __construct(
        private readonly AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof CStudentPublicationComment;
    }

    public function normalize($object, ?string $format = null, array $context = [])
    {
        $context[self::ALREADY_CALLED] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        if (!\is_array($data) || !$object instanceof CStudentPublicationComment) {
            return $data;
        }

        $iid = (int) ($object->getIid() ?? 0);
        if ($iid <= 0) {
            $data['ai_assisted_raw'] = false;
            $data['ai_assisted'] = false;

            return $data;
        }

        $raw = $this->aiDisclosureHelper->isAiAssistedExtraField(self::HANDLER, $iid);

        $data['ai_assisted_raw'] = (bool) $raw;
        $data['ai_assisted'] = $this->aiDisclosureHelper->isDisclosureEnabled() && (bool) $raw;

        return $data;
    }
}
