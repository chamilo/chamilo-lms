<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentMaterial extends CcAssesmentMaterialBase
{
    protected $altmaterial;

    public function __construct($value = null)
    {
        $this->setSetting(CcQtiTags::LABEL);
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML);
        $this->tagname = CcQtiTags::MATERIAL;
    }

    public function setLabel($value): void
    {
        $this->setSetting(CcQtiTags::LABEL, $value);
    }

    public function setAltmaterial(CcAssesmentAltmaterial $object): void
    {
        $this->altmaterial = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $material = parent::generate($doc, $item, $namespace);
        if (!empty($this->altmaterial)) {
            $this->altmaterial->generate($doc, $material, $namespace);
        }
    }
}
