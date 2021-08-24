<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentMatref
{
    protected $linkref = null;

    public function __construct($linkref)
    {
        $this->linkref = $linkref;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::MATREF, $this->linkref);
        $doc->appendNewAttributeNs($node, $namespace, CcQtiTags::LINKREFID, $this->linkref);
    }
}
