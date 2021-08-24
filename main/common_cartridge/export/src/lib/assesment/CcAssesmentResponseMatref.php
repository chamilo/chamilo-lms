<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentResponseMatref extends CcAssesmentMatref
{
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::MATERIAL_REF);
        $doc->appendNewAttributeNs($node, $namespace, CcQtiTags::LINKREFID, $this->linkref);
    }
}
