<?php
/* For licensing terms, see /license.txt */

class CcAssesmentRenderFibtype extends CcQuestionMetadataBase
{
    protected $material = null;
    protected $materialRef = null;
    protected $responseLabel = null;
    protected $flowLabel = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::encoding);
        $this->setSetting(CcQtiTags::charset);
        $this->setSetting(CcQtiTags::rows);
        $this->setSetting(CcQtiTags::columns);
        $this->setSetting(CcQtiTags::maxchars);
        $this->setSetting(CcQtiTags::minnumber);
        $this->setSetting(CcQtiTags::maxnumber);
        $this->setSetting(CcQtiTags::prompt, CcQtiValues::Box);
        $this->setSetting(CcQtiTags::fibtype, CcQtiValues::String);
    }

    public function setEncoding($value)
    {
        $this->setSetting(CcQtiTags::encoding, $value);
    }

    public function setCharset($value)
    {
        $this->setSetting(CcQtiTags::charset, $value);
    }

    public function setRows($value)
    {
        $this->setSetting(CcQtiTags::rows, $value);
    }

    public function setColumns($value)
    {
        $this->setSetting(CcQtiTags::columns, $value);
    }

    public function setMaxchars($value)
    {
        $this->setSetting(CcQtiTags::columns, $value);
    }

    public function setLimits($min = null, $max = null)
    {
        $this->setSetting(CcQtiTags::minnumber, $min);
        $this->setSetting(CcQtiTags::maxnumber, $max);
    }

    public function setPrompt($value)
    {
        $this->setSetting(CcQtiTags::prompt, $value);
    }

    public function setFibtype($value)
    {
        $this->setSetting(CcQtiTags::fibtype, $value);
    }

    public function setMaterial(CcAssesmentMaterial $object)
    {
        $this->material = $object;
    }

    public function setMaterialRef(CcAssesmentResponseMatref $object)
    {
        $this->materialRef = $object;
    }

    public function setResponseLabel(CcAssesmentResponseLabeltype $object)
    {
        $this->responseLabel = $object;
    }

    public function setFlowLabel($object)
    {
        $this->flowLabel = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::render_fib);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->material) && empty($this->materialRef)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->materialRef) && empty($this->material)) {
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
