<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentFlowLabeltype extends CcQuestionMetadataBase
{
    protected $flowLabel = null;
    protected $responseLabel = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::T_CLASS);
    }

    public function setClass($value)
    {
        $this->setSetting(CcQtiTags::T_CLASS, $value);
    }

    public function setFlowLabel(CcAssesmentFlowLabeltype $object)
    {
        $this->flowLabel = $object;
    }

    public function setResponseLabel(CcAssesmentResponseLabeltype $object)
    {
        $this->responseLabel = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::FLOW_LABEL);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->materialRef)) {
            $this->materialRef->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseLabel)) {
            $this->responseLabel->generate($doc, $node, $namespace);
        }

        if (!empty($this->flowLabel)) {
            $this->flowLabel->generate($doc, $node, $namespace);
        }
    }
}
