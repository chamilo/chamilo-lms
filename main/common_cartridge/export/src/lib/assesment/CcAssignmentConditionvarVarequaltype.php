<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

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
        $this->setSetting(CcQtiTags::RESPIDENT);
        $this->setSetting(CcQtiTags::CASE_);
        $this->tagname = CcQtiTags::VAREQUAL;
    }

    public function setRespident($value)
    {
        $this->setSetting(CcQtiTags::RESPIDENT, $value);
    }

    public function enableCase($value = true)
    {
        $this->enableSettingYesno(CcQtiTags::CASE_, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname, $this->answerid);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
