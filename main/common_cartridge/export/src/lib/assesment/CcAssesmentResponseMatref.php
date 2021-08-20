<?php
/* For licensing terms, see /license.txt */

class CcAssesmentResponseMatref extends CcAssesmentMatref
{
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::material_ref);
        $doc->appendNewAttributeNs($node, $namespace, CcQtiTags::linkrefid, $this->linkref);
    }
}
