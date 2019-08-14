<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS.
 *
 * Updates the Chamilo files from version 1.10.0 to version 1.11.0
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 *
 * @package chamilo.install
 */
error_log("Starting ".basename(__FILE__));

global $debug;

if (defined('SYSTEM_INSTALLATION')) {
    // Changes for 1.11.x
    // Delete directories and files that are not necessary anymore

    // The main/exercice path was moved to main/exercise, so the code from 1.11
    // should just create the new directory, and we should delete the previous
    // one to avoid the web server to use the old
    $exercisePath = api_get_path(SYS_CODE_PATH).'exercice';
    if (is_dir($exercisePath)) {
        @rrmdir($exercisePath);
    }
    // Same with main/newscorm, renamed main/lp
    $lpPath = api_get_path(SYS_CODE_PATH).'newscorm';
    if (is_dir($lpPath)) {
        @rrmdir($lpPath);
    }
    // The ticket plugin has been moved to core in 1.11
    $ticketPluginPath = api_get_path(SYS_PLUGIN_PATH).'ticket';
    if (is_dir($ticketPluginPath)) {
        @rrmdir($ticketPluginPath);
    }
    // The Skype plugin has been moved to core in 1.11
    $skypePluginPath = api_get_path(SYS_PLUGIN_PATH).'skype';
    if (is_dir($skypePluginPath)) {
        @rrmdir($skypePluginPath);
    }

    // Some entities have been removed in 1.11. Delete the corresponding files
    $entitiesToRemove = [
        api_get_path(SYS_PATH).'src/Chamilo/CoreBundle/Entity/Groups.php',
        api_get_path(SYS_PATH).'src/Chamilo/CoreBundle/Entity/GroupRelGroup.php',
        api_get_path(SYS_PATH).'src/Chamilo/CoreBundle/Entity/GroupRelTag.php',
        api_get_path(SYS_PATH).'src/Chamilo/CoreBundle/Entity/GroupRelUser.php',
    ];
    foreach ($entitiesToRemove as $entity) {
        if (file_exists($entity)) {
            $success = unlink($entity);
            if (!$success) {
                error_log('Could not delete '.$entity.', probably due to permissions. Please delete manually to avoid entities inconsistencies');
            }
        } else {
            error_log('Could not delete. It seems the file '.$entity.' does not exists.');
        }
    }

    $oldDefaultCertificatePath = api_get_path(SYS_CODE_PATH).'default_course_document/certificates/';

    if (is_dir($oldDefaultCertificatePath)) {
        @rrmdir($oldDefaultCertificatePath);
    }

    if ($debug) {
        error_log('Folders cleaned up');
    }
} else {
    echo 'You are not allowed here !'.__FILE__;
}
