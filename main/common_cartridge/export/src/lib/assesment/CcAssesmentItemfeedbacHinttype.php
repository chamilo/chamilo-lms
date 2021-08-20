<?php
/* For licensing terms, see /license.txt */

class CcAssesmentItemfeedbacHinttype extends CcAssesmentItemfeedbackShintypeBase
{
    public function __construct()
    {
        parent::__construct();
        $this->tagname = CcQtiTags::hint;
    }

    public function addHintmaterial(CcAssesmentItemfeedbackHintmaterial $object)
    {
        $this->items[] = $object;
    }
}
