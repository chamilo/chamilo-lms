<?php
/* For licensing terms, see /license.txt */

class CcAssesmentItemfeedbackShintypeBase extends CcQuestionMetadataBase
{
    protected $tagname = null;
    protected $items = [];

    public function __construct()
    {
        $this->setSetting(CcQtiTags::feedbackstyle, CcQtiValues::Complete);
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, $this->tagname);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->items)) {
            foreach ($this->items as $telement) {
                $telement->generate($doc, $node, $namespace);
            }
        }
    }
}
