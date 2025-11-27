<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssignmentConditionvar extends CcQuestionMetadataBase
{
    protected $and;
    protected $other;
    protected $varequal = [];
    protected $varsubstring;

    public function setAnd(CcAssignmentConditionvarAndtype $object): void
    {
        $this->and = $object;
    }

    public function setOther(CcAssignmentConditionvarOthertype $object): void
    {
        $this->other = $object;
    }

    public function setVarequal(CcAssignmentConditionvarVarequaltype $object): void
    {
        $this->varequal[] = $object;
    }

    public function setVarsubstring(CcAssignmentConditionvarVarsubstringtype $object): void
    {
        $this->varsubstring = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::CONDITIONVAR);

        if (!empty($this->and)) {
            $this->and->generate($doc, $node, $namespace);
        }

        if (!empty($this->other)) {
            $this->other->generate($doc, $node, $namespace);
        }

        if (!empty($this->varequal)) {
            foreach ($this->varequal as $varequal) {
                $varequal->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->varsubstring)) {
            $this->varsubstring->generate($doc, $node, $namespace);
        }
    }
}
