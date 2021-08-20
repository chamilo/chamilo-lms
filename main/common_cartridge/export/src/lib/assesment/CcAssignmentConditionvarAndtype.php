<?php
/* For licensing terms, see /license.txt */

class CcAssignmentConditionvarAndtype extends CcQuestionMetadataBase
{
    protected $nots = [];
    protected $varequals = [];

    public function setNot(CcAssignmentConditionvarVarequaltype $object)
    {
        $this->nots[] = $object;
    }

    public function setVarequal(CcAssignmentConditionvarVarequaltype $object)
    {
        $this->varequals[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::and_);
        if (!empty($this->nots)) {
            foreach ($this->nots as $notv) {
                $not = $doc->appendNewElementNs($node, $namespace, CcQtiTags::not_);
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
