<?php
/* For licensing terms, see /license.txt */

class CcAssignmentDisplayfeedbacktype extends CcQuestionMetadataBase
{
    public function __construct()
    {
        $this->setSetting(CcQtiTags::feedbacktype);
        $this->setSetting(CcQtiTags::linkrefid);
    }

    public function setFeedbacktype($value)
    {
        $this->setSetting(CcQtiTags::feedbacktype, $value);
    }

    public function setLinkrefid($value)
    {
        $this->setSetting(CcQtiTags::linkrefid, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::displayfeedback);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
