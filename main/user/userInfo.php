<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 hent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This script displays info about one specific user, specified through
*	a GET parameter, e.g. uInfo=2
*
*	@todo clean up script in clean sections:
*	(1) gather information
*	(2) tool logic
*	(3) display
*	@author original author (unknown, probably thomas,hugues,moosh)
*	@author Roan Embrechts, minor modification: virtual courses support
*	@package dokeos.user
==============================================================================
*/

/*
==============================================================================
	   INIT SECTION
==============================================================================
*/
$editMainUserInfo = $_REQUEST['editMainUserInfo'];
$uInfo = intval($_REQUEST['uInfo']);
$langFile = array ('registration', 'userInfo');

include ("../inc/global.inc.php");
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$this_section = SECTION_COURSES;

$nameTools = get_lang("Users");
api_protect_course_script();

$TBL_USERINFO_DEF 		= Database :: get_course_table(TABLE_USER_INFO);
$TBL_USERINFO_CONTENT 	= Database :: get_course_table(TABLE_USER_INFO_CONTENT);

$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang('Users'));

if ($origin != 'learnpath')
{ //so we are not in learnpath tool
	Display :: display_header($nameTools, "User");
}
else
{
?> <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH);?>css/default.css" /> <?php

}

$currentCourse = $currentCourseID;

api_display_tool_title(get_lang("Users"));

/*
 * data  found  in settings  are :
 *	$uid
 *	$isAdmin
 *	$isAdminOfCourse
 *	$_configuration['main_database']
 *	$currentCourseID
 */

$userIdViewed = $uInfo; // Id of the user we want to view coming from the user.php

/*
-----------------------------------------------------------
	Connection layer between Dokeos and the current script
-----------------------------------------------------------
*/

$mainDB = $_configuration['main_database'];
$courseCode = $currentCourseID = $_course['sysCode'];
$tbl_coursUser = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

$userIdViewer = $_user['user_id']; // id fo the user currently online
//$userIdViewed = $_GET['userIdViewed']; // Id of the user we want to view

$allowedToEditContent = ($userIdViewer == $userIdViewed) || $is_platformAdmin;
$allowedToEditDef = $is_courseAdmin;
$is_allowedToTrack = $is_courseAdmin && $_configuration['tracking_enabled'];

// Library connection
include ("userInfoLib.php");

// clean field submitted by the user
foreach ($_POST as $key => $value)
{
	$$key = replace_dangerous_char($value);
}

/*
==============================================================================
	   FUNCTIONS
==============================================================================
*/

/*
==============================================================================
	   COMMANDS SECTION
==============================================================================
*/

$displayMode = "viewContentList";

if ($allowedToEditDef)
{
	if ($submitDef)
	{
		if ($id)
		{
			edit_cat_def($id, $title, $comment, $nbline);
		}
		else
		{
			create_cat_def($title, $comment, $nbline);
		}

		$displayMode = "viewDefList";
	}
	elseif ($removeDef)
	{
		remove_cat_def($removeDef, true);
		$displayMode = "viewDefList";
	}
	elseif ($editDef)
	{
		$displayMode = "viewDefEdit";
	}
	elseif (isset ($addDef))
	{
		$displayMode = "viewDefEdit";
	}
	elseif ($moveUpDef)
	{
		move_cat_rank($moveUpDef, "up");
		$displayMode = "viewDefList";
	}
	elseif ($moveDownDef)
	{
		move_cat_rank($moveDownDef, "down");
		$displayMode = "viewDefList";
	}
	elseif ($viewDefList)
	{
		$displayMode = "viewDefList";
	}
	elseif ($editMainUserInfo)
	{
		$userIdViewed = $editMainUserInfo;
		$displayMode = "viewMainInfoEdit";
	}
	elseif ($submitMainUserInfo)
	{
		$userIdViewed = $submitMainUserInfo;

		$promoteCourseAdmin ? $userProperties['status'] = 1 : $userProperties['status'] = 5;
		$promoteTutor ? $userProperties['tutor'] = 1 : $userProperties['tutor'] = 0;

		$userProperties['role'] = $role;

		update_user_course_properties($userIdViewed, $courseCode, $userProperties);

		$displayMode = "viewContentList";
	}
}

// COMMON COMMANDS

if ($allowedToEditContent)
{
	if ($submitContent)
	{
		if ($cntId) // submit a content change
		{
			edit_cat_content($catId, $userIdViewer, $content, $REMOTE_ADDR);
		}
		else // submit a totally new content
			{
			fill_new_cat_content($catId, $userIdViewer, $content, $REMOTE_ADDR);
		}

		$displayMode = "viewContentList";
	}
	elseif ($editContent)
	{
		$displayMode = "viewContentEdit";

		$userIdViewed = $userIdViewer;
	}
}

/*
==============================================================================
	   DISPLAY MODES
==============================================================================
*/

// Back button for each display mode (Top)
echo "<p align=\"right\"><a href=\"user.php?".api_get_cidreq()."&origin=".$origin."\">".get_lang('BackUser')."</a></p>\n";

if ($displayMode == "viewDefEdit")
{
	/*>>>>>>>>>>>> CATEGORIES DEFINITIONS : EDIT <<<<<<<<<<<<*/

	$catToEdit = get_cat_def($editDef);
	$edit_heading_form = new FormValidator('edit_heading_form');
	$edit_heading_form->addElement('hidden', 'id');
	$edit_heading_form->add_textfield('title', get_lang('Title'));
	$edit_heading_form->addElement('textarea', 'comment', get_lang('Comment'), array ('cols' => 60, 'rows' => 4));
	$possible_line_nrs[1] = '1 '.get_lang('Line');
	$possible_line_nrs[3] = '3 '.get_lang('Lines');
	$possible_line_nrs[5] = '5 '.get_lang('Lines');
	$possible_line_nrs[10] = '10 '.get_lang('Lines');
	$possible_line_nrs[15] = '15 '.get_lang('Lines');
	$edit_heading_form->addElement('select', 'nbline', get_lang('LineNumber'), $possible_line_nrs);
	$edit_heading_form->addElement('submit', 'submitDef', get_lang('Ok'));
	$edit_heading_form->setDefaults($catToEdit);
	$edit_heading_form->display();
}
elseif ($displayMode == "viewDefList")
{
	/*>>>>>>>>>>>> CATEGORIES DEFINITIONS : LIST <<<<<<<<<<<<*/

	$catList = get_cat_def_list();

	if ($catList)
	{

		foreach ($catList as $thisCat)
		{
			// displays Title and comments

			echo "<p>", "<b>".htmlize($thisCat['title'])."</b><br>\n", "<i>".htmlize($thisCat['comment'])."</i>\n", "</p>";

			// displays lines

			echo "<blockquote>\n", "<font color=\"gray\">\n";

			for ($i = 1; $i <= $thisCat['nbline']; $i ++)
			{
				echo "<br>__________________________________________\n";
			}

			echo "</font>\n", "</blockquote>\n";

			// displays commands

			echo "<a href=\"".$_SERVER['PHP_SELF']."?removeDef=", $thisCat['catId'], "\">", "<img src=\"../img/delete.gif\" border=\"0\" alt=\"".get_lang('Remove')."\" onclick=\"javascript:if(!confirm('".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."')) return false;\">", "</a>", "<a href=\"".$_SERVER['PHP_SELF']."?editDef=", $thisCat['catId'], "\">", "<img src=\"../img/edit.gif\" border=\"0\" alt=\"".get_lang('Edit')."\">", "</a>", "<a href=\"".$_SERVER['PHP_SELF']."?moveUpDef=", $thisCat['catId'], "\">", "<img src=\"../img/up.gif\" border=\"0\" alt=\"".get_lang('MoveUp')."\">", "</a>", "<a href=\"".$_SERVER['PHP_SELF']."?moveDownDef=", $thisCat['catId'], "\">", "<img src=\"../img/down.gif\" border=\"0\" alt=\"".get_lang('MoveDown')."\">", "</a>\n";
		} // end for each

	} // end if ($catList)

	echo "<center>\n", "<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">", "<input type=\"submit\" name=\"addDef\" value=\"".get_lang('AddNewHeading')."\">", "</form>\n", "<center>\n";
}
elseif ($displayMode == "viewContentEdit")
{
	/*>>>>>>>>>>>> CATEGORIES CONTENTS : EDIT <<<<<<<<<<<<*/

	$catToEdit = get_cat_content($userIdViewed, $editContent);

	$content_heading_form = new FormValidator('content_heading_form');
	$content_heading_form->addElement('hidden', 'cntId');
	$content_heading_form->addElement('hidden', 'catId');
	$content_heading_form->addElement('hidden', 'uInfo');
	$content_heading_form->addElement('static', null, $catToEdit['title'], htmlize($catToEdit['comment']));
	if ($catToEdit['nbline'] == 1)
	{
		$content_heading_form->addElement('text', 'content', null, array ('size' => 80));
	}
	else
	{
		$content_heading_form->addElement('textarea', 'content', null, array ('cols' => 60, 'rows' => $catToEdit['nbline']));
	}
	$content_heading_form->addElement('submit', 'submitContent', get_lang('Ok'));
	$defaults = $catToEdit;
	$defaults['cntId'] = $catToEdit['contentId'];
	$defaults['uInfo'] = $userIdViewed;
	$content_heading_form->setDefaults($defaults);
	$content_heading_form->display();
}
elseif ($displayMode == "viewMainInfoEdit")
{
	/*>>>>>>>>>>>> CATEGORIES MAIN INFO : EDIT <<<<<<<<<<<<*/

	$mainUserInfo = get_main_user_info($userIdViewed, $courseCode);

	if ($mainUserInfo)
	{
		($mainUserInfo['status'] == 1) ? $courseAdminChecked = "checked" : $courseAdminChecked = "";
		($mainUserInfo['tutor_id'] == 1) ? $tutorChecked = "checked" : $tutorChecked = "";

		if ($mainUserInfo['picture'] != '')
		{
			$size = @ getImageSize('../upload/users/'.$mainUserInfo['picture']);
			$vertical_space = (($size[1] > 200) ? 'height="200"' : '');
			echo "<img src=\"../upload/users/".$mainUserInfo['picture']."\" $vertical_space border=\"1\">";
		}
		else
		{
			echo "<img src=\"../img/unknown.jpg\" border=\"1\">";
		}

		echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n", "<input type=\"hidden\" name=\"submitMainUserInfo\" value=\"$userIdViewed\">\n", "<table width=\"80%\" border=\"0\">", "<tr align=\"center\" bgcolor=\"#E6E6E6\">\n", "<td align=\"left\">", get_lang('Name'), "</td>\n", "<td align=\"left\">", get_lang('Role'), "</td>\n", "<td>", get_lang('Tutor'), "</td>\n", "<td>", get_lang('CourseManager'), "</td>\n", "</tr>\n", "<tr align=\"center\">", "<td align=\"left\"><b>", htmlize($mainUserInfo['firstName']), " ", htmlize($mainUserInfo['lastName']), "</b></td>\n", "<td align=\"left\"><input type=\"text\" name =\"role\" value=\"", $mainUserInfo['role'], "\" maxlength=\"40\"></td>", "<td><input class=\"checkbox\" type=\"checkbox\" name=\"promoteTutor\" value=\"1\" ", $tutorChecked, "></td>";

		if (!($is_courseAdmin && $_user['user_id'] == $userIdViewed))
		{
			echo "<td><input class=\"checkbox\" type=\"checkbox\" name=\"promoteCourseAdmin\" value=\"1\"", $courseAdminChecked, "></td>\n";
		}
		else
		{
			echo "<td>", get_lang('CourseManager'), "</td>\n";

		}

		echo "<td><input type=\"submit\" name=\"submit\" value=\"Ok\"></td>\n", "</tr>", "</table>", "</form>\n";

		echo "<p>".Display :: encrypted_mailto_link($mainUserInfo['email'], $mainUserInfo['email'])."</p>";

	}
}
elseif ($displayMode == "viewContentList") // default display
{
	/*>>>>>>>>>>>> CATEGORIES CONTENTS : LIST <<<<<<<<<<<<*/

	$virtual_course_code = $_GET["virtual_course"];
	if (isset ($virtual_course_code))
	{
		$courseCode = $virtual_course_code;
		//not supported yet: editing users of virtual courses
		$allowedToEditDef = false;
	}

	$mainUserInfo = get_main_user_info($userIdViewed, $courseCode);

	if ($mainUserInfo)
	{
		if ($mainUserInfo['picture'] != '')
		{
			$size = @ getImageSize('../upload/users/'.$mainUserInfo['picture']);
			$vertical_space = (($size[1] > 200) ? 'height="200"' : '');
			echo "<img src=\"../upload/users/".$mainUserInfo['picture']."\" $vertical_space border=\"1\">";
		}
		else
		{
			echo "<img src=\"../img/unknown.jpg\" border=\"1\">";
		}
		if($is_allowedToTrack)
		{
			echo get_lang('Tracking');	
		}

		//DISPLAY TABLE HEADING
		if ($origin == 'learnpath') { $allowedToEditDef=false; $is_allowedToTrack=false; }
		echo	"<table width=\"80%\" border=\"0\">",

				"<tr align=\"center\" bgcolor=\"#E6E6E6\">\n",
				"<td align=\"left\">",get_lang('Name'),"</td>\n",
				"<td align=\"left\">",get_lang('Description'),"</td>\n",
				"<td>",get_lang('Tutor'),"</td>\n",
				"<td>",get_lang('CourseManager'),"</td>\n",
				($allowedToEditDef?"<td>".get_lang('Edit')."</td>\n":""),
                                ($is_allowedToTrack?"<td>".get_lang('Tracking')."</td>\n":""),
				"</tr>\n",

				"<tr align=\"center\">\n",

				"<td  align=\"left\"><b>",htmlize($mainUserInfo['firstName'])," ",htmlize($mainUserInfo['lastName']),"</b></td>\n",
				"<td  align=\"left\">",htmlize($mainUserInfo['role']),"</td>";

		//DISPLAY TABLE CONTENT
				if ($mainUserInfo['tutor_id'] == 1)
				{
					echo "<td>",get_lang('Tutor'),"</td>\n";
				}
				else
				{
					echo "<td> - </td>\n";
				}

				if ($mainUserInfo['status'] == 1)
				{
					echo "<td>",get_lang('CourseManager'),"</td>";
				}
				else
				{
					echo "<td> - </td>\n";
				}

				if ($allowedToEditDef)
				{
					echo	"<td>",
							"<a href=\"".$_SERVER['PHP_SELF']."?editMainUserInfo=$userIdViewed\">",
							"<img border=\"0\" alt=\"\" src=\"../img/edit.gif\">",
							"</a>",
							"</td>";
				}
                                if ($is_allowedToTrack)
                                {
                                        echo	"<td>",
							"<a href=\"../tracking/userLog.php?".api_get_cidreq()."&uInfo=$userIdViewed\">",
							"<img border=\"0\" alt=\"".get_lang('Tracking')." : $userIdViewed\" src=\"../img/statistics.png\" />",
							"</a>",
							"</td>";
                                }
				echo "</tr>",
				"</table>";
				//"<p><a href=\"mailto:",$mainUserInfo['email'],"\">",$mainUserInfo['email'],"</a>",
				echo "<p>". Display::encrypted_mailto_link($mainUserInfo['email'],$mainUserInfo['email']). "</p>";

				echo "<p>\n";
	}

	if (get_setting('allow_user_headings') == 'true' && $allowedToEditDef) // only course administrators see this line
	{
		echo	"<div align=right>",
				"<form method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">",
				get_lang('CourseAdministratorOnly')," : ",
				"<input type=\"submit\" name=\"viewDefList\" value=\"".get_lang('DefineHeadings')."\">",
				"</form>",
				"<hr noshade size=\"1\" style=\"color:#99CCFF\">",
				"</div>\n";
	}

	$catList = get_course_user_info($userIdViewed);

	if ($catList)
	{
		foreach ($catList as $thisCat)
		{
			// Category title

			echo	"<p><b>",$thisCat['title'],"</b></p>\n";

			// Category content

			echo	"<blockquote>\n";

			if ($thisCat['content'])
			{
				echo htmlize($thisCat['content'])."\n";
			}
			else
			{
				echo "....";
			}

			// Edit command

			if ($allowedToEditContent)
			{
				echo	"<br><br>\n",
						"<a href=\"".$_SERVER['PHP_SELF']."?editContent=",$thisCat['catId'],"&uInfo=",$userIdViewed,"\">",
						"<img src=\"../img/edit.gif\" border=\"0\" alt=\"edit\">",
						"</a>\n";
			}

			echo	"</blockquote>\n";
		}
	}
}

// Back button for each display mode (bottom)
echo "<p align=\"right\"><a href=\"user.php?".api_get_cidreq()."&origin=".$origin."\">".get_lang('BackUser')."</a></p>\n";
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>