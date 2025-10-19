<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentItemfeedbackShintmaterialBase extends CcQuestionMetadataBase
{
    protected $tagname;
    protected $flowMats = [];
    protected $materials = [];

    public function addFlowMat(CcAssesmentFlowMattype $object): void
    {
        $this->flowMats[] = $object;
    }

    public function add_material(CcAssesmentMaterial $object): void
    {
        $this->materials[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname);

        if (!empty($this->flowMats)) {
            foreach ($this->flowMats as $flowMat) {
                $flowMat->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->materials)) {
            foreach ($this->materials as $material) {
                $material->generate($doc, $node, $namespace);
            }
        }
    }
}
