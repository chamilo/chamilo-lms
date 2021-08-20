<?php
/* For licensing terms, see /license.txt */

class CcAssesmentRespconditiontype extends CcQuestionMetadataBase
{
    protected $conditionvar = null;
    protected $setvar = [];
    protected $displayfeedback = [];

    public function __construct()
    {
        $this->setSetting(CcQtiTags::title);
        $this->setSetting(CcQtiTags::continue_, CcQtiValues::No);
    }

    public function setTitle($value)
    {
        $this->setSetting(CcQtiTags::title, $value);
    }

    public function enableContinue($value = true)
    {
        $this->enableSettingYesno(CcQtiTags::continue_, $value);
    }

    public function setConditionvar(CcAssignmentConditionvar $object)
    {
        $this->conditionvar = $object;
    }

    public function addSetvar(CcAssignmentSetvartype $object)
    {
        $this->setvar[] = $object;
    }

    public function addDisplayfeedback(CcAssignmentDisplayfeedbacktype $object)
    {
        $this->displayfeedback[] = $object;
    }

    public function generate(XMLGenericDocument &$doc, DOMNode &$item, $namespace)
    {
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::respcondition);
        $this->generateAttributes($doc, $node, $namespace);

        if (!empty($this->conditionvar)) {
            $this->conditionvar->generate($doc, $node, $namespace);
        }

        if (!empty($this->setvar)) {
            foreach ($this->setvar as $setvar) {
                $setvar->generate($doc, $node, $namespace);
            }
        }

        if (!empty($this->displayfeedback)) {
            foreach ($this->displayfeedback as $displayfeedback) {
                $displayfeedback->generate($doc, $node, $namespace);
            }
        }
    }
}
