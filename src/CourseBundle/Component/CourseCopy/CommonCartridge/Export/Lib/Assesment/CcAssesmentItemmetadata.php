<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentItemmetadata extends CcQuestionMetadataBase
{
    public function addMetadata($object): void
    {
        $this->metadata[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::ITEMMETADATA);
        if (!empty($this->metadata)) {
            foreach ($this->metadata as $metaitem) {
                $metaitem->generate($doc, $node, $namespace);
            }
        }
    }
}
