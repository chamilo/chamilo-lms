<?php
/* For licensing terms, see /license.txt */

// TODO: Is this file needed anymore?

/**
 * MODIFY COURSE INFO
 * Modify course settings like:
 * 1. Course title
 * 2. Department
 * 3. Course description URL in the university web
 * Course code cannot be modified, because it gives the name for the
 * course database and course web directory. Professor cannot be
 * changed either as it determines who is allowed to modify the course.
 *
 * @author Thomas Depraetere
 * @author Hugues Peeters
 * @author Christophe Gesche
 *
 * @package chamilo.course_info
 */
/**
 * Code
 */

/*	INIT SECTION */

// Language file that needs to be included
$language_file = 'postpone';

//$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('Admin'));
$htmlHeadXtra[] = "
<style type=\"text/css\">
<!--
.month {font-weight : bold;color : #FFFFFF;background-color : #4171B5;padding-left : 15px;padding-right : 15px;}
.content {position: relative; left: 25px;}
-->
</style>
<STYLE media=\"print\" type=\"text/css\">
TD {border-bottom: thin dashed Gray;}
</STYLE>";

require '../inc/global.inc.php';
$this_section = SECTION_COURSES;

Display::display_header($nameTools, 'Settings');

//include api_get_path(CONFIGURATION_PATH).'postpone.conf.php';

$nameTools					= get_lang('Postpone');
$TABLECOURSE				= Database::get_main_table(TABLE_MAIN_COURSE);
$is_allowedToEdit			= $is_courseAdmin;
$currentCourseID			= $_course['sysCode'];
$currentCourseRepository	= $_course['path'];


$sqlCourseExtention 			= "SELECT last_visit, last_edit, creation_date, expiration_date FROM ".$TABLECOURSE." WHERE code = '".$_cid."'";
$resultCourseExtention 			= Database::query($sqlCourseExtention);
$currentCourseExtentionData 	= Database::fetch_array($resultCourseExtention);
$currentCourseLastVisit 		= $currentCourseExtentionData['last_visit'];
$currentCourseLastEdit			= $currentCourseExtentionData['last_edit'];
$currentCourseCreationDate 		= $currentCourseExtentionData['creation_date'];
$currentCourseExpirationDate	= $currentCourseExtentionData['expiration_date'];
// HERE YOU CAN EDIT YOUR RULES TO EXTEND THE LIFE OF COURSE

// $newCourseExpirationDate	= now() + $extendDelay



?>
<h3>
	<?php echo $nameTools ?>
</h3>
<?php //echo get_lang('SubTitle'); ?>


This script  would be  called  by
	professor,
	or administrator,
	or other  script
to give more time to a course before expiration.

<?php
Display::display_footer();
