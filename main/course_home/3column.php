<?php
/* For licensing terms, see /license.txt */

/**
 *  HOME PAGE FOR EACH COURSE (BASIC TOOLS FIXED)
 *
 *	This page, included in every course's index.php is the home
 *	page.To make administration simple, the professor edits his
 *	course from it's home page. Only the login detects that the
 *	visitor is allowed to activate, deactivate home page links,
 *	access to Professor's tools (statistics, edit forums...).
 *
 *	@package chamilo.course_home
 */

require_once api_get_path(LIBRARY_PATH).'course_home.lib.php';

$hide = isset($_GET['hide']) && $_GET['hide'] == 'yes' ? 'yes' : null;
$restore = isset($_GET['restore']) && $_GET['restore'] == 'yes' ? 'yes' : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

$TABLE_TOOLS = Database::get_main_table(TABLE_MAIN_COURSE_MODULE);
$TBL_ACCUEIL = Database::get_course_table(TABLE_TOOL_LIST);

$course_id = api_get_course_int_id();

// WORK with data post askable by admin of course
if (api_is_allowed_to_edit(null, true)) {

	/*  Processing request */

	/*	MODIFY HOME PAGE */

	/*
	 * Edit visibility of tools
	 *
	 *     visibility = 1 - everybody
	 *     visibility = 0 - prof and admin
	 *     visibility = 2 - admin
	 *
	 * Who can change visibility ?
	 *
	 *     admin = 0 - prof and admin
	 *     admin = 1 - admin
	 *
	 * Show message to confirm that a tools must be hide from aivailable tools
	 *
	 *     visibility 0,1->2 - $remove
	 *
	 * Process hiding a tools from aivailable tools.
	 *
	 *     visibility=2                         are only view  by Dokeos
	 * Administrator visibility 0,1->2 - $destroy
	 *
	 *     visibility 1 -> 0 - $hide / $restore
	 */

	/*
	 * Diplay message to confirm that a tools must be hide from aivailable tools
	 * (visibility 0,1->2)
	 */

	if ($remove) {
		$sql = "SELECT * FROM $TBL_ACCUEIL WHERE c_id = $course_id AND id=$id";
		$result = Database::query($sql);
		$tool = Database::fetch_array($result);
		$tool_name = @htmlspecialchars($tool['name'] != '' ? $tool['name'] : $tool['link'], ENT_QUOTES, api_get_system_encoding());
		if ($tool['img'] != 'external.gif') {
			$tool['link'] = api_get_path(WEB_CODE_PATH).$tool['link'];
		}
		$tool['image'] = api_get_path(WEB_IMG_PATH).$tool['image'];

		echo "<br /><br /><br />\n";
		echo "<table class=\"message\" width=\"70%\" align=\"center\">\n",
			"<tr><td width=\"7%\" align=\"center\">\n",
			"<a href=\"".$tool['link']."\">".Display::return_icon($tool['image'], get_lang('Delete')), "</a></td>\n",
			"<td width=\"28%\" height=\"45\"><small>\n",
			"<a href=\"".$tool['link']."\">".$tool_name."</a></small></td>\n";
		echo "<td align=\"center\">\n",
			"<font color=\"#ff0000\">",
			"&nbsp;&nbsp;&nbsp;",
			"<strong>", get_lang('DelLk'), "</strong>",
			"<br />&nbsp;&nbsp;&nbsp;\n",
			"<a href=\"".api_get_self()."\">", get_lang('No'), "</a>\n",
			"&nbsp;|&nbsp;\n",
			"<a href=\"".api_get_self()."?destroy=yes&amp;id=$id\">", get_lang('Yes'), "</a>\n",
			"</font></td></tr>\n",
			"</table>\n";
		echo "<br /><br /><br />\n";

} // if remove

/*
 * Process hiding a tools from aivailable tools.
 * visibility=2 are only view  by Dokeos Administrator (visibility 0,1->2)
 */

elseif ($destroy) {
	Database::query("UPDATE $TBL_ACCUEIL SET visibility='2' WHERE c_id = $course_id AND id = $id");
}

/* HIDE */

elseif ($hide) { // visibility 1 -> 0
	Database::query("UPDATE $TBL_ACCUEIL SET visibility=0 WHERE c_id = $course_id AND id=$id");
	Display::display_confirmation_message(get_lang('ToolIsNowHidden'));
}

/*	REACTIVATE */

elseif ($restore) { // visibility 0,2 -> 1
	Database::query("UPDATE $TBL_ACCUEIL SET visibility=1  WHERE c_id = $course_id AND id=$id");
	Display::display_confirmation_message(get_lang('ToolIsNowVisible'));
}

/*
 * Editing "apparance" of  a tools  on the course Home Page.
 */

elseif (isset($update) && $update) {

	$result 	= Database::query("SELECT * FROM $TBL_ACCUEIL WHERE c_id = $course_id AND id=$id");
	$tool		= Database::fetch_array($result);
	$racine		= $_configuration['root_sys'].'/'.$currentCourseID.'/images/';
	$chemin		= $racine;
	$name		= $tool[1];
	$image		= $tool[3];

	echo "<tr>\n",
		"<td colspan=\"4\">\n",
		"<table>\n",
		"<tr>\n",
		"<td>\n",
		"<form method=\"post\" action=\"".api_get_self()."\">\n",
		"<input type=\"hidden\" name=\"id\" value=\"$id\">\n",
		"Image : ".Display::return_icon($image)."\n",
		"</td>\n",
		"<td>\n",
		"<select name=\"image\">\n",
		"<option selected>", $image, "</option>\n";

	if ($dir = @opendir($chemin)) {
		while ($file = readdir($dir)) {
			if ($file == '..' || $file == '.') {
				unset($file);
			}
			echo "<option>", $file, "</option>\n";
		}
		closedir($dir);
	}

	echo "</select>\n",
		"</td>\n",
		"</tr>\n",
		"<tr>\n",
		"<td>", get_lang('NameOfTheLink'), " : </td>\n",
		"<td><input type=\"text\" name=\"name\" value=\"", $name, "\"></td>\n",
		"</tr>\n",
		"<tr>\n",
		"<td>Lien :</td>\n",
		"<td><input type=\"text\" name=\"link\" value=\"", $link, "\"></td>\n",
		"</tr>\n",
		"<tr>\n",
		"<td colspan=\"2\"><input type=\"submit\" name=\"submit\" value=\"", get_lang('Ok'), "\"></td>\n",
		"</tr>\n",
		"</form>\n",
		"</table>\n",
		"</td>\n",
		"</tr>\n";
	}
}


// Work with data post askable by admin of  course

if ($is_platformAdmin && api_is_allowed_to_edit(null, true) && !api_is_coach()) {
	// Show message to confirm that a tools must be hide  from aivailable tools
	// visibility 0,1->2
	if ($askDelete) {
		echo "<table align=\"center\"><tr>\n",
			"<td colspan=\"4\">\n",
			"<br /><br />\n",
			"<font color=\"#ff0000\">",
			"&nbsp;&nbsp;&nbsp;",
			"<strong>",get_lang('DelLk'),"</strong>",
			"<br />&nbsp;&nbsp;&nbsp;\n",
			"<a href=\"".api_get_self()."\">",get_lang('No'),"</a>\n",
			"&nbsp;|&nbsp;\n",
			"<a href=\"".api_get_self()."?delete=yes&amp;id=$id\">",get_lang('Yes'),"</a>\n",
			"</font>\n",
			"<br /><br /><br />\n",
			"</td>\n",
			"</tr>",
			"</table>\n";
	} // if remove

	/*
	 * Process hiding a tools from aivailable tools.
	 * visibility=2 are only viewed by Dokeos Administrator visibility 0,1->2
	 */

	elseif (isset($delete) && $delete) {
		Database::query("DELETE FROM $TBL_ACCUEIL WHERE c_id = $course_id AND id = $id AND added_tool=1");
	}
}

echo "<table class=\"item\" align=\"center\" border=\"0\" width=\"95%\">\n";

/*	TOOLS  FOR  EVERYBODY */

echo "<tr>\n<td colspan=\"6\">&nbsp;</td>\n</tr>\n";
echo "<tr>\n<td colspan=\"6\">";

CourseHome::show_tool_3column('Basic');
CourseHome::show_tool_3column('External');

echo "</td>\n</tr>\n";


/*	PROF ONLY VIEW */

if (api_is_allowed_to_edit(null, true) && !api_is_coach()) {
	echo "<tr><td colspan=\"6\"><hr noshade size=\"1\" /></td></tr>\n",
		"<tr>\n","<td colspan=\"6\">\n",
		"<font color=\"#F66105\">\n", get_lang('CourseAdminOnly'), "</font>\n",
		"</td>\n","</tr>\n";
	echo "<tr>\n<td colspan=\"6\">";
	CourseHome::show_tool_3column('courseAdmin');
	echo "</td>\n</tr>\n";
}


/*	TOOLS FOR PLATFORM ADMIN ONLY */

if ($is_platformAdmin && api_is_allowed_to_edit(null, true) && !api_is_coach()) {
	echo "<tr>","<td colspan=\"6\">",
		"<hr noshade size=\"1\" />",
		"</td>","</tr>\n",
		"<tr>\n","<td colspan=\"6\">\n",
		"<font color=\"#F66105\" >", get_lang('PlatformAdminOnly'), "</font>\n",
		"</td>\n","</tr>\n";
	echo "<tr>\n<td colspan=\"6\">";
	CourseHome::show_tool_3column('platformAdmin');
	echo "</td>\n</tr>\n";
}

echo "</table>\n";
