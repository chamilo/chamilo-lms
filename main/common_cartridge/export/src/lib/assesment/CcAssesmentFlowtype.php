<?php
/* For licensing terms, see /license.txt */

class CcAssesmentFlowtype extends CcQuestionMetadataBase
{
    protected $flow = null;
    protected $material = null;
    protected $materialRef = null;
    protected $responseLid = null;
    protected $responseStr = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::t_class);
    }

    public function setClass($value)
    {
        $this->setSetting(CcQtiTags::t_class, $value);
    }

    public function setFlow(CcAssesmentFlowtype $object)
    {
        $this->flow = $object;
    }

    public function setMaterial(CcAssesmentMaterial $object)
    {
        $this->material = $object;
    }

    public function setMaterialRef(CcAssesmentResponseMatref $object)
    {
        $this->materialRef = $object;
    }

    public function setResponseLid(CcResponseLidtype $object)
    {
        $this->responseLid = $object;
    }

    public function setResponseStr(CcAssesmentResponseStrtype $object)
    {
        $this->responseStr = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::flow);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->flow)) {
            $this->flow->generate($doc, $node, $namespace);
        }

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseLid)) {
            $this->responseLid->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseStr)) {
            $this->responseStr->generate($doc, $node, $namespace);
        }
    }
}
