<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

abstract class CcQtiMetadata
{
    // Assessment.
    public const string QMD_ASSESSMENTTYPE = 'qmd_assessmenttype';
    public const string QMD_SCORETYPE = 'qmd_scoretype';
    public const string QMD_FEEDBACKPERMITTED = 'qmd_feedbackpermitted';
    public const string QMD_HINTSPERMITTED = 'qmd_hintspermitted';
    public const string QMD_SOLUTIONSPERMITTED = 'qmd_solutionspermitted';
    public const string QMD_TIMELIMIT = 'qmd_timelimit';
    public const string CC_ALLOW_LATE_SUBMISSION = 'cc_allow_late_submission';
    public const string CC_MAXATTEMPTS = 'cc_maxattempts';
    public const string CC_PROFILE = 'cc_profile';

    // Items.
    public const string CC_WEIGHTING = 'cc_weighting';
    public const string QMD_SCORINGPERMITTED = 'qmd_scoringpermitted';
    public const string QMD_COMPUTERSCORED = 'qmd_computerscored';
    public const string CC_QUESTION_CATEGORY = 'cc_question_category';
}
