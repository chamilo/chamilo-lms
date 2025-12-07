<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssignmentConditionvarOthertype extends CcQuestionMetadataBase
{
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $doc->appendNewElementNs($item, $namespace, CcQtiTags::OTHER);
    }
}
