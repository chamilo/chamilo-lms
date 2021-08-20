<?php
/* For licensing terms, see /license.txt */

class CcResponseLidtype extends CcQuestionMetadataBase
{
    protected $tagname = null;
    protected $material = null;
    protected $materialRef = null;
    protected $renderChoice = null;
    protected $renderFib = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::rcardinality, CcQtiValues::Single);
        $this->setSetting(CcQtiTags::rtiming);
        $this->setSetting(CcQtiTags::ident, CcHelpers::uuidgen('I_'));
        $this->tagname = CcQtiTags::response_lid;
    }

    public function setRcardinality($value)
    {
        $this->setSetting(CcQtiTags::rcardinality, $value);
    }

    public function enableRtiming($value = true)
    {
        $this->enableSettingYesno(CcQtiTags::rtiming, $value);
    }

    public function setIdent($value)
    {
        $this->setSetting(CcQtiTags::ident, $value);
    }

    public function getIdent()
    {
        return $this->getSetting(CcQtiTags::ident);
    }

    public function setMaterialRef(CcAssesmentResponseMatref $object)
    {
        $this->materialRef = $object;
    }

    public function setMaterial(CcAssesmentMaterial $object)
    {
        $this->material = $object;
    }

    public function setRenderChoice(CcAssesmentRenderChoicetype $object)
    {
        $this->renderChoice = $object;
    }

    public function setRenderFib(CcAssesmentRenderFibtype $object)
    {
        $this->renderFib = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->material) && empty($this->materialRef)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->materialRef) && empty($this->material)) {
            $this->materialRef->generate($doc, $node, $namespace);
        }

        if (!empty($this->renderChoice) && empty($this->renderFib)) {
            $this->renderChoice->generate($doc, $node, $namespace);
        }

        if (!empty($this->renderFib) && empty($this->renderChoice)) {
            $this->renderFib->generate($doc, $node, $namespace);
        }
    }
}
