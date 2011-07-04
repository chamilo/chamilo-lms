<?php
/* For licensing terms, see /license.txt */

/**
 * Create a backup.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */

// Language files that need to be included
$language_file = array('exercice', 'coursebackup', 'admin');

// Including the global initialization file
require '../inc/global.inc.php';

// Check access rights (only teachers allowed)
if (!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = array('url' => '../course_info/maintenance.php', 'name' => get_lang('Maintenance'));

// Displaying the header
$nameTools = get_lang('Backup');
Display::display_header($nameTools);

// Display the tool title
api_display_tool_title($nameTools);

/*		MAIN CODE	*/

?>
  <ul>
    <li><a href="create_backup.php"><?php echo get_lang('CreateBackup')  ?></a><br/>
    <?php echo get_lang('CreateBackupInfo'); ?>
    </li>
    <li><a href="import_backup.php"><?php echo get_lang('ImportBackup')  ?></a><br/>
    <?php echo get_lang('ImportBackupInfo'); ?>
    </li>
  </ul>
<?php

// Display the footer
Display::display_footer();
