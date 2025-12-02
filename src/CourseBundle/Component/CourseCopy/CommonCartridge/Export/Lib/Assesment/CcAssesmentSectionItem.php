<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentSectionItem extends CcAssesmentSection
{
    protected $itemmetadata;
    protected $presentation;
    protected $resprocessing = [];
    protected $itemfeedback = [];

    public function setItemmetadata(CcAssesmentItemmetadata $object): void
    {
        $this->itemmetadata = $object;
    }

    public function setPresentation(CcAssesmentPresentation $object): void
    {
        $this->presentation = $object;
    }

    public function addResprocessing(CcAssesmentResprocessingtype $object): void
    {
        $this->resprocessing[] = $object;
    }

    public function addItemfeedback(CcAssesmentItemfeedbacktype $object): void
    {
        $this->itemfeedback[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::ITEM);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->itemmetadata)) {
            $this->itemmetadata->generate($doc, $node, $namespace);
        }

        if (!empty($this->presentation)) {
            $this->presentation->generate($doc, $node, $namespace);
        }

        if (!empty($this->resprocessing)) {
            foreach ($this->resprocessing as $resprocessing) {
                $resprocessing->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->itemfeedback)) {
            foreach ($this->itemfeedback as $itemfeedback) {
                $itemfeedback->generate($doc, $node, $namespace);
            }
        }
    }
}
