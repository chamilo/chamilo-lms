<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Utils;

use Symfony\Component\Filesystem\Filesystem;

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
}
