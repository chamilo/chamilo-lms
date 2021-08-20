<?php
/* For licensing terms, see /license.txt */

class CcAssesmentPresentationMaterialBase extends CcQuestionMetadataBase
{
    protected $flowmats = [];

    public function addFlowMat($object)
    {
        $this->flowmats[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::presentation_material);
        if (!empty($this->flowmats)) {
            foreach ($this->flowmats as $flowMat) {
                $flowMat->generate($doc, $node, $namespace);
            }
        }
    }
}
