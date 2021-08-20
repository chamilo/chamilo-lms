<?php
/* For licensing terms, see /license.txt */

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
        $this->setSetting(CcQtiMetadata::cc_profile, $profile);
        $this->setSetting(CcQtiMetadata::cc_question_category);
        $this->setSetting(CcQtiMetadata::cc_weighting);
        $this->setSetting(CcQtiMetadata::qmd_scoringpermitted);
        $this->setSetting(CcQtiMetadata::qmd_computerscored);
    }

    public function setCategory($value)
    {
        $this->setSetting(CcQtiMetadata::cc_question_category, $value);
    }

    public function setWeighting($value)
    {
        $this->setSetting(CcQtiMetadata::cc_weighting, $value);
    }

    public function enableScoringpermitted($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::qmd_scoringpermitted, $value);
    }

    public function enableComputerscored($value = true)
    {
        $this->enableSettingYesno(CcQtiMetadata::qmd_computerscored, $value);
    }
}
