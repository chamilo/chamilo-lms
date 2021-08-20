<?php
/* For licensing terms, see /license.txt */

class CcAssesmentItemfeedbacktype extends CcQuestionMetadataBase
{
    protected $flowMat = null;
    protected $material = null;
    protected $solution = null;
    protected $hint = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::ident, CcHelpers::uuidgen('I_'));
        $this->setSetting(CcQtiTags::title);
    }

    /**
     * @param string $value
     */
    public function setIdent($value)
    {
        $this->setSetting(CcQtiTags::ident, $value);
    }

    /**
     * @param string $value
     */
    public function setTitle($value)
    {
        $this->setSetting(CcQtiTags::title, $value);
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
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::itemfeedback);
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
