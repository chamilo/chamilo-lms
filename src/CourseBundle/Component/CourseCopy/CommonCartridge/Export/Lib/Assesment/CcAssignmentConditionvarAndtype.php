<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssignmentConditionvarAndtype extends CcQuestionMetadataBase
{
    protected $nots = [];
    protected $varequals = [];

    public function setNot(CcAssignmentConditionvarVarequaltype $object): void
    {
        $this->nots[] = $object;
    }

    public function setVarequal(CcAssignmentConditionvarVarequaltype $object): void
    {
        $this->varequals[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::AND_);
        if (!empty($this->nots)) {
            foreach ($this->nots as $notv) {
                $not = $doc->appendNewElementNs($node, $namespace, CcQtiTags::NOT_);
                $notv->generate($doc, $not, $namespace);
            }
        }

        if (!empty($this->varequals)) {
            foreach ($this->varequals as $varequal) {
                $varequal->generate($doc, $node, $namespace);
            }
        }
    }
}
