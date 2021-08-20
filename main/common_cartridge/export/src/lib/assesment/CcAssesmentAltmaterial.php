<?php
/* For licensing terms, see /license.txt */

class CcAssesmentAltmaterial extends CcAssesmentMaterialBase
{
    public function __construct($value = null)
    {
        $this->setSettingWns(CcQtiTags::xml_lang, CcXmlNamespace::xml);
        $this->tagname = CcQtiTags::altmaterial;
    }
}
