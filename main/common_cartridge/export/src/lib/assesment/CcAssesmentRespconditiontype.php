<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentRespconditiontype extends CcQuestionMetadataBase
{
    protected $conditionvar = null;
    protected $setvar = [];
    protected $displayfeedback = [];

    public function __construct()
    {
        $this->setSetting(CcQtiTags::TITLE);
        $this->setSetting(CcQtiTags::CONTINUE_, CcQtiValues::NO);
    }

    public function setTitle($value)
    {
        $this->setSetting(CcQtiTags::TITLE, $value);
    }

    public function enableContinue($value = true)
    {
        $this->enableSettingYesno(CcQtiTags::CONTINUE_, $value);
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
        $node = $doc->appendNewElementNs($item, $namespace, CcQtiTags::RESPCONDITION);
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
