<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentRubricBase extends CcQuestionMetadataBase
{
    protected $material;

    public function setMaterial($object): void
    {
        $this->material = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $rubric = $doc->appendNewElementNs($item, $namespace, CcQtiTags::RUBRIC);
        if (!empty($this->material)) {
            $this->material->generate($doc, $rubric, $namespace);
        }
    }
}
