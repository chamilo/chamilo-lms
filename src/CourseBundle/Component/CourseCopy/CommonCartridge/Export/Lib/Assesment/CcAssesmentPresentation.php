<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentPresentation extends CcQuestionMetadataBase
{
    protected $flow;
    protected $material;
    protected $responseLid;
    protected $responseStr;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::LABEL);
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML);
        $this->setSetting(CcQtiTags::X0);
        $this->setSetting(CcQtiTags::Y0);
        $this->setSetting(CcQtiTags::WIDTH);
        $this->setSetting(CcQtiTags::HEIGHT);
    }

    public function setLabel($value): void
    {
        $this->setSetting(CcQtiTags::LABEL, $value);
    }

    public function setLang($value): void
    {
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML, $value);
    }

    public function setCoor($x = null, $y = null): void
    {
        $this->setSetting(CcQtiTags::X0, $x);
        $this->setSetting(CcQtiTags::Y0, $y);
    }

    public function setSize($width = null, $height = null): void
    {
        $this->setSetting(CcQtiTags::WIDTH, $width);
        $this->setSetting(CcQtiTags::HEIGHT, $height);
    }

    public function setFlow(CcAssesmentFlowtype $object): void
    {
        $this->flow = $object;
    }

    public function setMaterial(CcAssesmentMaterial $object): void
    {
        $this->material = $object;
    }

    public function setResponseLid(CcResponseLidtype $object): void
    {
        $this->responseLid = $object;
    }

    public function setResponseStr(CcAssesmentResponseStrtype $object): void
    {
        $this->responseStr = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::PRESENTATION);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->flow)) {
            $this->flow->generate($doc, $node, $namespace);
        }

        if (!empty($this->material) && empty($this->flow)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseLid) && empty($this->flow)) {
            $this->responseLid->generate($doc, $node, $namespace);
        }

        if (!empty($this->responseStr) && empty($this->flow)) {
            $this->responseStr->generate($doc, $node, $namespace);
        }
    }
}
