<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentMaterial extends CcAssesmentMaterialBase
{
    protected $altmaterial = null;

    public function __construct($value = null)
    {
        $this->setSetting(CcQtiTags::LABEL);
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML);
        $this->tagname = CcQtiTags::MATERIAL;
    }

    public function setLabel($value)
    {
        $this->setSetting(CcQtiTags::LABEL, $value);
    }

    public function setAltmaterial(CcAssesmentAltmaterial $object)
    {
        $this->altmaterial = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $material = parent::generate($doc, $item, $namespace);
        if (!empty($this->altmaterial)) {
            $this->altmaterial->generate($doc, $material, $namespace);
        }
    }
}
