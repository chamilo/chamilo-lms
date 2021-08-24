<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentAltmaterial extends CcAssesmentMaterialBase
{
    public function __construct($value = null)
    {
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML);
        $this->tagname = CcQtiTags::ALTMATERIAL;
    }
}
