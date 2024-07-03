<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

abstract class CcQtiMetadata
{
    // Assessment.
    public const QMD_ASSESSMENTTYPE = 'qmd_assessmenttype';
    public const QMD_SCORETYPE = 'qmd_scoretype';
    public const QMD_FEEDBACKPERMITTED = 'qmd_feedbackpermitted';
    public const QMD_HINTSPERMITTED = 'qmd_hintspermitted';
    public const QMD_SOLUTIONSPERMITTED = 'qmd_solutionspermitted';
    public const QMD_TIMELIMIT = 'qmd_timelimit';
    public const CC_ALLOW_LATE_SUBMISSION = 'cc_allow_late_submission';
    public const CC_MAXATTEMPTS = 'cc_maxattempts';
    public const CC_PROFILE = 'cc_profile';

    // Items.
    public const CC_WEIGHTING = 'cc_weighting';
    public const QMD_SCORINGPERMITTED = 'qmd_scoringpermitted';
    public const QMD_COMPUTERSCORED = 'qmd_computerscored';
    public const CC_QUESTION_CATEGORY = 'cc_question_category';
}
