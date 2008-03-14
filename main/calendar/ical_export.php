<?php // $id: $
/**
 * This file exclusively export calendar items to iCal or similar formats
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
/**
 * Initialisation
 */
// name of the language file that needs to be included
$language_file = 'agenda';
// we are not inside a course, so we reset the course id
$cidReset = true;
// setting the global file that gets the general configuration, the databases, the languages, ...
require ('../inc/global.inc.php');
$this_section = SECTION_MYAGENDA;
api_block_anonymous_users();
require (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');
// setting the name of the tool
$nameTools = get_lang('MyAgenda');
 
?>