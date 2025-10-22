<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;
use InvalidArgumentException;

class CcAssignmentConditionvarVarequaltype extends CcQuestionMetadataBase
{
    protected $tagname;
    protected $answerid;

    public function __construct($value = null)
    {
        if (null === $value) {
            throw new InvalidArgumentException('Must not pass null!');
        }
        $this->answerid = $value;
        $this->setSetting(CcQtiTags::RESPIDENT);
        $this->setSetting(CcQtiTags::CASE_);
        $this->tagname = CcQtiTags::VAREQUAL;
    }

    public function setRespident($value): void
    {
        $this->setSetting(CcQtiTags::RESPIDENT, $value);
    }

    public function enableCase($value = true): void
    {
        $this->enableSettingYesno(CcQtiTags::CASE_, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname, $this->answerid);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
