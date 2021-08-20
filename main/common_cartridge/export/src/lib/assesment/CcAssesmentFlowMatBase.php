<?php
/* For licensing terms, see /license.txt */

class CcAssesmentFlowMatBase extends CcQuestionMetadataBase
{
    protected $mattag = null;

    public function __construct($value = null)
    {
        $this->setSetting(CcQtiTags::t_class);
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
        $this->setSetting(CcQtiTags::t_class, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::flow_mat);
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
