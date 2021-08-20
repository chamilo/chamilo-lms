<?php
/* For licensing terms, see /license.txt */

class CcAssesmentPresentation extends CcQuestionMetadataBase
{
    protected $flow = null;
    protected $material = null;
    protected $responseLid = null;
    protected $responseStr = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::label);
        $this->setSettingWns(CcQtiTags::xml_lang, CcXmlNamespace::xml);
        $this->setSetting(CcQtiTags::x0);
        $this->setSetting(CcQtiTags::y0);
        $this->setSetting(CcQtiTags::width);
        $this->setSetting(CcQtiTags::height);
    }

    public function setLabel($value)
    {
        $this->setSetting(CcQtiTags::label, $value);
    }

    public function setLang($value)
    {
        $this->setSettingWns(CcQtiTags::xml_lang, CcXmlNamespace::xml, $value);
    }

    public function setCoor($x = null, $y = null)
    {
        $this->setSetting(CcQtiTags::x0, $x);
        $this->setSetting(CcQtiTags::y0, $y);
    }

    public function setSize($width = null, $height = null)
    {
        $this->setSetting(CcQtiTags::width, $width);
        $this->setSetting(CcQtiTags::height, $height);
    }

    public function setFlow(CcAssesmentFlowtype $object)
    {
        $this->flow = $object;
    }

    public function setMaterial(CcAssesmentMaterial $object)
    {
        $this->material = $object;
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
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::presentation);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->flow)) {
            $this->flow->generate($doc, $node, $namespace);
        }

        if (!empty($this->material) && empty($this->flow)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseLid) && empty($this->flow)) {
            $this->responseLid->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseStr) && empty($this->flow)) {
            $this->responseStr->generate($doc, $node, $namespace);
        }
    }
}
