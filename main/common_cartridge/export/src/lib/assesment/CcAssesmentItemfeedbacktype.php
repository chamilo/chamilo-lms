<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentItemfeedbacktype extends CcQuestionMetadataBase
{
    protected $flowMat = null;
    protected $material = null;
    protected $solution = null;
    protected $hint = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::IDENT, CcHelpers::uuidgen('I_'));
        $this->setSetting(CcQtiTags::TITLE);
    }

    /**
     * @param string $value
     */
    public function setIdent($value)
    {
        $this->setSetting(CcQtiTags::IDENT, $value);
    }

    /**
     * @param string $value
     */
    public function setTitle($value)
    {
        $this->setSetting(CcQtiTags::TITLE, $value);
    }

    public function setFlowMat(CcAssesmentFlowMattype $object)
    {
        $this->flowMat = $object;
    }

    public function setMaterial(CcAssesmentMaterial $object)
    {
        $this->material = $object;
    }

    public function set_solution(CcAssesmentItemfeedbackSolutiontype $object)
    {
        $this->solution = $object;
    }

    public function set_hint($object)
    {
        $this->hint = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
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
