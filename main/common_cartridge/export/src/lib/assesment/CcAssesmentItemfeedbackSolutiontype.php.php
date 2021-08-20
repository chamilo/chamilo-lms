<?php
/* For licensing terms, see /license.txt */

class CcAssesmentItemfeedbackSolutiontype extends CcAssesmentItemfeedbackShintypeBase
{
    public function __construct()
    {
        parent::__construct();
        $this->tagname = CcQtiTags::solution;
    }

    public function add_solutionmaterial(CcAssesmentItemfeedbackSolutionmaterial $object)
    {
        $this->items[] = $object;
    }
}
