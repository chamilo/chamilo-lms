<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentDecvartype extends CcQuestionMetadataBase
{
    public function __construct()
    {
        $this->setSetting(CcQtiTags::VARNAME, CcQtiValues::SCORE);
        $this->setSetting(CcQtiTags::VARTYPE, CcQtiValues::INTEGER);
        $this->setSetting(CcQtiTags::MINVALUE);
        $this->setSetting(CcQtiTags::MAXVALUE);
    }

    public function setVartype($value)
    {
        $this->setSetting(CcQtiTags::VARTYPE, $value);
    }

    public function setLimits($min = null, $max = null)
    {
        $this->setSetting(CcQtiTags::MINVALUE, $min);
        $this->setSetting(CcQtiTags::MAXVALUE, $max);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::DECVAR);
        $this->generateAttributes($doc, $node, $namespace);
    }
}
