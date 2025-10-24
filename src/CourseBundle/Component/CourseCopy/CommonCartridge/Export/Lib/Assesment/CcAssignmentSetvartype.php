<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssignmentSetvartype extends CcQuestionMetadataBase
{
    protected $tagvalue;

    public function __construct($tagvalue = 100)
    {
        $this->setSetting(CcQtiTags::VARNAME, CcQtiValues::SCORE);
        $this->setSetting(CcQtiTags::ACTION, CcQtiValues::SET);
        $this->tagvalue = $tagvalue;
    }

    public function setVarname($value): void
    {
        $this->setSetting(CcQtiTags::VARNAME, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::SETVAR, $this->tagvalue);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
