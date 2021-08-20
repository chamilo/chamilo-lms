<?php
/* For licensing terms, see /license.txt */

class CcAssesmentFlowMattype extends CcQuestionMetadataBase
{
    protected $material = null;
    protected $materialRef = null;
    protected $flowMat = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::t_class);
    }

    public function setClass($value)
    {
        $this->setSetting(CcQtiTags::t_class, $value);
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
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::flow_mat);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->flowMat)) {
            $this->flowMat->generate($doc, $node, $namespace);
        }

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->materialRef)) {
            $this->materialRef->generate($doc, $node, $namespace);
        }
    }
}
