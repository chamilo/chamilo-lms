<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentFlowMattype extends CcQuestionMetadataBase
{
    protected $material;
    protected $materialRef;
    protected $flowMat;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::T_CLASS);
    }

    public function setClass($value): void
    {
        $this->setSetting(CcQtiTags::T_CLASS, $value);
    }

    public function setMaterial(CcAssesmentMaterial $object): void
    {
        $this->material = $object;
    }

    public function setMaterialRef(CcAssesmentResponseMatref $object): void
    {
        $this->materialRef = $object;
    }

    public function setFlowMat(self $object): void
    {
        $this->flowMat = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::FLOW_MAT);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->flowMat)) {
            $this->flowMat->generate($doc, $node, $namespace);
        }

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->materialRef)) {
            $this->materialRef->generate($doc, $node, $namespace);
        }
    }
}
