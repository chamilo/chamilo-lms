<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentItemfeedbackSolutiontype extends CcAssesmentItemfeedbackShintypeBase
{
    public function __construct()
    {
        parent::__construct();
        $this->tagname = CcQtiTags::SOLUTION;
    }

    public function add_solutionmaterial(CcAssesmentItemfeedbackSolutionmaterial $object)
    {
        $this->items[] = $object;
    }
}
