<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentMatbreak
{
    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $doc->appendNewElementNs($item, $namespace, CcQtiTags::MATBREAK);
    }
}
