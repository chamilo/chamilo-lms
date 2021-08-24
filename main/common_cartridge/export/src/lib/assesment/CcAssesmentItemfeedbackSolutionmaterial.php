<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentItemfeedbackSolutionmaterial extends CcAssesmentItemfeedbackShintmaterialBase
{
    public function __construct()
    {
        $this->tagname = CcQtiTags::SOLUTIONMATERIAL;
    }
}
