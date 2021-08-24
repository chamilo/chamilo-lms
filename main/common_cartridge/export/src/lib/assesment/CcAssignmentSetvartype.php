<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssignmentSetvartype extends CcQuestionMetadataBase
{
    protected $tagvalue = null;

    public function __construct($tagvalue = 100)
    {
        $this->setSetting(CcQtiTags::VARNAME, CcQtiValues::SCORE);
        $this->setSetting(CcQtiTags::ACTION, CcQtiValues::SET);
        $this->tagvalue = $tagvalue;
    }

    public function setVarname($value)
    {
        $this->setSetting(CcQtiTags::VARNAME, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::SETVAR, $this->tagvalue);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
