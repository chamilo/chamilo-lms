<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentItemfeedbacHinttype extends CcAssesmentItemfeedbackShintypeBase
{
    public function __construct()
    {
        parent::__construct();
        $this->tagname = CcQtiTags::HINT;
    }

    public function addHintmaterial(CcAssesmentItemfeedbackHintmaterial $object)
    {
        $this->items[] = $object;
    }
}
