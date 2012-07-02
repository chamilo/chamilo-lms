<?php

/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * Updates the Dokeos files from version 1.6.x to version 1.8.0
 * IMPORTANT: This script has to be included by install/index.php or
 * update_courses.php (deprecated)
 *
 * SYSTEM_INSTALLATION is defined in the install/index.php (means that we are in
 * the regular upgrade process)
 *
 * When SYSTEM_INSTALLATION is defined, do for every course:
 * - create a new set of directories that reflect the new tools offered by 1.8
 * - record an item_property for each directory added
 *
 * @package chamilo.install
 */
Log::notice('Entering file');

function insert_db($db_name, $folder_name, $text)
{

    // TODO: The (global?) variable $_course has not been declared/initialized.
    $_course['dbName'] = $db_name;

    $doc_id = add_document_180($_course, '/' . $folder_name, 'folder', 0, api_ucfirst($text));
    api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'invisible', 1);
}

if (defined('SYSTEM_INSTALLATION')) {

    $sys_course_path = $pathForm . 'courses/';
    //$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
    Database::select_db($dbNameForm);
    $db_name = $dbNameForm;
    $sql = "SELECT * FROM course";
    Log::notice('Getting courses for files updates: ' . $sql);
    $result = Database::query($sql);

    while ($courses_directories = Database::fetch_array($result)) {

        $currentCourseRepositorySys = $sys_course_path . $courses_directories["directory"] . "/";
        $db_name = $courses_directories["db_name"];
        $origCRS = $updatePath . 'courses/' . $courses_directories["directory"];

        if (!is_dir($origCRS)) {
            Log::error('Directory ' . $origCRS . ' does not exist. Skipping.');
            continue;
        }
        // Move everything to the new hierarchy (from old path to new path)
        Log::notice('Renaming ' . $origCRS . ' to ' . $sys_course_path . $courses_directories["directory"]);
        rename($origCRS, $sys_course_path . $courses_directories["directory"]);
        Log::notice('Creating dirs in ' . $currentCourseRepositorySys);

        // FOLDER DOCUMENT
        // document > audio
        if (!is_dir($currentCourseRepositorySys . "document/audio")) {
            mkdir($currentCourseRepositorySys . "document/audio", $perm);
            insert_db($db_name, "audio", get_lang('Audio'));
        }

        // document > flash
        if (!is_dir($currentCourseRepositorySys . "document/flash")) {
            mkdir($currentCourseRepositorySys . "document/flash", $perm);
            insert_db($db_name, "flash", get_lang('Flash'));
        }

        // document > images
        if (!is_dir($currentCourseRepositorySys . "document/images")) {
            mkdir($currentCourseRepositorySys . "document/images", $perm);
            insert_db($db_name, "images", get_lang('Images'));
        }

        // document > video
        if (!is_dir($currentCourseRepositorySys . "document/video")) {
            mkdir($currentCourseRepositorySys . "document/video", $perm);
            insert_db($db_name, "video", get_lang('Video'));
        }

        // document > video > flv
        if (!is_dir($currentCourseRepositorySys . "document/video/flv")) {
            mkdir($currentCourseRepositorySys . "document/video/flv", $perm);
            insert_db($db_name, "video", get_lang('Video') . " (flv)");
        }

        // FOLDER UPLOAD
        // upload
        if (!is_dir($currentCourseRepositorySys . "upload")) {
            mkdir($currentCourseRepositorySys . "upload", $perm);
        }

        // upload > blog
        if (!is_dir($currentCourseRepositorySys . "upload/blog")) {
            mkdir($currentCourseRepositorySys . "upload/blog", $perm);
        }

        // upload > forum
        if (!is_dir($currentCourseRepositorySys . "upload/forum")) {
            mkdir($currentCourseRepositorySys . "upload/forum", $perm);
        }

        // upload > test
        if (!is_dir($currentCourseRepositorySys . "upload/test")) {
            mkdir($currentCourseRepositorySys . "upload/test", $perm);
        }

        // Updating index file in courses directories to change claroline/ into main/
        $content = '<?php' . "\n" .
            '$cidReq="' . $courses_directories['code'] . '";' . "\n" .
            '$dbname="' . $courses_directories['db_name'] . '";' . "\n" .
            'include("../../main/course_home/course_home.php");' . "\n" .
            '?>';
        unlink($currentCourseRepositorySys . 'index.php');
        $fp = @ fopen($currentCourseRepositorySys . 'index.php', 'w');
        if ($fp) {
            Log::error('Writing redirection file in ' . $currentCourseRepositorySys . 'index.php');
            fwrite($fp, $content);
            fclose($fp);
        } else {
            Log::error('Could not open file ' . $currentCourseRepositorySys . 'index.php');
        }
    }

    // Write the config file
    write_system_config_file(api_get_path(CONFIGURATION_PATH) . 'configuration.php');
    // Write a distribution file with the config as a backup for the admin
    write_system_config_file(api_get_path(CONFIGURATION_PATH) . 'configuration.dist.php');
    // Write a .htaccess file in the course repository
    write_courses_htaccess_file($urlAppendPath);
    copy($updatePath . 'claroline/inc/conf/add_course.conf.php', $pathForm . 'main/inc/conf/add_course.conf.php');
    copy($updatePath . 'claroline/inc/conf/course_info.conf.php', $pathForm . 'main/inc/conf/course_info.conf.php');
    copy($updatePath . 'claroline/inc/conf/mail.conf.php', $pathForm . 'main/inc/conf/mail.conf.php');
    copy($updatePath . 'claroline/inc/conf/profile.conf.inc.php', $pathForm . 'main/inc/conf/profile.conf.php');

    Log::notice('Renaming ' . $updatePath . 'claroline/upload/users to ' . $pathForm . 'main/upload/users');
    rename($updatePath . 'claroline/upload/users', $pathForm . 'main/upload/users');
    Log::notice('Renaming ' . $updatePath . 'claroline/upload/audio to ' . $pathForm . 'main/upload/audio');
    rename($updatePath . 'claroline/upload/audio', $pathForm . 'main/upload/audio');
    Log::notice('Renaming ' . $updatePath . 'claroline/upload/images to ' . $pathForm . 'main/upload/images');
    rename($updatePath . 'claroline/upload/images', $pathForm . 'main/upload/images');
    Log::notice('Renaming ' . $updatePath . 'claroline/upload/linked_files to ' . $pathForm . 'main/upload/linked_files');
    rename($updatePath . 'claroline/upload/linked_files', $pathForm . 'main/upload/linked_files');
    Log::notice('Renaming ' . $updatePath . 'claroline/upload/video to ' . $pathForm . 'main/upload/video');
    rename($updatePath . 'claroline/upload/video', $pathForm . 'main/upload/video');
} else {

    echo 'You are not allowed here !' . __FILE__;
}
