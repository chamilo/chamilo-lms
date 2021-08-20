<?php
/* For licensing terms, see /license.txt */

class CcAssesmentMetadata extends CcQuestionMetadataBase
{
    public function __construct()
    {
        //prepared default values
        $this->setSetting(CcQtiMetadata::cc_profile, CcQtiValues::exam_profile);
        $this->setSetting(CcQtiMetadata::qmd_assessmenttype, CcQtiValues::Examination);
        $this->setSetting(CcQtiMetadata::qmd_scoretype, CcQtiValues::Percentage);
        //optional empty values
        $this->setSetting(CcQtiMetadata::qmd_feedbackpermitted);
        $this->setSetting(CcQtiMetadata::qmd_hintspermitted);
        $this->setSetting(CcQtiMetadata::qmd_solutionspermitted);
        $this->setSetting(CcQtiMetadata::qmd_timelimit);
        $this->setSetting(CcQtiMetadata::cc_allow_late_submission);
        $this->setSetting(CcQtiMetadata::cc_maxattempts);
    }

    public function enableHints($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::qmd_hintspermitted, $value);
    }

    public function enableSolutions($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::qmd_solutionspermitted, $value);
    }

    public function enableLatesubmissions($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::cc_allow_late_submission, $value);
    }

    public function enableFeedback($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::qmd_feedbackpermitted, $value);
    }

    public function setTimelimit($value)
    {
        $ivalue = (int) $value;
        if (($ivalue < 0) || ($ivalue > 527401)) {
            throw new OutOfRangeException('Time limit value out of permitted range!');
        }

        $this->setSetting(CcQtiMetadata::qmd_timelimit, $value);
    }

    public function setMaxattempts($value)
    {
        $valid_values = [CcQtiValues::Examination, CcQtiValues::unlimited, 1, 2, 3, 4, 5];
        if (!in_array($value, $valid_values)) {
            throw new OutOfRangeException('Max attempts has invalid value');
        }

        $this->setSetting(CcQtiMetadata::cc_maxattempts, $value);
    }
}
