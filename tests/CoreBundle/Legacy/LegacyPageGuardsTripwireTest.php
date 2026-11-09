<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use PHPUnit\Framework\TestCase;

/**
 * Tripwire for the access-control and token guards of the legacy pages under public/main and public/plugin
 * that are plain scripts (they call exit/api_not_allowed at include time, so they cannot be unit-tested in
 * this harness; the helper-level guards have their own *AccessControlTest). Each page must keep AT LEAST the
 * number of guard calls it has now: removing one (a token check, a course-ownership check, an
 * api_not_allowed) lowers the count and fails the test. A deliberate refactor that moves a guard into a
 * helper must lower the number here in the same commit. It does not prove the guard is correct, only that
 * it is still there: the behaviour itself belongs to a Playwright scenario.
 */
final class LegacyPageGuardsTripwireTest extends TestCase
{
    private const GUARD = '/Security::check_token\(|->protect\(\)|api_not_allowed\(|api_protect_admin_script\(|protectElementInCurrentCourse\(|BelongsToCourse\(|canManageCourseCertificates\(|categoryInCourse\(|workActionTokenIsValid\(/';

    /**
     * @return array<string, array{string, int}>
     */
    public static function pages(): array
    {
        $min = [
            'public/main/admin/languages.php' => 8,
            'public/main/attendance/index.php' => 45,
            'public/main/dropbox/dropbox_download.php' => 5,
            'public/main/dropbox/index.php' => 13,
            'public/main/dropbox/recover_dropbox_files.php' => 3,
            'public/main/dropbox/update.php' => 4,
            'public/main/exercise/admin.php' => 11,
            'public/main/exercise/category.php' => 9,
            'public/main/exercise/exercise.php' => 9,
            'public/main/exercise/exercise_admin.php' => 3,
            'public/main/exercise/exercise_history.php' => 2,
            'public/main/exercise/exercise_question_reminder.php' => 3,
            'public/main/exercise/exercise_submit.php' => 5,
            'public/main/exercise/hotspot_actionscript.as.php' => 4,
            'public/main/exercise/hotspot_actionscript_admin.as.php' => 5,
            'public/main/exercise/hotspot_answers.as.php' => 6,
            'public/main/exercise/hotspot_save.inc.php' => 1,
            'public/main/exercise/live_stats.php' => 4,
            'public/main/exercise/overview.php' => 5,
            'public/main/exercise/question_pool.php' => 12,
            'public/main/exercise/question_stats.php' => 5,
            'public/main/exercise/recalculate.php' => 1,
            'public/main/exercise/recalculate_all.php' => 4,
            'public/main/exercise/result.php' => 7,
            'public/main/exercise/stats.php' => 4,
            'public/main/exercise/tests_category.php' => 10,
            'public/main/glossary/index.php' => 10,
            'public/main/gradebook/gradebook_add_cat.php' => 2,
            'public/main/gradebook/gradebook_add_eval.php' => 3,
            'public/main/gradebook/gradebook_add_link.php' => 4,
            'public/main/gradebook/gradebook_add_link_select_course.php' => 1,
            'public/main/gradebook/gradebook_add_result.php' => 1,
            'public/main/gradebook/gradebook_display_certificate.php' => 7,
            'public/main/gradebook/gradebook_display_summary.php' => 2,
            'public/main/gradebook/gradebook_edit_all.php' => 2,
            'public/main/gradebook/gradebook_edit_cat.php' => 2,
            'public/main/gradebook/gradebook_edit_eval.php' => 6,
            'public/main/gradebook/gradebook_edit_link.php' => 6,
            'public/main/gradebook/gradebook_edit_result.php' => 5,
            'public/main/gradebook/gradebook_flatview.php' => 2,
            'public/main/gradebook/gradebook_scoring_system.php' => 3,
            'public/main/gradebook/gradebook_showlog_eval.php' => 1,
            'public/main/gradebook/gradebook_showlog_link.php' => 1,
            'public/main/gradebook/gradebook_statistics.php' => 2,
            'public/main/gradebook/gradebook_view_result.php' => 7,
            'public/main/gradebook/index.php' => 33,
            'public/main/gradebook/lib/GradebookUtils.php' => 16,
            'public/main/gradebook/personal_stats.php' => 2,
            'public/main/gradebook/user_stats.php' => 2,
            'public/main/inc/ajax/exercise.ajax.php' => 8,
            'public/main/inc/ajax/gradebook.ajax.php' => 4,
            'public/main/inc/ajax/model.ajax.php' => 8,
            'public/main/inc/ajax/work.ajax.php' => 4,
            'public/main/inc/lib/attendance.lib.php' => 2,
            'public/main/inc/lib/exercise.lib.php' => 6,
            'public/main/plagiarism/compilatio/upload.php' => 1,
            'public/main/survey/create_meeting.php' => 2,
            'public/main/survey/create_new_survey.php' => 4,
            'public/main/survey/edit_meeting.php' => 4,
            'public/main/survey/meeting.php' => 4,
            'public/main/survey/preview.php' => 3,
            'public/main/survey/question.php' => 7,
            'public/main/survey/survey.php' => 10,
            'public/main/survey/survey_invite.php' => 3,
            'public/main/survey/survey_list.php' => 12,
            'public/main/survey/survey_question.php' => 1,
            'public/main/work/work.lib.php' => 12,
            'public/main/work/work_list_all.php' => 10,
            'public/plugin/CourseLegal/start.php' => 3,
            'public/plugin/CourseLegal/user_list.php' => 6,
            'public/plugin/CustomCertificate/src/customcertificate.ajax.php' => 6,
            'public/plugin/Justification/cron.php' => 1,
            'public/plugin/LearningCalendar/ajax.php' => 3,
            'public/plugin/LearningCalendar/calendar.php' => 1,
            'public/plugin/LearningCalendar/start.php' => 8,
        ];
        $rows = [];
        foreach ($min as $file => $count) {
            $rows[$file] = [$file, $count];
        }

        return $rows;
    }

    /**
     * @dataProvider pages
     */
    public function testThePageKeepsItsGuards(string $file, int $minimum): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../../'.$file);
        $this->assertNotSame('', $source, $file.' is missing');
        $this->assertGreaterThanOrEqual($minimum, preg_match_all(self::GUARD, $source), $file.' lost a guard call');
    }

    public function testTheStatisticsAjaxScopesTheSessionsByDateChartsToTheCurrentUrl(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../../public/main/inc/ajax/statistics.ajax.php');
        $this->assertStringContainsString('SELECT session_id FROM \'.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION)', $source);
    }

    public function testTheLearningPathSearchLinksEncodeTheEchoedQuery(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../../public/main/lp/lp_list_search.php');
        $this->assertGreaterThanOrEqual(2, substr_count($source, 'htmlspecialchars($get_params, ENT_QUOTES)'));
    }

    public function testTheCompilatioUploadScriptStaysDisabled(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../../public/main/plagiarism/compilatio/upload.php');
        $this->assertMatchesRegularExpression('/^api_not_allowed\(true\);\s*exit;/m', $source);
    }
}
