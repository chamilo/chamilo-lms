<?php
/* For licensing terms, see /license.txt */

class CcAssesmentRubricBase extends CcQuestionMetadataBase
{
    protected $material = null;

    public function setMaterial($object)
    {
        $this->material = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $rubric = $doc->appendNewElementNs($item, $namespace, CcQtiTags::rubric);
        if (!empty($this->material)) {
            $this->material->generate($doc, $rubric, $namespace);
        }
    }
}
