<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentItemfeedbackShintmaterialBase extends CcQuestionMetadataBase
{
    protected $tagname = null;
    protected $flowMats = [];
    protected $materials = [];

    public function addFlowMat(CcAssesmentFlowMattype $object)
    {
        $this->flowMats[] = $object;
    }

    public function add_material(CcAssesmentMaterial $object)
    {
        $this->materials[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname);

        if (!empty($this->flowMats)) {
            foreach ($this->flowMats as $flowMat) {
                $flowMat->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->materials)) {
            foreach ($this->materials as $material) {
                $material->generate($doc, $node, $namespace);
            }
        }
    }
}
