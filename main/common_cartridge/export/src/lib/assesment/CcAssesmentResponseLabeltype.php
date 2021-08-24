<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentResponseLabeltype extends CcQuestionMetadataBase
{
    protected $material = null;
    protected $materialRef = null;
    protected $flowMat = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::IDENT, CcHelpers::uuidgen('I_'));
        $this->setSetting(CcQtiTags::LABELREFID);
        $this->setSetting(CcQtiTags::RSHUFFLE);
        $this->setSetting(CcQtiTags::MATCH_GROUP);
        $this->setSetting(CcQtiTags::MATCH_MAX);
    }

    public function setIdent($value)
    {
        $this->setSetting(CcQtiTags::IDENT, $value);
    }

    public function getIdent()
    {
        return $this->getSetting(CcQtiTags::IDENT);
    }

    public function setLabelrefid($value)
    {
        $this->setSetting(CcQtiTags::LABELREFID, $value);
    }

    public function enableRshuffle($value = true)
    {
        $this->enableSettingYesno(CcQtiTags::RSHUFFLE, $value);
    }

    public function setMatchGroup($value)
    {
        $this->setSetting(CcQtiTags::MATCH_GROUP, $value);
    }

    public function setMatchMax($value)
    {
        $this->setSetting(CcQtiTags::MATCH_MAX, $value);
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
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::RESPONSE_LABEL);
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
