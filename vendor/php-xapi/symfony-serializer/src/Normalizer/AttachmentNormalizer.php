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

use Xabbuh\XApi\Model\Attachment;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;

/**
 * Denormalizes PHP arrays to {@link Attachment} objects.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class AttachmentNormalizer extends Normalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!$object instanceof Attachment) {
            return;
        }

        $data = array(
            'usageType' => $object->getUsageType()->getValue(),
            'contentType' => $object->getContentType(),
            'length' => $object->getLength(),
            'sha2' => $object->getSha2(),
            'display' => $this->normalizeAttribute($object->getDisplay(), $format, $context),
        );

        if (null !== $description = $object->getDescription()) {
            $data['description'] = $this->normalizeAttribute($description, $format, $context);
        }

        if (null !== $fileUrl = $object->getFileUrl()) {
            $data['fileUrl'] = $fileUrl->getValue();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Attachment;
    }

    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $display = $this->denormalizeData($data['display'], 'Xabbuh\XApi\Model\LanguageMap', $format, $context);
        $description = null;
        $fileUrl = null;
        $content = null;

        if (isset($data['description'])) {
            $description = $this->denormalizeData($data['description'], 'Xabbuh\XApi\Model\LanguageMap', $format, $context);
        }

        if (isset($data['fileUrl'])) {
            $fileUrl = IRL::fromString($data['fileUrl']);
        }

        if (isset($context['xapi_attachments'][$data['sha2']])) {
            $content = $context['xapi_attachments'][$data['sha2']]['content'];
        }

        return new Attachment(IRI::fromString($data['usageType']), $data['contentType'], $data['length'], $data['sha2'], $display, $description, $fileUrl, $content);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return 'Xabbuh\XApi\Model\Attachment' === $type;
    }
}
