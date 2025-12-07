<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcHelpers;
use DOMNode;

class CcAssesmentItemfeedbacktype extends CcQuestionMetadataBase
{
    protected $flowMat;
    protected $material;
    protected $solution;
    protected $hint;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::IDENT, CcHelpers::uuidgen('I_'));
        $this->setSetting(CcQtiTags::TITLE);
    }

    /**
     * @param string $value
     */
    public function setIdent($value): void
    {
        $this->setSetting(CcQtiTags::IDENT, $value);
    }

    /**
     * @param string $value
     */
    public function setTitle($value): void
    {
        $this->setSetting(CcQtiTags::TITLE, $value);
    }

    public function setFlowMat(CcAssesmentFlowMattype $object): void
    {
        $this->flowMat = $object;
    }

    public function setMaterial(CcAssesmentMaterial $object): void
    {
        $this->material = $object;
    }

    public function set_solution(CcAssesmentItemfeedbackSolutiontype $object): void
    {
        $this->solution = $object;
    }

    public function set_hint($object): void
    {
        $this->hint = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::ITEMFEEDBACK);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->flowMat) && empty($this->material)) {
            $this->flowMat->generate($doc, $node, $namespace);
        }

        if (!empty($this->material) && empty($this->flowMat)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->solution)) {
            $this->solution->generate($doc, $node, $namespace);
        }

        if (!empty($this->itemfeedback)) {
            $this->itemfeedback->generate($doc, $node, $namespace);
        }
    }
}
