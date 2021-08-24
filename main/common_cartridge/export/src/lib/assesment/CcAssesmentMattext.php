<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentMattext extends CcQuestionMetadataBase
{
    protected $value = null;

    public function __construct($value = null)
    {
        $this->setSetting(CcQtiTags::TEXTTYPE, CcQtiValues::TEXTTYPE);
        $this->setSetting(CcQtiTags::CHARSET); //, 'ascii-us');
        $this->setSetting(CcQtiTags::LABEL);
        $this->setSetting(CcQtiTags::URI);
        $this->setSetting(CcQtiTags::WIDTH);
        $this->setSetting(CcQtiTags::HEIGHT);
        $this->setSetting(CcQtiTags::X0);
        $this->setSetting(CcQtiTags::Y0);
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML);
        $this->setSettingWns(CcQtiTags::XML_SPACE, CcXmlNamespace::XML); //, 'default');
        $this->value = $value;
    }

    public function setLabel($value)
    {
        $this->setSetting(CcQtiTags::LABEL, $value);
    }

    public function setUri($value)
    {
        $this->setSetting(CcQtiTags::URI, $value);
    }

    public function setWidthHeight($width = null, $height = null)
    {
        $this->setSetting(CcQtiTags::WIDTH, $width);
        $this->setSetting(CcQtiTags::HEIGHT, $height);
    }

    public function setCoor($x = null, $y = null)
    {
        $this->setSetting(CcQtiTags::X0, $x);
        $this->setSetting(CcQtiTags::Y0, $y);
    }

    public function setLang($lang = null)
    {
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML, $lang);
    }

    public function setContent($content, $type = CcQtiValues::TEXTTYPE, $charset = null)
    {
        $this->value = $content;
        $this->setSetting(CcQtiTags::TEXTTYPE, $type);
        $this->setSetting(CcQtiTags::CHARSET, $charset);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $mattext = $doc->appendNewElementNsCdata($item, $namespace, CcQtiTags::MATTEXT, $this->value);
        $this->generateAttributes($doc, $mattext, $namespace);
    }
}
