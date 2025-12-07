<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

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

    public function add_material(CcAssesmentMaterial $object): void
    {
        $this->materials[] = $object;
    }

    public function add_material_ref(CcAssesmentResponseMatref $object): void
    {
        $this->material_refs[] = $object;
    }

    public function addResponseLabel(CcAssesmentResponseLabeltype $object): void
    {
        $this->responseLabels[] = $object;
    }

    public function add_flow_label($object): void
    {
        $this->flow_labels[] = $object;
    }

    public function enableShuffle($value = true): void
    {
        $this->enableSettingYesno(CcQtiTags::SHUFFLE, $value);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
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
