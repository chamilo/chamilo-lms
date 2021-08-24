<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

abstract class CcQtiMetadata
{
    // Assessment.
    const QMD_ASSESSMENTTYPE = 'qmd_assessmenttype';
    const QMD_SCORETYPE = 'qmd_scoretype';
    const QMD_FEEDBACKPERMITTED = 'qmd_feedbackpermitted';
    const QMD_HINTSPERMITTED = 'qmd_hintspermitted';
    const QMD_SOLUTIONSPERMITTED = 'qmd_solutionspermitted';
    const QMD_TIMELIMIT = 'qmd_timelimit';
    const CC_ALLOW_LATE_SUBMISSION = 'cc_allow_late_submission';
    const CC_MAXATTEMPTS = 'cc_maxattempts';
    const CC_PROFILE = 'cc_profile';

    // Items.
    const CC_WEIGHTING = 'cc_weighting';
    const QMD_SCORINGPERMITTED = 'qmd_scoringpermitted';
    const QMD_COMPUTERSCORED = 'qmd_computerscored';
    const CC_QUESTION_CATEGORY = 'cc_question_category';
}
