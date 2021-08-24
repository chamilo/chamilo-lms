<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentSection extends CcQuestionMetadataBase
{
    /**
     * @var array
     */
    protected $items = [];

    public function __construct()
    {
        $this->setSetting(CcQtiTags::IDENT, CcHelpers::uuidgen('I_'));
        $this->setSetting(CcQtiTags::TITLE);
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML);
    }

    public function setIdent($value)
    {
        $this->setSetting(CcQtiTags::IDENT, $value);
    }

    public function setTitle($value)
    {
        $this->setSetting(CcQtiTags::TITLE, $value);
    }

    public function setLang($value)
    {
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML, $value);
    }

    public function addItem(CcAssesmentSectionItem $object)
    {
        $this->items[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::SECTION);
        $this->generateAttributes($doc, $node, $namespace);
        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $item->generate($doc, $node, $namespace);
            }
        }
    }
}
