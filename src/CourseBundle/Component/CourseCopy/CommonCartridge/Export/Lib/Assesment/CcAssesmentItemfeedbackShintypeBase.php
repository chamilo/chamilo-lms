<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentItemfeedbackShintypeBase extends CcQuestionMetadataBase
{
    protected $tagname;
    protected $items = [];

    public function __construct()
    {
        $this->setSetting(CcQtiTags::FEEDBACKSTYLE, CcQtiValues::COMPLETE);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->items)) {
            foreach ($this->items as $telement) {
                $telement->generate($doc, $node, $namespace);
            }
        }
    }
}
