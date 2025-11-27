<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

abstract class CcAssesmentMaterialBase extends CcQuestionMetadataBase
{
    /**
     * @var mixed
     */
    protected $mattag;
    protected $tagname;

    public function setMattext(CcAssesmentMattext $object): void
    {
        $this->setTagValue($object);
    }

    public function setMatref(CcAssesmentMatref $object): void
    {
        $this->setTagValue($object);
    }

    public function setMatbreak(CcAssesmentMatbreak $object): void
    {
        $this->setTagValue($object);
    }

    public function setLang($value): void
    {
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $material = $doc->appendNewElementNs($item, $namespace, $this->tagname);
        $this->generateAttributes($doc, $material, $namespace);
        if (!empty($this->mattag)) {
            $this->mattag->generate($doc, $material, $namespace);
        }

        return $material;
    }

    protected function setTagValue($object): void
    {
        $this->mattag = $object;
    }
}
