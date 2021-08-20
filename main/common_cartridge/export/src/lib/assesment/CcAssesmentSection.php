<?php
/* For licensing terms, see /license.txt */

class CcAssesmentSection extends CcQuestionMetadataBase
{
    /**
     * @var array
     */
    protected $items = [];

    public function __construct()
    {
        $this->setSetting(CcQtiTags::ident, CcHelpers::uuidgen('I_'));
        $this->setSetting(CcQtiTags::title);
        $this->setSettingWns(CcQtiTags::xml_lang, CcXmlNamespace::xml);
    }

    public function setIdent($value)
    {
        $this->setSetting(CcQtiTags::ident, $value);
    }

    public function setTitle($value)
    {
        $this->setSetting(CcQtiTags::title, $value);
    }

    public function setLang($value)
    {
        $this->setSettingWns(CcQtiTags::xml_lang, CcXmlNamespace::xml, $value);
    }

    public function addItem(CcAssesmentSectionItem $object)
    {
        $this->items[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::section);
        $this->generateAttributes($doc, $node, $namespace);
        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $item->generate($doc, $node, $namespace);
            }
        }
    }
}
