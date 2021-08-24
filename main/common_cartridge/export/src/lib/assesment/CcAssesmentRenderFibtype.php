<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentRenderFibtype extends CcQuestionMetadataBase
{
    protected $material = null;
    protected $materialRef = null;
    protected $responseLabel = null;
    protected $flowLabel = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::ENCODING);
        $this->setSetting(CcQtiTags::CHARSET);
        $this->setSetting(CcQtiTags::ROWS);
        $this->setSetting(CcQtiTags::COLUMNS);
        $this->setSetting(CcQtiTags::MAXCHARS);
        $this->setSetting(CcQtiTags::MINNUMBER);
        $this->setSetting(CcQtiTags::MAXNUMBER);
        $this->setSetting(CcQtiTags::PROMPT, CcQtiValues::BOX);
        $this->setSetting(CcQtiTags::FIBTYPE, CcQtiValues::STRING);
    }

    public function setEncoding($value)
    {
        $this->setSetting(CcQtiTags::ENCODING, $value);
    }

    public function setCharset($value)
    {
        $this->setSetting(CcQtiTags::CHARSET, $value);
    }

    public function setRows($value)
    {
        $this->setSetting(CcQtiTags::ROWS, $value);
    }

    public function setColumns($value)
    {
        $this->setSetting(CcQtiTags::COLUMNS, $value);
    }

    public function setMaxchars($value)
    {
        $this->setSetting(CcQtiTags::COLUMNS, $value);
    }

    public function setLimits($min = null, $max = null)
    {
        $this->setSetting(CcQtiTags::MINNUMBER, $min);
        $this->setSetting(CcQtiTags::MAXNUMBER, $max);
    }

    public function setPrompt($value)
    {
        $this->setSetting(CcQtiTags::PROMPT, $value);
    }

    public function setFibtype($value)
    {
        $this->setSetting(CcQtiTags::FIBTYPE, $value);
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
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::RENDER_FIB);
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
