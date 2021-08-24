<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcQuestionMetadata extends CcQuestionMetadataBase
{
    /**
     * Constructs metadata.
     *
     * @param string $profile
     *
     * @throws InvalidArgumentException
     */
    public function __construct($profile)
    {
        if (!CcQtiProfiletype::valid($profile)) {
            throw new InvalidArgumentException('Invalid profile type!');
        }
        $this->setSetting(CcQtiMetadata::CC_PROFILE, $profile);
        $this->setSetting(CcQtiMetadata::CC_QUESTION_CATEGORY);
        $this->setSetting(CcQtiMetadata::CC_WEIGHTING);
        $this->setSetting(CcQtiMetadata::QMD_SCORINGPERMITTED);
        $this->setSetting(CcQtiMetadata::QMD_COMPUTERSCORED);
    }

    public function setCategory($value)
    {
        $this->setSetting(CcQtiMetadata::CC_QUESTION_CATEGORY, $value);
    }

    public function setWeighting($value)
    {
        $this->setSetting(CcQtiMetadata::CC_WEIGHTING, $value);
    }

    public function enableScoringpermitted($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::QMD_SCORINGPERMITTED, $value);
    }

    public function enableComputerscored($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::QMD_COMPUTERSCORED, $value);
    }
}
