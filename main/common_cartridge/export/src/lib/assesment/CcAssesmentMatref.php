<?php
/* For licensing terms, see /license.txt */

class CcAssesmentMatref
{
    protected $linkref = null;

    public function __construct($linkref)
    {
        $this->linkref = $linkref;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::matref, $this->linkref);
        $doc->appendNewAttributeNs($node, $namespace, CcQtiTags::linkrefid, $this->linkref);
    }
}
