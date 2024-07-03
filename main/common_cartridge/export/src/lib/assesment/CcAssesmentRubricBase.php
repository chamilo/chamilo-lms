<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentRubricBase extends CcQuestionMetadataBase
{
    protected $material = null;

    public function setMaterial($object)
    {
        $this->material = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $rubric = $doc->appendNewElementNs($item, $namespace, CcQtiTags::RUBRIC);
        if (!empty($this->material)) {
            $this->material->generate($doc, $rubric, $namespace);
        }
    }
}
