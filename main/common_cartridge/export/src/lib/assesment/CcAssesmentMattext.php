<?php
/* For licensing terms, see /license.txt */

class CcAssesmentMattext extends CcQuestionMetadataBase
{
    protected $value = null;

    public function __construct($value = null)
    {
        $this->setSetting(CcQtiTags::texttype, CcQtiValues::texttype);
        $this->setSetting(CcQtiTags::charset); //, 'ascii-us');
        $this->setSetting(CcQtiTags::label);
        $this->setSetting(CcQtiTags::uri);
        $this->setSetting(CcQtiTags::width);
        $this->setSetting(CcQtiTags::height);
        $this->setSetting(CcQtiTags::x0);
        $this->setSetting(CcQtiTags::y0);
        $this->setSettingWns(CcQtiTags::xml_lang, CcXmlNamespace::xml);
        $this->setSettingWns(CcQtiTags::xml_space, CcXmlNamespace::xml); //, 'default');
        $this->value = $value;
    }

    public function setLabel($value)
    {
        $this->setSetting(CcQtiTags::label, $value);
    }

    public function setUri($value)
    {
        $this->setSetting(CcQtiTags::uri, $value);
    }

    public function setWidthHeight($width = null, $height = null)
    {
        $this->setSetting(CcQtiTags::width, $width);
        $this->setSetting(CcQtiTags::height, $height);
    }

    public function setCoor($x = null, $y = null)
    {
        $this->setSetting(CcQtiTags::x0, $x);
        $this->setSetting(CcQtiTags::y0, $y);
    }

    public function setLang($lang = null)
    {
        $this->setSettingWns(CcQtiTags::xml_lang, CcXmlNamespace::xml, $lang);
    }

    public function setContent($content, $type = CcQtiValues::texttype, $charset = null)
    {
        $this->value = $content;
        $this->setSetting(CcQtiTags::texttype, $type);
        $this->setSetting(CcQtiTags::charset, $charset);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $mattext = $doc->appendNewElementNsCdata($item, $namespace, CcQtiTags::mattext, $this->value);
        $this->generateAttributes($doc, $mattext, $namespace);
    }
}
