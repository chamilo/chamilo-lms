<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentFlowLabeltype extends CcQuestionMetadataBase
{
    protected ?CcAssesmentFlowLabeltype $flowLabel = null;
    protected ?CcAssesmentResponseLabeltype $responseLabel = null;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::T_CLASS);
    }

    public function setClass($value): void
    {
        $this->setSetting(CcQtiTags::T_CLASS, $value);
    }

    public function setFlowLabel(self $object): void
    {
        $this->flowLabel = $object;
    }

    public function setResponseLabel(CcAssesmentResponseLabeltype $object): void
    {
        $this->responseLabel = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::FLOW_LABEL);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->material)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->materialRef)) {
            $this->materialRef->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseLabel)) {
            $this->responseLabel->generate($doc, $node, $namespace);
        }

        if (!empty($this->flowLabel)) {
            $this->flowLabel->generate($doc, $node, $namespace);
        }
    }
}
