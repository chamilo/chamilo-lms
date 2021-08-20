<?php
/* For licensing terms, see /license.txt */

class CcAssignmentConditionvarOthertype extends CcQuestionMetadataBase
{
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $doc->appendNewElementNs($item, $namespace, CcQtiTags::other);
    }
}
