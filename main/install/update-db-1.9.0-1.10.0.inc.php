<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * Update the Chamilo database from an older Chamilo version
 * Notice : This script has to be included by index.php
 *
 * @package chamilo.install
 * @todo
 * - conditional changing of tables. Currently we execute for example
 * ALTER TABLE $dbNameForm.cours
 * instructions without checking whether this is necessary.
 * - reorganise code into functions
 * @todo use database library
 */
Log::notice('Entering file');

$oldFileVersion = '1.9.0';
$newFileVersion = '1.10.0';

// Check if we come from index.php or update_courses.php - otherwise display error msg
if (defined('SYSTEM_INSTALLATION')) {

    // Check if the current Chamilo install is eligible for update
    // If not, emergency exit (back to step 1)
    if (!file_exists('../inc/conf/configuration.php')) {
        echo '<strong>'.get_lang('Error').' !</strong> '
            .'Chamilo '.implode('|', $updateFromVersion).' '.get_lang('HasNotBeenFound').'
            .<br /><br />'
            .get_lang('PleasGoBackToStep1')
            .'<p>'
            .'<button type="submit" class="back" name="step1" value="'.get_lang('Back').'">'
            .get_lang('Back')
            .'</button>'
            .'</p>'
            .'</td></tr></table></form></body></html>';
        exit ();
    }

    /*   Normal upgrade procedure: start by updating the main database */

    // If this script has been included by index.php, not update_courses.php, so
    // that we want to change the main databases as well...
    $onlyTest = false;
    if (defined('SYSTEM_INSTALLATION')) {
        include_once '../lang/english/trad4all.inc.php';

        if ($languageForm != 'english') {
            // languageForm has been escaped in index.php
            include_once '../lang/' . $languageForm . '/trad4all.inc.php';
        }

        // PRE
        $sqlFile = 'migrate-db-' . $oldFileVersion . '-' . $newFileVersion . '-pre.sql';
        Log::notice('Starting migration: '.$oldFileVersion.' - '.$newFileVersion);
        $sql = file_get_contents($sqlFile);
        $result = $manager->getConnection()->prepare($sql);
        $result->execute();

        // Do something in between

        // POST
        $sqlFile = 'migrate-db-' . $oldFileVersion . '-' . $newFileVersion . '-post.sql';
        $sql = file_get_contents($sqlFile);
        $result = $manager->getConnection()->prepare($sql);
        $result->execute();
    }
} else {
    echo 'You are not allowed here !' . __FILE__;
}
