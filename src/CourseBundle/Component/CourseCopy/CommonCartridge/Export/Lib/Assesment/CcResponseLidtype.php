<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcHelpers;
use DOMNode;

class CcResponseLidtype extends CcQuestionMetadataBase
{
    protected $tagname;
    protected $material;
    protected $materialRef;
    protected $renderChoice;
    protected $renderFib;

    public function __construct()
    {
        $this->setSetting(CcQtiTags::RCARDINALITY, CcQtiValues::SINGLE);
        $this->setSetting(CcQtiTags::RTIMING);
        $this->setSetting(CcQtiTags::IDENT, CcHelpers::uuidgen('I_'));
        $this->tagname = CcQtiTags::RESPONSE_LID;
    }

    public function setRcardinality($value): void
    {
        $this->setSetting(CcQtiTags::RCARDINALITY, $value);
    }

    public function enableRtiming($value = true): void
    {
        $this->enableSettingYesno(CcQtiTags::RTIMING, $value);
    }

    public function setIdent($value): void
    {
        $this->setSetting(CcQtiTags::IDENT, $value);
    }

    public function getIdent()
    {
        return $this->getSetting(CcQtiTags::IDENT);
    }

    public function setMaterialRef(CcAssesmentResponseMatref $object): void
    {
        $this->materialRef = $object;
    }

    public function setMaterial(CcAssesmentMaterial $object): void
    {
        $this->material = $object;
    }

    public function setRenderChoice(CcAssesmentRenderChoicetype $object): void
    {
        $this->renderChoice = $object;
    }

    public function setRenderFib(CcAssesmentRenderFibtype $object): void
    {
        $this->renderFib = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->material) && empty($this->materialRef)) {
            $this->material->generate($doc, $node, $namespace);
        }

        if (!empty($this->materialRef) && empty($this->material)) {
            $this->materialRef->generate($doc, $node, $namespace);
        }

        if (!empty($this->renderChoice) && empty($this->renderFib)) {
            $this->renderChoice->generate($doc, $node, $namespace);
        }

        if (!empty($this->renderFib) && empty($this->renderChoice)) {
            $this->renderFib->generate($doc, $node, $namespace);
        }
    }
}
