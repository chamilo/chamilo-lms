<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentDecvartype extends CcQuestionMetadataBase
{
    public function __construct()
    {
        $this->setSetting(CcQtiTags::VARNAME, CcQtiValues::SCORE);
        $this->setSetting(CcQtiTags::VARTYPE, CcQtiValues::INTEGER);
        $this->setSetting(CcQtiTags::MINVALUE);
        $this->setSetting(CcQtiTags::MAXVALUE);
    }

    public function setVartype($value): void
    {
        $this->setSetting(CcQtiTags::VARTYPE, $value);
    }

    public function setLimits($min = null, $max = null): void
    {
        $this->setSetting(CcQtiTags::MINVALUE, $min);
        $this->setSetting(CcQtiTags::MAXVALUE, $max);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::DECVAR);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
