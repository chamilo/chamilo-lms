<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentMattext extends CcQuestionMetadataBase
{
    protected $value;

    public function __construct($value = null)
    {
        $this->setSetting(CcQtiTags::TEXTTYPE, CcQtiValues::TEXTTYPE);
        $this->setSetting(CcQtiTags::CHARSET); // , 'ascii-us');
        $this->setSetting(CcQtiTags::LABEL);
        $this->setSetting(CcQtiTags::URI);
        $this->setSetting(CcQtiTags::WIDTH);
        $this->setSetting(CcQtiTags::HEIGHT);
        $this->setSetting(CcQtiTags::X0);
        $this->setSetting(CcQtiTags::Y0);
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML);
        $this->setSettingWns(CcQtiTags::XML_SPACE, CcXmlNamespace::XML); // , 'default');
        $this->value = $value;
    }

    public function setLabel($value): void
    {
        $this->setSetting(CcQtiTags::LABEL, $value);
    }

    public function setUri($value): void
    {
        $this->setSetting(CcQtiTags::URI, $value);
    }

    public function setWidthHeight($width = null, $height = null): void
    {
        $this->setSetting(CcQtiTags::WIDTH, $width);
        $this->setSetting(CcQtiTags::HEIGHT, $height);
    }

    public function setCoor($x = null, $y = null): void
    {
        $this->setSetting(CcQtiTags::X0, $x);
        $this->setSetting(CcQtiTags::Y0, $y);
    }

    public function setLang($lang = null): void
    {
        $this->setSettingWns(CcQtiTags::XML_LANG, CcXmlNamespace::XML, $lang);
    }

    public function setContent($content, $type = CcQtiValues::TEXTTYPE, $charset = null): void
    {
        $this->value = $content;
        $this->setSetting(CcQtiTags::TEXTTYPE, $type);
        $this->setSetting(CcQtiTags::CHARSET, $charset);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $mattext = $doc->appendNewElementNsCdata($item, $namespace, CcQtiTags::MATTEXT, $this->value);
        $this->generateAttributes($doc, $mattext, $namespace);
    }
}
