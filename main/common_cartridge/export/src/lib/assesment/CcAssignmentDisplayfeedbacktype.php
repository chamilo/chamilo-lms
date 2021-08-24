<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssignmentDisplayfeedbacktype extends CcQuestionMetadataBase
{
    public function __construct()
    {
        $this->setSetting(CcQtiTags::FEEDBACKTYPE);
        $this->setSetting(CcQtiTags::LINKREFID);
    }

    public function setFeedbacktype($value)
    {
        $this->setSetting(CcQtiTags::FEEDBACKTYPE, $value);
    }

    public function setLinkrefid($value)
    {
        $this->setSetting(CcQtiTags::LINKREFID, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::DISPLAYFEEDBACK);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
