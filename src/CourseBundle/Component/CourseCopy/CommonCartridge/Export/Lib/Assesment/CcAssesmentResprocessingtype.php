<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Base\XMLGenericDocument;
use DOMNode;

class CcAssesmentResprocessingtype extends CcQuestionMetadataBase
{
    protected $decvar;
    protected $respconditions = [];

    public function setDecvar(CcAssesmentDecvartype $object): void
    {
        $this->decvar = $object;
    }

    public function addRespcondition(CcAssesmentRespconditiontype $object): void
    {
        $this->respconditions[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace): void
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::RESPROCESSING);
        $outcomes = $doc->appendNewElementNs($node, $namespace, CcQtiTags::OUTCOMES);
        if (!empty($this->decvar)) {
            $this->decvar->generate($doc, $outcomes, $namespace);
        }
        if (!empty($this->respconditions)) {
            foreach ($this->respconditions as $rcond) {
                $rcond->generate($doc, $node, $namespace);
            }
        }
    }
}
