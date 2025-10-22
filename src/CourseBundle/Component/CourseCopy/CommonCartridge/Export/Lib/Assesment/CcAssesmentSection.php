<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils\CcHelpers;
use DOMNode;

class CcAssesmentSection extends CcQuestionMetadataBase
{
    /**
     * @var array
     */
    protected $items = [];

    public function __construct()
    {
        $this->setSetting(CcQtiTags::IDENT, CcHelpers::uuidgen('I_'));
        $this->setSetting(CcQtiTags::TITLE);
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML);
    }

    public function setIdent($value): void
    {
        $this->setSetting(CcQtiTags::IDENT, $value);
    }

    public function setTitle($value): void
    {
        $this->setSetting(CcQtiTags::TITLE, $value);
    }

    public function setLang($value): void
    {
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML, $value);
    }

    public function addItem(CcAssesmentSectionItem $object): void
    {
        $this->items[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::SECTION);
        $this->generateAttributes($doc, $node, $namespace);
        if (!empty($this->items)) {
            foreach ($this->items as $item) {
                $item->generate($doc, $node, $namespace);
            }
        }
    }
}
