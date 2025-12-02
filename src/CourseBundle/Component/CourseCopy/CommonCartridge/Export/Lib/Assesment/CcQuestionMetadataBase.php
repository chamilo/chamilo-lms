<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcQuestionMetadataBase
{
    /**
     * @var array<string,mixed>
     */
    protected array $metadata = [];

    /**
     * Generates attributes (name/value pairs) on the given node.
     */
    public function generateAttributes(XMLGenericDocument &$doc, DOMNode &$item, string $namespace): void
    {
        foreach ($this->metadata as $attribute => $value) {
            if (null === $value) {
                continue;
            }

            if (!\is_array($value)) {
                $doc->appendNewAttributeNs($item, $namespace, (string) $attribute, (string) $value);

                continue;
            }

            $ns = key($value);
            $nval = current($value);
            if (null !== $nval && \is_string($ns)) {
                $doc->appendNewAttributeNs($item, $ns, (string) $attribute, (string) $nval);
            }
        }
    }

    /**
     * Generates the <qtimetadata> block.
     * The signature must exactly match that of the subclasses.
     */
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, string $namespace): void
    {
        $qtimetadata = $doc->appendNewElementNs($item, $namespace, CcQtiTags::QTIMETADATA);

        foreach ($this->metadata as $label => $entry) {
            if (null === $entry) {
                continue;
            }

            $qtimetadatafield = $doc->appendNewElementNs($qtimetadata, $namespace, CcQtiTags::QTIMETADATAFIELD);
            $doc->appendNewElementNs($qtimetadatafield, $namespace, CcQtiTags::FIELDLABEL, (string) $label);
            $doc->appendNewElementNs($qtimetadatafield, $namespace, CcQtiTags::FIELDENTRY, (string) $entry);
        }
    }

    /**
     * Saves a simple setting (key => value).
     */
    protected function setSetting(string $setting, mixed $value = null): void
    {
        $this->metadata[$setting] = $value;
    }

    /**
     * Gets a setting; null if it doesn't exist.
     */
    protected function getSetting(string $setting): mixed
    {
        return $this->metadata[$setting] ?? null;
    }

    /**
     * Saves a setting with a specific namespace.
     */
    protected function setSettingWns(string $setting, string $namespace, ?string $value = null): void
    {
        $this->metadata[$setting] = [$namespace => $value];
    }

    /**
     * Saves a boolean setting as yes/no (QTI values).
     */
    protected function enableSettingYesno(string $setting, bool $value = true): void
    {
        $this->setSetting($setting, $value ? CcQtiValues::YES : CcQtiValues::NO);
    }
}
