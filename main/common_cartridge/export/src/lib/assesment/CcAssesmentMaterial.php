<?php
/* For licensing terms, see /license.txt */

class CcAssesmentMaterial extends CcAssesmentMaterialBase
{
    protected $altmaterial = null;

    public function __construct($value = null)
    {
        $this->setSetting(CcQtiTags::label);
        $this->setSettingWns(CcQtiTags::xml_lang, CcXmlNamespace::xml);
        $this->tagname = CcQtiTags::material;
    }

    public function setLabel($value)
    {
        $this->setSetting(CcQtiTags::label, $value);
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
