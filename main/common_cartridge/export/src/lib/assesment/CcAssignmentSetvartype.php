<?php
/* For licensing terms, see /license.txt */

class CcAssignmentSetvartype extends CcQuestionMetadataBase
{
    protected $tagvalue = null;

    public function __construct($tagvalue = 100)
    {
        $this->setSetting(CcQtiTags::varname, CcQtiValues::SCORE);
        $this->setSetting(CcQtiTags::action, CcQtiValues::Set);
        $this->tagvalue = $tagvalue;
    }

    public function setVarname($value)
    {
        $this->setSetting(CcQtiTags::varname, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::setvar, $this->tagvalue);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
