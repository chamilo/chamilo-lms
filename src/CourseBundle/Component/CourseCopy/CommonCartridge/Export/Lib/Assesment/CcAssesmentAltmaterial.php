<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

class CcAssesmentAltmaterial extends CcAssesmentMaterialBase
{
    public function __construct($value = null)
    {
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML);
        $this->tagname = CcQtiTags::ALTMATERIAL;
    }
}
