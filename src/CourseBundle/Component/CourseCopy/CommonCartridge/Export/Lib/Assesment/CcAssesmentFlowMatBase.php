<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentFlowMatBase extends CcQuestionMetadataBase
{
    protected $mattag;

    public function __construct($value = null)
    {
        $this->setSetting(CcQtiTags::T_CLASS);
    }

    public function setFlowMat(self $object): void
    {
        $this->setTagValue($object);
    }

    public function setMaterial(CcAssesmentMaterial $object): void
    {
        $this->setTagValue($object);
    }

    public function setMaterialRef(CcAssesmentMatref $object): void
    {
        $this->setTagValue($object);
    }

    public function setClass($value): void
    {
        $this->setSetting(CcQtiTags::T_CLASS, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::FLOW_MAT);
        $this->generateAttributes($doc, $node, $namespace);
        if (!empty($this->mattag)) {
            $this->mattag->generate($doc, $node, $namespace);
        }
    }

    protected function setTagValue($object): void
    {
        $this->mattag = $object;
    }
}
