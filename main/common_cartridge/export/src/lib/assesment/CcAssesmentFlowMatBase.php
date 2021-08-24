<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentFlowMatBase extends CcQuestionMetadataBase
{
    protected $mattag = null;

    public function __construct($value = null)
    {
        $this->setSetting(CcQtiTags::T_CLASS);
    }

    public function setFlowMat(CcAssesmentFlowMatBase $object)
    {
        $this->setTagValue($object);
    }

    public function setMaterial(CcAssesmentMaterial $object)
    {
        $this->setTagValue($object);
    }

    public function setMaterialRef(CcAssesmentMatref $object)
    {
        $this->setTagValue($object);
    }

    public function setClass($value)
    {
        $this->setSetting(CcQtiTags::T_CLASS, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::FLOW_MAT);
        $this->generateAttributes($doc, $node, $namespace);
        if (!empty($this->mattag)) {
            $this->mattag->generate($doc, $node, $namespace);
        }
    }

    protected function setTagValue($object)
    {
        $this->mattag = $object;
    }
}
