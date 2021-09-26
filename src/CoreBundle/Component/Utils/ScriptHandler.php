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
        $paths = self::getFoldersToDelete();

        foreach ($paths as $path) {
            $path = __DIR__.'/../../../..'.$path;
            if (is_dir($path) && is_writable($path)) {
                self::rmdirr($path);
            }
        }

        $files = self::getFilesToDelete();

        foreach ($files as $file) {
            $file = __DIR__.'/../../../..'.$file;
            if (is_file($file) && is_writable($file)) {
                unlink($file);
            }
        }
    }

    public static function getFoldersToDelete(): array
    {
        return [
            '/app/Resources/public/assets/bootstrap/docs',
            '/app/Resources/public/assets/bootstrap/nuget',
            '/app/Resources/public/assets/bootstrap/grunt',
            '/app/Resources/public/assets/bootstrap/test-infra',
            '/archive/',
            '/main/announcements/resources',
            '/main/conference/',
            '/main/course_notice/',
            '/main/metadata/',
            '/main/exercice/export/qti',
            '/main/glossary/resources',
            '/main/link/resources',
            '/main/notebook/resources',
            '/main/reservation/',
            '/main/inc/lib/symfony/',
            '/main/inc/entity/',
            '/main/inc/lib/phpdocx/',
            '/main/inc/lib/phpqrcode/',
            '/main/inc/lib/ezpdf',
            '/main/inc/lib/javascript/bootstrap',
            '/main/inc/lib/javascript/bxslider',
            '/main/inc/lib/javascript/fullcalendar',
            '/main/inc/lib/javascript/jquery-ui',
            '/main/inc/lib/fckeditor',
            '/main/inc/lib/mpdf/',
            '/main/inc/lib/nanogong/',
            '/main/inc/lib/phpseclib/',
            '/main/inc/lib/phpmailer/',
            '/main/inc/lib/symfony/',
            '/main/inc/lib/system/media/renderer',
            '/main/inc/lib/system/io',
            '/main/inc/lib/system/net',
            '/main/inc/lib/system/text/',
            '/main/inc/lib/system/portfolio/',
            '/main/inc/lib/icalcreator/',
            '/main/inc/lib/getid3/',
            '/main/inc/lib/tools/',
            '/main/inc/lib/pchart/',
            '/main/inc/lib/pclzip/',
            '/main/inc/lib/htmlpurifier',
            '/main/pear/excelreader/',
            '/main/resourcelinker',
            '/main/newscorm',
            '/main/exercice',
            '/plugin/ticket',
            '/plugin/skype',
            '/vendor/pclzip',
            '/web/assets/bootstrap/grunt',
            '/web/assets/bootstrap/nuget',
            '/web/assets/bootstrap/docs',
            '/web/assets/bootstrap/test-infra',
        ];
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
            '/src/Chamilo/CoreBundle/Entity/GroupRelGroup.php',
            '/src/Chamilo/CoreBundle/Entity/GroupRelTag.php',
            '/src/Chamilo/CoreBundle/Entity/GroupRelUser.php',
            '/src/Chamilo/CoreBundle/Entity/Groups.php',
            '/src/Chamilo/UserBundle/Entity/Repository/UserRepository.php',
            '/app/Resources/public/assets/bootstrap/Gemfile',
            '/app/Resources/public/assets/bootstrap/Gemfile.lock',
            '/app/Resources/public/assets/bootstrap/Gruntfile.js',
            '/app/Resources/public/assets/bootstrap/package.js',
            '/app/Resources/public/assets/bootstrap/package.json',
            '/web/assets/bootstrap/Gemfile',
            '/web/assets/bootstrap/Gemfile.lock',
            '/web/assets/bootstrap/Gruntfile.js',
            '/web/assets/bootstrap/package.js',
            '/web/assets/bootstrap/package.json',
            '/src/CourseBundle/Entity/CQuizQuestionRelCategory.php',
            '/main/inc/lib/tablesort.lib.php',
        ];
    }

    /**
     * Update the basis css files.
     * Avoid use the ScriptHandler::dumpCssFiles.
     */
    public static function updateCss(): void
    {
        $appCss = __DIR__.'/../../../../app/Resources/public/css/';
        $newPath = __DIR__.'/../../../../web/css/';
        $cssFiles = [
            'base.css',
            'chat.css',
            'document.css',
            'editor_content.css',
            'markdown.css',
            'print.css',
            'responsive.css',
            'scorm.css',
        ];

        $fs = new Filesystem();

        foreach ($cssFiles as $file) {
            $fs->copy($appCss.$file, $newPath.$file, true);
        }
    }

    /**
     * Copied from chamilo rmdirr function.
     *
     * @return bool
     */
    private static function rmdirr(string $dirname, bool $delete_only_content_in_folder = false, bool $strict = false)
    {
        $res = true;

        // A sanity check.
        if (!file_exists($dirname)) {
            return false;
        }
        // Simple delete for a file.
        if (is_file($dirname) || is_link($dirname)) {
            return unlink($dirname);
        }

        // Loop through the folder.
        $dir = dir($dirname);
        // A sanity check.
        $is_object_dir = \is_object($dir);
        if ($is_object_dir) {
            while (false !== $entry = $dir->read()) {
                // Skip pointers.
                if ('.' === $entry || '..' === $entry) {
                    continue;
                }

                // Recurse.
                if ($strict) {
                    $result = self::rmdirr(sprintf('%s/%s', $dirname, $entry));
                    if (!$result) {
                        $res = false;

                        break;
                    }
                } else {
                    self::rmdirr(sprintf('%s/%s', $dirname, $entry));
                }
            }
        }

        // Clean up.
        if ($is_object_dir) {
            $dir->close();
        }

        if (!$delete_only_content_in_folder) {
            $res = rmdir($dirname);
        }

        return $res;
    }
}
