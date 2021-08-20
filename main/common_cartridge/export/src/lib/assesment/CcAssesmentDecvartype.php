<?php
/* For licensing terms, see /license.txt */

class CcAssesmentDecvartype extends CcQuestionMetadataBase
{
    public function __construct()
    {
        $this->setSetting(CcQtiTags::varname, CcQtiValues::SCORE);
        $this->setSetting(CcQtiTags::vartype, CcQtiValues::Integer);
        $this->setSetting(CcQtiTags::minvalue);
        $this->setSetting(CcQtiTags::maxvalue);
    }

    public function setVartype($value)
    {
        $this->setSetting(CcQtiTags::vartype, $value);
    }

    public function setLimits($min = null, $max = null)
    {
        $this->setSetting(CcQtiTags::minvalue, $min);
        $this->setSetting(CcQtiTags::maxvalue, $max);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::decvar);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
