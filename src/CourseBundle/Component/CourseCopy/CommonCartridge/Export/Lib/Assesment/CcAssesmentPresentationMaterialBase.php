<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentPresentationMaterialBase extends CcQuestionMetadataBase
{
    protected $flowmats = [];

    public function addFlowMat($object): void
    {
        $this->flowmats[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::PRESENTATION_MATERIAL);
        if (!empty($this->flowmats)) {
            foreach ($this->flowmats as $flowMat) {
                $flowMat->generate($doc, $node, $namespace);
            }
        }
    }
}
