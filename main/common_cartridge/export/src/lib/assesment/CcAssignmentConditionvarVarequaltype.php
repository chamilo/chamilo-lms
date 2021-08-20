<?php
/* For licensing terms, see /license.txt */

class CcAssignmentConditionvarVarequaltype extends CcQuestionMetadataBase
{
    protected $tagname = null;
    protected $answerid = null;

    public function __construct($value = null)
    {
        if (is_null($value)) {
            throw new InvalidArgumentException('Must not pass null!');
        }
        $this->answerid = $value;
        $this->setSetting(CcQtiTags::respident);
        $this->setSetting(CcQtiTags::case_);
        $this->tagname = CcQtiTags::varequal;
    }

    public function setRespident($value)
    {
        $this->setSetting(CcQtiTags::respident, $value);
    }

    public function enableCase($value = true)
    {
        $this->enableSettingYesno(CcQtiTags::case_, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname, $this->answerid);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
