<?php
/* For licensing terms, see /license.txt */

class CcAssesmentMatbreak
{
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $doc->appendNewElementNs($item, $namespace, CcQtiTags::matbreak);
    }
}
