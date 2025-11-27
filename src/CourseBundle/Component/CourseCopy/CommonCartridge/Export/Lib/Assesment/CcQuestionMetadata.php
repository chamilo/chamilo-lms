<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

use InvalidArgumentException;

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

    public function setCategory($value): void
    {
        $this->setSetting(CcQtiMetadata::CC_QUESTION_CATEGORY, $value);
    }

    public function setWeighting($value): void
    {
        $this->setSetting(CcQtiMetadata::CC_WEIGHTING, $value);
    }

    public function enableScoringpermitted($value = true): void
    {
        $this->enableSettingYesno(CcQtiMetadata::QMD_SCORINGPERMITTED, $value);
    }

    public function enableComputerscored($value = true): void
    {
        $this->enableSettingYesno(CcQtiMetadata::QMD_COMPUTERSCORED, $value);
    }
}
