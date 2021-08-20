<?php
/* For licensing terms, see /license.txt */

class CcAssesmentResponseLabeltype extends CcQuestionMetadataBase
{
    protected $material = null;
    protected $materialRef = null;
    protected $flowMat = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::ident, CcHelpers::uuidgen('I_'));
        $this->setSetting(CcQtiTags::labelrefid);
        $this->setSetting(CcQtiTags::rshuffle);
        $this->setSetting(CcQtiTags::match_group);
        $this->setSetting(CcQtiTags::match_max);
    }

    public function setIdent($value)
    {
        $this->setSetting(CcQtiTags::ident, $value);
    }

    public function getIdent()
    {
        return $this->getSetting(CcQtiTags::ident);
    }

    public function setLabelrefid($value)
    {
        $this->setSetting(CcQtiTags::labelrefid, $value);
    }

    public function enableRshuffle($value = true)
    {
        $this->enableSettingYesno(CcQtiTags::rshuffle, $value);
    }

    public function setMatchGroup($value)
    {
        $this->setSetting(CcQtiTags::match_group, $value);
    }

    public function setMatchMax($value)
    {
        $this->setSetting(CcQtiTags::match_max, $value);
    }

    public function setMaterial(CcAssesmentMaterial $object)
    {
        $this->material = $object;
    }

    public function setMaterialRef(CcAssesmentResponseMatref $object)
    {
        $this->materialRef = $object;
    }

    public function setFlowMat(CcAssesmentFlowMattype $object)
    {
        $this->flowMat = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::response_label);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->materialRef)) {
            $this->materialRef->generate($doc, $node, $namespace);
        }

        if (!empty($this->flowMat)) {
            $this->flowMat->generate($doc, $node, $namespace);
        }
    }
}
