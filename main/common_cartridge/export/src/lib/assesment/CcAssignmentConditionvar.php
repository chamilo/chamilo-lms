<?php
/* For licensing terms, see /license.txt */

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
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::conditionvar);

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
