<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentMetadata extends CcQuestionMetadataBase
{
    public function __construct()
    {
        //prepared default values
        $this->setSetting(CcQtiMetadata::CC_PROFILE, CcQtiValues::EXAM_PROFILE);
        $this->setSetting(CcQtiMetadata::QMD_ASSESSMENTTYPE, CcQtiValues::EXAMINATION);
        $this->setSetting(CcQtiMetadata::QMD_SCORETYPE, CcQtiValues::PERCENTAGE);
        //optional empty values
        $this->setSetting(CcQtiMetadata::QMD_FEEDBACKPERMITTED);
        $this->setSetting(CcQtiMetadata::QMD_HINTSPERMITTED);
        $this->setSetting(CcQtiMetadata::QMD_SOLUTIONSPERMITTED);
        $this->setSetting(CcQtiMetadata::QMD_TIMELIMIT);
        $this->setSetting(CcQtiMetadata::CC_ALLOW_LATE_SUBMISSION);
        $this->setSetting(CcQtiMetadata::CC_MAXATTEMPTS);
    }

    public function enableHints($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::QMD_HINTSPERMITTED, $value);
    }

    public function enableSolutions($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::QMD_SOLUTIONSPERMITTED, $value);
    }

    public function enableLatesubmissions($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::CC_ALLOW_LATE_SUBMISSION, $value);
    }

    public function enableFeedback($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::QMD_FEEDBACKPERMITTED, $value);
    }

    public function setTimelimit($value)
    {
        $ivalue = (int) $value;
        if (($ivalue < 0) || ($ivalue > 527401)) {
            throw new OutOfRangeException('Time limit value out of permitted range!');
        }

        $this->setSetting(CcQtiMetadata::QMD_TIMELIMIT, $value);
    }

    public function setMaxattempts($value)
    {
        $valid_values = [CcQtiValues::EXAMINATION, CcQtiValues::UNLIMITED, 1, 2, 3, 4, 5];
        if (!in_array($value, $valid_values)) {
            throw new OutOfRangeException('Max attempts has invalid value');
        }

        $this->setSetting(CcQtiMetadata::CC_MAXATTEMPTS, $value);
    }
}
