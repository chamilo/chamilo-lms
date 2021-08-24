<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentSectionItem extends CcAssesmentSection
{
    protected $itemmetadata = null;
    protected $presentation = null;
    protected $resprocessing = [];
    protected $itemfeedback = [];

    public function setItemmetadata(CcAssesmentItemmetadata $object)
    {
        $this->itemmetadata = $object;
    }

    public function setPresentation(CcAssesmentPresentation $object)
    {
        $this->presentation = $object;
    }

    public function addResprocessing(CcAssesmentResprocessingtype $object)
    {
        $this->resprocessing[] = $object;
    }

    public function addItemfeedback(CcAssesmentItemfeedbacktype $object)
    {
        $this->itemfeedback[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::ITEM);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->itemmetadata)) {
            $this->itemmetadata->generate($doc, $node, $namespace);
        }

        if (!empty($this->presentation)) {
            $this->presentation->generate($doc, $node, $namespace);
        }

        if (!empty($this->resprocessing)) {
            foreach ($this->resprocessing as $resprocessing) {
                $resprocessing->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->itemfeedback)) {
            foreach ($this->itemfeedback as $itemfeedback) {
                $itemfeedback->generate($doc, $node, $namespace);
            }
        }
    }
}
