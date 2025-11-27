<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentFlowtype extends CcQuestionMetadataBase
{
    protected $flow;
    protected $material;
    protected $materialRef;
    protected $responseLid;
    protected $responseStr;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::T_CLASS);
    }

    public function setClass($value): void
    {
        $this->setSetting(CcQtiTags::T_CLASS, $value);
    }

    public function setFlow(self $object): void
    {
        $this->flow = $object;
    }

    public function setMaterial(CcAssesmentMaterial $object): void
    {
        $this->material = $object;
    }

    public function setMaterialRef(CcAssesmentResponseMatref $object): void
    {
        $this->materialRef = $object;
    }

    public function setResponseLid(CcResponseLidtype $object): void
    {
        $this->responseLid = $object;
    }

    public function setResponseStr(CcAssesmentResponseStrtype $object): void
    {
        $this->responseStr = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::FLOW);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->flow)) {
            $this->flow->generate($doc, $node, $namespace);
        }

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseLid)) {
            $this->responseLid->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseStr)) {
            $this->responseStr->generate($doc, $node, $namespace);
        }
    }
}
