<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Composer;

class ScriptHandler
{
    /**
     * Dump files to the web/css folder.
     */
    public static function dumpCssFiles(): void
    {
        /*$appCss = __DIR__.'/../../../assets/libs';
        $newPath = __DIR__.'/../../../public/libs/';
        if (!is_dir($newPath)) {
            mkdir($newPath);
        }
        $fs = new Filesystem();
        $fs->mirror($appCss, $newPath, null, ['override' => true]);*/

        if (\function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Point git at the versioned hooks in tests/scripts/git-hooks so every clone runs the
     * pre-push code style check without any manual setup.
     *
     * Writes .git/config directly instead of shelling out to "git config":
     * composer usually runs inside the Docker container, where git refuses the
     * bind-mounted repository as "dubious ownership".
     */
    public static function installGitHooks(): void
    {
        $root = \dirname(__DIR__, 4);

        if (!is_dir($root.'/tests/scripts/git-hooks')) {
            return;
        }

        $gitDir = $root.'/.git';

        // In a worktree or a submodule, .git is a file pointing at the real dir.
        if (is_file($gitDir)) {
            $pointer = trim((string) file_get_contents($gitDir));

            if (!str_starts_with($pointer, 'gitdir:')) {
                return;
            }

            $gitDir = self::resolveGitPath($root, trim(substr($pointer, 7)));
        }

        // A worktree keeps the shared config one level up, next to commondir.
        if (is_file($gitDir.'/commondir')) {
            $gitDir = self::resolveGitPath($gitDir, trim((string) file_get_contents($gitDir.'/commondir')));
        }

        $configFile = $gitDir.'/config';

        if (!is_file($configFile) || !is_writable($configFile)) {
            return;
        }

        $config = (string) file_get_contents($configFile);

        // Never override a hooks path the developer set on purpose.
        if (preg_match('/^\s*hooksPath\s*=/mi', $config)) {
            return;
        }

        $config = preg_match('/^\[core\]/m', $config)
            ? preg_replace('/^\[core\]/m', "[core]\n\thooksPath = tests/scripts/git-hooks", $config, 1)
            : $config."[core]\n\thooksPath = tests/scripts/git-hooks\n";

        if (false === file_put_contents($configFile, $config)) {
            echo "Chamilo: could not enable git hooks. Run \"git config core.hooksPath tests/scripts/git-hooks\" manually.\n";

            return;
        }

        echo "Chamilo: git hooks enabled (core.hooksPath=tests/scripts/git-hooks).\n";
    }

    /**
     * Delete old Symfony folder before update (generates conflicts with composer)
     * This method also applies to 1.10 folders removed for 1.11.
     */
    public static function deleteOldFilesFrom19x(): void
    {
        $files = self::getFilesToDelete();

        foreach ($files as $file) {
            $file = __DIR__.'/../../../..'.$file;
            if (is_file($file) && is_writable($file)) {
                unlink($file);
            }
        }
    }

    public static function getFilesToDelete(): array
    {
        return [
            '/main/admin/statistics/statistics.lib.php',
            '/main/admin/add_users_to_group.php',
            '/main/admin/group_add.php',
            '/main/admin/group_edit.php',
            '/main/admin/group_list.php',
            '/main/admin/admin_page.class.php',
            '/main/admin/system_management.php',
            '/main/announcements/resources/announcements.inc.php',
            '/main/announcements/resources/announcements_email.class.php',
            '/main/auth/external_login/facebook-php-sdk/src/base_facebook.php',
            '/main/auth/external_login/facebook-php-sdk/src/facebook.php',
            '/main/auth/external_login/facebook-php-sdk/src/base_facebook.php',
            '/main/course_description/ajax_controller.class.php',
            '/main/course_description/controller.class.php',
            '/main/course_description/course_description.class.php',
            '/main/course_description/course_description_form.class.php',
            '/main/course_description/course_description_repository.class.php',
            '/main/course_description/course_description_type.class.php',
            '/main/course_description/course_description_type_repository.class.php',
            '/main/course_description/course_import.class.php',
            '/main/course_description/csv_reader.class.php',
            '/main/course_description/csv_writer.class.php',
            '/main/course_description/request.class.php',
            '/main/course_description/upload_file_form.class.php',
            '/main/calendar/agenda.inc.php',
            '/main/calendar/agenda.lib.php',
            '/main/exercice/addlimits.php',
            '/main/exercice/testcategory.class.php',
            '/main/exercice/export/scorm/scorm_export.php',
            '/main/exercice/testheaderpage.php',
            '/main/exercise/hotspot_lang_conversion.php',
            '/main/exercise/export/qti2/qti2_classes.php',
            '/main/inc/lib/main_api.lib.php',
            '/main/inc/lib/nusoap/class.soapclient.php',
            '/main/inc/lib/nusoap/nusoap.php',
            '/main/inc/lib/autoload.class.php',
            '/main/inc/autoload.inc.php',
            '/main/inc/lib/uri.class.php',
            '/main/inc/lib/db.class.php',
            '/main/inc/lib/phpmailer/test/phpmailerTest.php',
            '/main/inc/lib/xht.lib.php',
            '/main/inc/lib/xmd.lib.php',
            '/main/inc/lib/surveymanager.lib.php',
            '/main/inc/lib/entity.class.php',
            '/main/inc/lib/entity_repository.class.php',
            '/main/inc/lib/javascript.class.php',
            '/main/inc/lib/course.class.php',
            '/main/inc/lib/document.class.php',
            '/main/inc/lib/item_property.class.php',
            '/main/inc/lib/chamilo.class.php',
            '/main/inc/lib/events.lib.inc.php',
            '/main/inc/lib/current_user.class.php',
            '/main/inc/lib/current_course.class.php',
            '/main/inc/lib/response.class.php',
            '/main/inc/lib/result_set.class.php',
            '/main/inc/lib/session_handler.class.php',
            '/main/inc/lib/WCAG/WCAG_rendering.php',
            '/main/inc/lib/zip.class.php',
            '/main/inc/lib/student_publication.class.php',
            '/main/inc/lib/ajax_controller.class.php',
            '/main/inc/lib/system/closure_compiler.class.php',
            '/main/inc/lib/system/code_utilities.class.php',
            '/main/inc/lib/controller.class.php',
            '/main/inc/lib/system/text/converter.class.php',
            '/main/inc/lib/course_entity_repository.class.php',
            '/main/inc/lib/course_entity.class.php',
            '/main/inc/lib/cache.class.php',
            '/main/inc/lib/system/web/request_server.class.php',
            '/main/inc/lib/page.class.php',
            '/main/inc/lib/sortabletable.class.php',
            '/main/inc/lib/mail.lib.inc.php',
            '/main/install/i_database.class.php',
            '/main/install/install.class.php',
            '/main/inc/latex.php',
            '/main/inc/lib/formvalidator/Element/calendar_popup.php',
            '/main/inc/lib/formvalidator/Element/datepickerdate.php',
            '/main/inc/lib/formvalidator/Element/html_editor.php',
            '/main/inc/lib/formvalidator/Element/select_language.php',
            '/main/inc/lib/formvalidator/Element/select_theme.php',
            '/main/inc/lib/formvalidator/Element/style_button.php',
            '/main/inc/lib/formvalidator/Element/style_reset_button.php',
            '/main/inc/lib/formvalidator/Element/style_submit_button.php',
            '/main/inc/lib/formvalidator/Element/tbl_change.js.php',
            '/main/lp/resourcelinker.php',
            '/main/lp/resourcelinker.inc.php',
            '/main/lp/learnpath_functions.inc.php',
            '/main/lp/lp_list_search.css',
            '/main/ticket/course_user_list.php',
            '/main/ticket/report.php',
            '/main/ticket/tutor.php',
            '/main/ticket/update_report.php',
            '/main/tracking/toolaccess_details.php',
            '/main/tracking/course_access_details.php',
            '/src/DataFixtures/AppFixtures.php',
            '/web/assets/bootstrap/Gemfile',
            '/web/assets/bootstrap/Gemfile.lock',
            '/web/assets/bootstrap/Gruntfile.js',
            '/web/assets/bootstrap/package.js',
            '/web/assets/bootstrap/package.json',
            '/src/CourseBundle/Entity/CQuizQuestionRelCategory.php',
            '/src/CoreBundle/Entity/TrackEExercises.php',
            '/main/inc/lib/tablesort.lib.php',
        ];
    }

    private static function resolveGitPath(string $base, string $path): string
    {
        return str_starts_with($path, '/') ? $path : $base.'/'.$path;
    }
}
