<?php
/* For licensing terms, see /license.txt */

/**
 *  HOME PAGE FOR EACH COURSE
 *
 *	This page, included in every course's index.php is the home
 *	page. To make administration simple, the teacher edits his
 *	course from the home page. Only the login detects that the
 *	visitor is allowed to activate, deactivate home page links,
 *	access to the teachers tools (statistics, edit forums...).
 *
 *	@package chamilo.course_home
 */

require_once api_get_path(LIBRARY_PATH).'course_home.lib.php';

/*	MAIN CODE */

/* 	Work with data post askable by admin of course (franglais, clean this) */

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$course_id = api_get_course_int_id();

if (api_is_allowed_to_edit(null, true)) {
	/*  Processing request */
	/*	Modify home page */
	/*
	 * Display message to confirm that a tool must be hidden from the list of available tools (visibility 0,1->2)
	 */

	if ($_GET['remove']) {
		$msgDestroy = get_lang('DelLk').'<br />';
		$msgDestroy .= '<a href="'.api_get_self().'">'.get_lang('No').'</a>&nbsp;|&nbsp;';
		$msgDestroy .= '<a href="'.api_get_self().'?destroy=yes&amp;id='.$id.'">'.get_lang('Yes').'</a>';
		Display :: display_confirmation_message($msgDestroy, false);
	}

	/*
	 * Process hiding a tools from available tools.
	 * visibility=2 are only view  by Dokeos Administrator (visibility 0,1->2)
	 */

	elseif ($_GET['destroy']) {
		Database::query("UPDATE $tool_table SET visibility='2' WHERE c_id = $course_id AND id='".$id."'");
	}

  	/*	HIDE */

	elseif ($_GET['hide']) { // visibility 1 -> 0
		Database::query("UPDATE $tool_table SET visibility=0 WHERE c_id = $course_id AND id='".$id."'");
		Display::display_confirmation_message(get_lang('ToolIsNowHidden'));
	}

    /*	REACTIVATE */

	elseif ($_GET["restore"]) { // visibility 0,2 -> 1
		Database::query("UPDATE $tool_table SET visibility=1  WHERE c_id = $course_id AND id='".$id."'");
		Display::display_confirmation_message(get_lang('ToolIsNowVisible'));
	}
}

// Work with data post askable by admin of course

if (api_is_platform_admin()) {
	// Show message to confirm that a tools must be hide from available tools
	// visibility 0,1->2
	if ($_GET['askDelete']) {
?>
			<div id="toolhide">
			<?php echo get_lang('DelLk'); ?>
			<br />&nbsp;&nbsp;&nbsp;
			<a href="<?php echo api_get_self(); ?>"><?php echo get_lang('No'); ?></a>&nbsp;|&nbsp;
			<a href="<?php echo api_get_self(); ?>?delete=yes&id=<?php echo $id; ?>"><?php echo get_lang('Yes'); ?></a>
			</div>
<?php
	}

	/*
	 * Process hiding a tools from available tools.
	 * visibility=2 are only view  by Dokeos Administrator visibility 0,1->2
	 */

	elseif (isset($_GET['delete']) && $_GET['delete']) {
		Database::query("DELETE FROM $tool_table WHERE c_id = $course_id AND id='$id' AND added_tool=1");
	}
}

/*	TOOLS VISIBLE FOR EVERYBODY */

echo '<div class="everybodyview">';
echo '<table width="100%">';

CourseHome::show_tool_2column(TOOL_PUBLIC);

echo '</table>';
echo '</div>';

/*	COURSE ADMIN ONLY VIEW */

// Start of tools for CourseAdmins (teachers/tutors)
if (api_is_allowed_to_edit(null, true) && !api_is_coach()) {
	echo "<div class=\"courseadminview\">";
	echo "<span class=\"viewcaption\">";
	echo get_lang('CourseAdminOnly');
	echo "</span>";
	echo "<table width=\"100%\">";

	CourseHome::show_tool_2column(TOOL_COURSE_ADMIN);

	/*	INACTIVE TOOLS - HIDDEN (GREY) LINKS */

	echo	"<tr><td colspan=\"4\"><hr style='color:\"#4171B5\"' noshade=\"noshade\" size=\"1\" /></td></tr>\n",
			"<tr>\n",
			"<td colspan=\"4\">\n",
			"<div style=\"margin-bottom: 10px;\"><font color=\"#808080\">\n", get_lang('InLnk'), "</font></div>",
			"</td>\n",
			"</tr>\n";

	CourseHome::show_tool_2column(TOOL_PUBLIC_BUT_HIDDEN);

	echo	"</table>";
	echo	"</div> ";
}

/*	Tools for platform admin only */

if (api_is_platform_admin() && api_is_allowed_to_edit(null, true) && !api_is_coach()) {
?>
		<div class="platformadminview">
		<span class="viewcaption"><?php echo get_lang('PlatformAdminOnly'); ?></span>
		<table width="100%">
<?php
			CourseHome::show_tool_2column(TOOL_PLATFORM_ADMIN);
?>
		</table>
		</div>
<?php
}
