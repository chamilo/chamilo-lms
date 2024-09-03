<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentRenderFibtype extends CcQuestionMetadataBase
{
    protected $materials = [];
    protected $material_refs = [];
    protected $responseLabels = [];
    protected $flow_labels = [];

    public function __construct()
    {
        $this->setSetting(CcQtiTags::ENCODING);
        $this->setSetting(CcQtiTags::CHARSET);
        $this->setSetting(CcQtiTags::ROWS);
        $this->setSetting(CcQtiTags::COLUMNS);
        $this->setSetting(CcQtiTags::MAXCHARS);
        $this->setSetting(CcQtiTags::MINNUMBER);
        $this->setSetting(CcQtiTags::MAXNUMBER);
        $this->setSetting(CcQtiTags::PROMPT, CcQtiValues::BOX);
        $this->setSetting(CcQtiTags::FIBTYPE, CcQtiValues::STRING);
        $this->qtype = CcQtiProfiletype::FIELD_ENTRY;
    }

    public function add_material(CcAssesmentMaterial $object)
    {
        $this->materials[] = $object;
    }

    public function add_material_ref(CcAssesmentResponseMatref $object)
    {
        $this->material_refs[] = $object;
    }

    public function addResponseLabel(CcAssesmentResponseLabeltype $object)
    {
        $this->responseLabels[] = $object;
    }

    public function add_flow_label($object)
    {
        $this->flow_labels[] = $object;
    }

    public function enableShuffle($value = true)
    {
        $this->enableSettingYesno(CcQtiTags::SHUFFLE, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::RENDER_FIB);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->materials)) {
            foreach ($this->materials as $mattag) {
                $mattag->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->material_refs)) {
            foreach ($this->material_refs as $matreftag) {
                $matreftag->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->responseLabels)) {
            foreach ($this->responseLabels as $resplabtag) {
                $resplabtag->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->flow_labels)) {
            foreach ($this->flow_labels as $flowlabtag) {
                $flowlabtag->generate($doc, $node, $namespace);
            }
        }
    }
}
