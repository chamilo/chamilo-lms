<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssignmentConditionvar extends CcQuestionMetadataBase
{
    protected $and = null;
    protected $other = null;
    protected $varequal = [];
    protected $varsubstring = null;

    public function setAnd(CcAssignmentConditionvarAndtype $object)
    {
        $this->and = $object;
    }

    public function setOther(CcAssignmentConditionvarOthertype $object)
    {
        $this->other = $object;
    }

    public function setVarequal(CcAssignmentConditionvarVarequaltype $object)
    {
        $this->varequal[] = $object;
    }

    public function setVarsubstring(CcAssignmentConditionvarVarsubstringtype $object)
    {
        $this->varsubstring = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
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
