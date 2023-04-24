<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Composer;

use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DumpTheme.
 */
class ScriptHandler
{
    /**
     * Dump files to the web/css folder.
     */
    public static function dumpCssFiles()
    {
        $fs = new Filesystem();

        $sysAssets = __DIR__.'/../../../../app/Resources/public/assets';
        $webAssets = __DIR__.'/../../../../web/assets';
        $fs->mirror($sysAssets, $webAssets, null, ['override' => true, 'delete' => true]);

        $sysCss = __DIR__.'/../../../../app/Resources/public/css';
        $webCss = __DIR__.'/../../../../web/css';
        $fs->mirror($sysCss, $webCss, null, ['override' => true]);

        if ($fs->exists(__DIR__.'/../../../../web/build')) {
            $fs->remove(__DIR__.'/../../../../web/build');
        }

        if ($fs->exists(__DIR__.'/../../../../app/cache/twig')) {
            $fs->remove(__DIR__.'/../../../../app/cache/twig');
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Delete old Symfony folder before update (generates conflicts with composer)
     * This method also applies to 1.10 folders removed for 1.11.
     */
    public static function deleteOldFilesFrom19x()
    {
        $paths = self::getFoldersToDelete();

        foreach ($paths as $path) {
            if (is_dir($path) && is_writable($path)) {
                self::rmdirr($path);
            }
        }

        $files = self::getFilesToDelete();

        foreach ($files as $file) {
            if (is_file($file) && is_writable($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @return array
     */
    public static function getFoldersToDelete()
    {
        $paths = [
            __DIR__.'/../../../../app/Resources/public/assets/bootstrap/docs',
            __DIR__.'/../../../../app/Resources/public/assets/bootstrap/nuget',
            __DIR__.'/../../../../app/Resources/public/assets/bootstrap/grunt',
            __DIR__.'/../../../../app/Resources/public/assets/bootstrap/test-infra',
            __DIR__.'/../../../../archive/',
            __DIR__.'/../../../../main/announcements/resources',
            __DIR__.'/../../../../main/conference/',
            __DIR__.'/../../../../main/course_notice/',
            __DIR__.'/../../../../main/metadata/',
            __DIR__.'/../../../../main/exercice/export/qti',
            __DIR__.'/../../../../main/glossary/resources',
            __DIR__.'/../../../../main/link/resources',
            __DIR__.'/../../../../main/notebook/resources',
            __DIR__.'/../../../../main/reservation/',
            __DIR__.'/../../../../main/inc/lib/symfony/',
            __DIR__.'/../../../../main/inc/entity/',
            __DIR__.'/../../../../main/inc/lib/phpdocx/',
            __DIR__.'/../../../../main/inc/lib/phpqrcode/',
            __DIR__.'/../../../../main/inc/lib/ezpdf',
            __DIR__.'/../../../../main/inc/lib/javascript/bootstrap',
            __DIR__.'/../../../../main/inc/lib/javascript/bxslider',
            __DIR__.'/../../../../main/inc/lib/javascript/fullcalendar',
            __DIR__.'/../../../../main/inc/lib/javascript/jquery-ui',
            __DIR__.'/../../../../main/inc/lib/fckeditor',
            __DIR__.'/../../../../main/inc/lib/mpdf/',
            __DIR__.'/../../../../main/inc/lib/phpmailer',
            __DIR__.'/../../../../main/inc/lib/nanogong/',
            __DIR__.'/../../../../main/inc/lib/symfony/',
            __DIR__.'/../../../../main/inc/lib/system/media/renderer',
            __DIR__.'/../../../../main/inc/lib/system/io',
            __DIR__.'/../../../../main/inc/lib/system/net',
            __DIR__.'/../../../../main/inc/lib/system/text/',
            __DIR__.'/../../../../main/inc/lib/icalcreator/',
            __DIR__.'/../../../../main/inc/lib/getid3/',
            __DIR__.'/../../../../main/inc/lib/tools/',
            __DIR__.'/../../../../main/inc/lib/pchart/',
            __DIR__.'/../../../../main/inc/lib/pclzip/',
            __DIR__.'/../../../../main/inc/lib/htmlpurifier',
            __DIR__.'/../../../../main/pear/excelreader/',
            __DIR__.'/../../../../main/resourcelinker',
            __DIR__.'/../../../../main/newscorm',
            __DIR__.'/../../../../main/exercice',
            __DIR__.'/../../../../plugin/ticket',
            __DIR__.'/../../../../plugin/skype',
            __DIR__.'/../../../../vendor/pclzip',
            __DIR__.'/../../../../web/assets/bootstrap/grunt',
            __DIR__.'/../../../../web/assets/bootstrap/nuget',
            __DIR__.'/../../../../web/assets/bootstrap/docs',
            __DIR__.'/../../../../web/assets/bootstrap/test-infra',
        ];

        return $paths;
    }

    /**
     * @return array
     */
    public static function getFilesToDelete()
    {
        $files = [
            __DIR__.'/../../../../main/admin/statistics/statistics.lib.php',
            __DIR__.'/../../../../main/admin/add_users_to_group.php',
            __DIR__.'/../../../../main/admin/group_add.php',
            __DIR__.'/../../../../main/admin/group_edit.php',
            __DIR__.'/../../../../main/admin/group_list.php',
            __DIR__.'/../../../../main/admin/admin_page.class.php',
            __DIR__.'/../../../../main/admin/system_management.php',
            __DIR__.'/../../../../main/announcements/resources/announcements.inc.php',
            __DIR__.'/../../../../main/announcements/resources/announcements_email.class.php',
            __DIR__.'/../../../../main/auth/external_login/facebook-php-sdk/src/base_facebook.php',
            __DIR__.'/../../../../main/auth/external_login/facebook-php-sdk/src/facebook.php',
            __DIR__.'/../../../../main/auth/external_login/facebook-php-sdk/src/base_facebook.php',
            __DIR__.'/../../../../main/course_description/ajax_controller.class.php',
            __DIR__.'/../../../../main/course_description/controller.class.php',
            __DIR__.'/../../../../main/course_description/course_description.class.php',
            __DIR__.'/../../../../main/course_description/course_description_form.class.php',
            __DIR__.'/../../../../main/course_description/course_description_repository.class.php',
            __DIR__.'/../../../../main/course_description/course_description_type.class.php',
            __DIR__.'/../../../../main/course_description/course_description_type_repository.class.php',
            __DIR__.'/../../../../main/course_description/course_import.class.php',
            __DIR__.'/../../../../main/course_description/csv_reader.class.php',
            __DIR__.'/../../../../main/course_description/csv_writer.class.php',
            __DIR__.'/../../../../main/course_description/request.class.php',
            __DIR__.'/../../../../main/course_description/upload_file_form.class.php',
            __DIR__.'/../../../../main/calendar/agenda.inc.php',
            __DIR__.'/../../../../main/calendar/agenda.lib.php',
            __DIR__.'/../../../../main/exercice/addlimits.php',
            __DIR__.'/../../../../main/exercice/testcategory.class.php',
            __DIR__.'/../../../../main/exercice/export/scorm/scorm_export.php',
            __DIR__.'/../../../../main/exercice/testheaderpage.php',
            __DIR__.'/../../../../main/inc/lib/main_api.lib.php',
            //__DIR__.'/../../../../main/inc/lib/nusoap/class.soapclient.php',
            __DIR__.'/../../../../main/inc/lib/nusoap/nusoap.php',
            __DIR__.'/../../../../main/inc/lib/autoload.class.php',
            __DIR__.'/../../../../main/inc/autoload.inc.php',
            __DIR__.'/../../../../main/inc/lib/uri.class.php',
            __DIR__.'/../../../../main/inc/lib/db.class.php',
            __DIR__.'/../../../../main/inc/lib/xht.lib.php',
            __DIR__.'/../../../../main/inc/lib/xmd.lib.php',
            __DIR__.'/../../../../main/inc/lib/entity.class.php',
            __DIR__.'/../../../../main/inc/lib/entity_repository.class.php',
            __DIR__.'/../../../../main/inc/lib/javascript.class.php',
            __DIR__.'/../../../../main/inc/lib/course.class.php',
            __DIR__.'/../../../../main/inc/lib/document.class.php',
            __DIR__.'/../../../../main/inc/lib/item_property.class.php',
            __DIR__.'/../../../../main/inc/lib/chamilo.class.php',
            __DIR__.'/../../../../main/inc/lib/events.lib.inc.php',
            __DIR__.'/../../../../main/inc/lib/current_user.class.php',
            __DIR__.'/../../../../main/inc/lib/current_course.class.php',
            __DIR__.'/../../../../main/inc/lib/response.class.php',
            __DIR__.'/../../../../main/inc/lib/result_set.class.php',
            __DIR__.'/../../../../main/inc/lib/session_handler.class.php',
            __DIR__.'/../../../../main/inc/lib/WCAG/WCAG_rendering.php',
            __DIR__.'/../../../../main/inc/lib/zip.class.php',
            __DIR__.'/../../../../main/inc/lib/student_publication.class.php',
            __DIR__.'/../../../../main/inc/lib/ajax_controller.class.php',
            __DIR__.'/../../../../main/inc/lib/system/closure_compiler.class.php',
            __DIR__.'/../../../../main/inc/lib/system/code_utilities.class.php',
            __DIR__.'/../../../../main/inc/lib/controller.class.php',
            __DIR__.'/../../../../main/inc/lib/system/text/converter.class.php',
            __DIR__.'/../../../../main/inc/lib/course_entity_repository.class.php',
            __DIR__.'/../../../../main/inc/lib/course_entity.class.php',
            __DIR__.'/../../../../main/inc/lib/cache.class.php',
            __DIR__.'/../../../../main/inc/lib/system/web/request_server.class.php',
            __DIR__.'/../../../../main/inc/lib/page.class.php',
            __DIR__.'/../../../../main/inc/lib/sortabletable.class.php',
            __DIR__.'/../../../../main/inc/lib/mail.lib.inc.php',
            __DIR__.'/../../../../main/install/i_database.class.php',
            __DIR__.'/../../../../main/install/install.class.php',
            __DIR__.'/../../../../main/inc/latex.php',
            __DIR__.'/../../../../main/inc/lib/formvalidator/Element/calendar_popup.php',
            __DIR__.'/../../../../main/inc/lib/formvalidator/Element/datepickerdate.php',
            __DIR__.'/../../../../main/inc/lib/formvalidator/Element/html_editor.php',
            __DIR__.'/../../../../main/inc/lib/formvalidator/Element/select_language.php',
            __DIR__.'/../../../../main/inc/lib/formvalidator/Element/select_theme.php',
            __DIR__.'/../../../../main/inc/lib/formvalidator/Element/style_button.php',
            __DIR__.'/../../../../main/inc/lib/formvalidator/Element/style_reset_button.php',
            __DIR__.'/../../../../main/inc/lib/formvalidator/Element/style_submit_button.php',
            __DIR__.'/../../../../main/inc/lib/formvalidator/Element/tbl_change.js.php',
            __DIR__.'/../../../../main/inc/lib/SequenceManager.php',
            __DIR__.'/../../../../main/inc/lib/SequenceResourceManager.php',
            __DIR__.'/../../../../main/lp/resourcelinker.php',
            __DIR__.'/../../../../main/lp/resourcelinker.inc.php',
            __DIR__.'/../../../../main/lp/learnpath_functions.inc.php',
            __DIR__.'/../../../../main/lp/lp_list_search.css',
            __DIR__.'/../../../../main/tracking/toolaccess_details.php',
            __DIR__.'/../../../../main/tracking/course_access_details.php',
            __DIR__.'/../../../../src/Chamilo/CoreBundle/Entity/GroupRelGroup.php',
            __DIR__.'/../../../../src/Chamilo/CoreBundle/Entity/GroupRelTag.php',
            __DIR__.'/../../../../src/Chamilo/CoreBundle/Entity/GroupRelUser.php',
            __DIR__.'/../../../../src/Chamilo/CoreBundle/Entity/Groups.php',
            __DIR__.'/../../../../src/Chamilo/UserBundle/Entity/Repository/UserRepository.php',
            __DIR__.'/../../../../src/Chamilo/CoreBundle/Entity/Manager/SequenceResourceManager.php',
            __DIR__.'/../../../../app/Resources/public/assets/bootstrap/Gemfile',
            __DIR__.'/../../../../app/Resources/public/assets/bootstrap/Gemfile.lock',
            __DIR__.'/../../../../app/Resources/public/assets/bootstrap/Gruntfile.js',
            __DIR__.'/../../../../app/Resources/public/assets/bootstrap/package.js',
            __DIR__.'/../../../../app/Resources/public/assets/bootstrap/package.json',
            __DIR__.'/../../../../web/assets/bootstrap/Gemfile',
            __DIR__.'/../../../../web/assets/bootstrap/Gemfile.lock',
            __DIR__.'/../../../../web/assets/bootstrap/Gruntfile.js',
            __DIR__.'/../../../../web/assets/bootstrap/package.js',
            __DIR__.'/../../../../web/assets/bootstrap/package.json',
        ];

        return $files;
    }

    /**
     * Update the basis css files.
     * Avoid use the ScriptHandler::dumpCssFiles.
     */
    public static function updateCss()
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
     * @throws \Exception
     */
    public static function generateDoctrineProxies()
    {
        $configurationFile = __DIR__.'/../../../../app/config/configuration.php';

        if (!is_file($configurationFile)) {
            echo 'app/config/configuration.php file does not exits.'.PHP_EOL;

            return;
        }

        $helperSet = require __DIR__.'/../../../../cli-config.php';

        $application = ConsoleRunner::createApplication(
            $helperSet,
            [
                new GenerateCommand(),
            ]
        );

        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'orm:generate:proxies',
        ]);
        $output = new BufferedOutput();

        $application->run($input, $output);

        echo $output->fetch();
    }

    /**
     * Copied from chamilo rmdirr function.
     *
     * @param string     $dirname
     * @param bool|false $delete_only_content_in_folder
     * @param bool|false $strict
     *
     * @return bool
     */
    private static function rmdirr($dirname, $delete_only_content_in_folder = false, $strict = false)
    {
        $res = true;

        // A sanity check.
        if (!file_exists($dirname)) {
            return false;
        }
        // Simple delete for a file.
        if (is_file($dirname) || is_link($dirname)) {
            $res = unlink($dirname);

            return $res;
        }

        // Loop through the folder.
        $dir = dir($dirname);
        // A sanity check.
        $is_object_dir = is_object($dir);
        if ($is_object_dir) {
            while (false !== $entry = $dir->read()) {
                // Skip pointers.
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                // Recurse.
                if ($strict) {
                    $result = self::rmdirr("$dirname/$entry");
                    if ($result == false) {
                        $res = false;
                        break;
                    }
                } else {
                    self::rmdirr("$dirname/$entry");
                }
            }
        }

        // Clean up.
        if ($is_object_dir) {
            $dir->close();
        }

        if ($delete_only_content_in_folder == false) {
            $res = rmdir($dirname);
        }

        return $res;
    }
}
