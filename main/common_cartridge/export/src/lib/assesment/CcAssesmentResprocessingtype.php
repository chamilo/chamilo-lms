<?php
/* For licensing terms, see /license.txt */

class CcAssesmentResprocessingtype extends CcQuestionMetadataBase
{
    protected $decvar = null;
    protected $respconditions = [];

    public function setDecvar(CcAssesmentDecvartype $object)
    {
        $this->decvar = $object;
    }

    public function addRespcondition(CcAssesmentRespconditiontype $object)
    {
        $this->respconditions[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::resprocessing);
        $outcomes = $doc->appendNewElementNs($node, $namespace, CcQtiTags::outcomes);
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
