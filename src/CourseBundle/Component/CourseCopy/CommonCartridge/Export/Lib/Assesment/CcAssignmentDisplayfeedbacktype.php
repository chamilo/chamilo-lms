<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssignmentDisplayfeedbacktype extends CcQuestionMetadataBase
{
    public function __construct()
    {
        $this->setSetting(CcQtiTags::FEEDBACKTYPE);
        $this->setSetting(CcQtiTags::LINKREFID);
    }

    public function setFeedbacktype($value): void
    {
        $this->setSetting(CcQtiTags::FEEDBACKTYPE, $value);
    }

    public function setLinkrefid($value): void
    {
        $this->setSetting(CcQtiTags::LINKREFID, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::DISPLAYFEEDBACK);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
