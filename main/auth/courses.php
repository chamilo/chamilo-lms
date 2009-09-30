<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	@package dokeos.auth
*	@todo check if unsubscribing from a course WITH group memberships works as it should
*	@todo constants are in uppercase, variables aren't
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array ('courses','registration');

//delete the globals["_cid"] we don't need it here
$cidReset = true; // Flag forcing the 'current course' reset

// including the global file
include('../inc/global.inc.php');

// section for the tabs
$this_section=SECTION_COURSES;

// acces rights: anonymous users can't do anything usefull here
api_block_anonymous_users();

if (!(api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course())) {
	if (api_get_setting('allow_students_to_browse_courses')=='false') {
		api_not_allowed();
	}
}
// include additional libraries
include_once(api_get_path(LIBRARY_PATH) . 'debug.lib.inc.php');
include_once(api_get_path(LIBRARY_PATH) . 'course.lib.php');
require_once(api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
$ctok = $_SESSION['sec_token'];
$stok = Security::get_token();

// Database table definitions
$tbl_course             = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_courses_nodes      = Database::get_main_table(TABLE_MAIN_CATEGORY);
$tbl_courseUser         = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_user               = Database::get_main_table(TABLE_MAIN_USER);


//filter
$safe = array();
$safe['action'] = '';
$actions = array('sortmycourses','createcoursecategory','subscribe','deletecoursecategory','unsubscribe');

if(in_array(htmlentities($_GET['action']),$actions)) {
	$safe['action'] = htmlentities($_GET['action']);
}

// title of the page
if ($safe['action'] == 'sortmycourses' OR !isset($safe['action'])) {
	$nameTools = get_lang('SortMyCourses');
}
if ($safe['action'] == 'createcoursecategory')
{
	$nameTools = get_lang('CreateCourseCategory');
}
if ($safe['action'] == 'subscribe')
{
	$nameTools = get_lang('SubscribeToCourse');
}

// breadcrumbs
$interbreadcrumb[] = array('url'=>api_get_path(WEB_PATH).'user_portal.php', 'name'=> get_lang('MyCourses'));
if (empty($nameTools)) {
	$nameTools=get_lang('CourseManagement');
}
else
{
	$interbreadcrumb[] = array('url'=>api_get_path(WEB_PATH).'main/auth/courses.php', 'name'=> get_lang('CourseManagement'));
}

// Displaying the header
Display::display_header($nameTools);

/*
==============================================================================
		COMMANDS SECTION
==============================================================================
*/
unset($message);
// we are moving a course or category of the user up/down the list (=Sort My Courses)
if (isset($_GET['move']))
{
	if (isset($_GET['course']))
	{
		if($ctok == $_GET['sec_token'])
		{
			$message=move_course($_GET['move'], $_GET['course'],$_GET['category']);
		}
	}
	if (isset($_GET['category']) and !$_GET['course'])
	{
		if($ctok == $_GET['sec_token'])
		{
			$message=move_category($_GET['move'], $_GET['category']);
		}
	}
}

// we are moving the course of the user to a different user defined course category (=Sort My Courses)
if (isset($_POST['submit_change_course_category']))
{
	if($ctok == $_POST['sec_token'])
	{
		$message=store_changecoursecategory($_POST['course_2_edit_category'], $_POST['course_categories']);
	}
}
// we are creating a new user defined course category (= Create Course Category)
if (isset($_POST['create_course_category']) AND isset($_POST['title_course_category']) AND strlen(trim($_POST['title_course_category'])) > 0)
{
	if($ctok == $_POST['sec_token'])
	{
		$message=store_course_category();
	}
}

if (isset($_POST['submit_edit_course_category']) AND isset($_POST['title_course_category']) AND strlen(trim($_POST['title_course_category'])) > 0)
{
	if($ctok == $_POST['sec_token'])
	{
		$message=store_edit_course_category();
	}
}

// we are subcribing to a course (=Subscribe to course)
if (isset($_POST['subscribe']))
{
	if($ctok == $_POST['sec_token'])
	{
		$message = subscribe_user($_POST['subscribe']);
	}
}

// we are unsubscribing from a course (=Unsubscribe from course)
if (isset($_POST['unsubscribe']))
{
	if($ctok == $_POST['sec_token'])
	{
		$message=remove_user_from_course($_user['user_id'], $_POST['unsubscribe']);
	}
}
// we are deleting a course category
if ($safe['action']=='deletecoursecategory' AND isset($_GET['id']))
{
	if($ctok == $_GET['sec_token'])
	{
		$get_id_cat=Security::remove_XSS($_GET['id']);
		$message=delete_course_category($get_id_cat);
	}
}

/*
==============================================================================
					DISPLAY SECTION
==============================================================================
*/
// Diplaying the tool title
// api_display_tool_title($nameTools);

// we are displaying any result messages;
if (isset($message))
{
	Display::display_confirmation_message($message, false);
}

// The menu with the different options in the course management
echo "<div id=\"actions\" class='actions'>";
if ($safe['action'] <> 'sortmycourses' AND isset($safe['action'])) {
	echo "&nbsp;&nbsp;<a href=\"".api_get_self()."?action=sortmycourses\">".Display::return_icon('deplacer_fichier.gif', get_lang("SortMyCourses")).' '.get_lang("SortMyCourses")."</a>&nbsp;";
} else {
	echo '&nbsp;&nbsp;<b>'.Display::return_icon('deplacer_fichier.gif', get_lang('SortMyCourses')).' '.get_lang('SortMyCourses').'</b>&nbsp;';
}
echo '&nbsp;';
if ($safe['action']<>'createcoursecategory') {
	echo "&nbsp;&nbsp;<a href=\"".api_get_self()."?action=createcoursecategory\">".Display::return_icon('folder_new.gif', get_lang("CreateCourseCategory")).' '.get_lang("CreateCourseCategory")."</a>&nbsp;";
} else {
	echo '&nbsp;&nbsp;<b>'.Display::return_icon('folder_new.gif', get_lang("CreateCourseCategory")).' '.get_lang('CreateCourseCategory').'</b>&nbsp;';
}
echo '&nbsp;';
if ($safe['action']<>'subscribe') {
	echo "&nbsp;&nbsp;<a href=\"".api_get_self()."?action=subscribe\">".Display::return_icon('view_more_stats.gif', get_lang("SubscribeToCourse")).' '.get_lang("SubscribeToCourse")."</a>&nbsp;";
} else {
	echo '&nbsp;&nbsp;<b>'.Display::return_icon('view_more_stats.gif', get_lang("SubscribeToCourse")).' '.get_lang("SubscribeToCourse").'</b>&nbsp;';
}
echo "</div>";

echo "<div>";
switch ($safe['action'])
{
	case 'subscribe':
		//api_display_tool_title(get_lang('SubscribeToCourse'));
		courses_subscribing();
		break;
	case 'unsubscribe':
		//api_display_tool_title(get_lang('UnsubscribeFromCourse'));
		$user_courses=get_courses_of_user($_user['user_id']);
		display_courses($_user['user_id'], true, $user_courses);
		break;
	case 'createcoursecategory':
		//api_display_tool_title(get_lang('CreateCourseCategory'));
		display_create_course_category_form();
		break;
	case 'deletecoursecategory':
	case 'sortmycourses':
	default:
		//api_display_tool_title(get_lang('SortMyCourses'));
		$user_courses=get_courses_of_user($_user['user_id']);
		display_courses($_user['user_id'], true, $user_courses);
		break;
}
echo '</div>';
Display :: display_footer();

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/
 /**
  * Subscribe the user to a given course
  * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
  * @param string $course_code the code of the course the user wants to subscribe to
  * @return string we return the message that is displayed when the action is succesfull
 */
function subscribe_user($course_code)
{
	global $_user, $stok;

	$all_course_information =  CourseManager::get_course_information($course_code);

	if ($all_course_information['registration_code']=='' OR $_POST['course_registration_code']==$all_course_information['registration_code'])
	{

		if (api_is_platform_admin()) {
			$status_user_in_new_course=COURSEMANAGER;
		} else {
			$status_user_in_new_course=null;
		}
		if (CourseManager::add_user_to_course($_user['user_id'], $course_code,$status_user_in_new_course))
		{
			$send = api_get_course_setting('email_alert_to_teacher_on_new_user_in_course',$course_code);
			if ($send == 1) {
				CourseManager::email_to_tutor($_user['user_id'],$course_code,$send_to_tutor_also=false);
			} else if ($send == 2){
				CourseManager::email_to_tutor($_user['user_id'],$course_code,$send_to_tutor_also=true);
			}
			return get_lang('EnrollToCourseSuccessful');

		}
		else
		{
			return get_lang('ErrorContactPlatformAdmin');
		}
	}
	else
	{
		$return='';
		if (isset($_POST['course_registration_code']) AND $_POST['course_registration_code']<>$all_course_information['registration_code'])
		{
			Display::display_error_message(get_lang('CourseRegistrationCodeIncorrect'));
		}
		$return.=get_lang('CourseRequiresPassword').'<br/>';
		$return.=$all_course_information['visual_code'].' - '.$all_course_information['title'];

		$return.="<form action=\"".$_SERVER["REQUEST_URI"]."\" method=\"post\">";
		$return.='<input type="hidden" name="sec_token" value="'.$stok.'" />';
		$return.="<input type=\"hidden\" name=\"subscribe\" value=\"".$all_course_information['code']."\" />";
		$return.="<input type=\"text\" name=\"course_registration_code\" value=\"".$_POST['course_registration_code']."\" />";
		$return.="<input type=\"Submit\" name=\"submit_course_registration_code\" value=\"OK\" alt=\"".get_lang("SubmitRegistrationCode")."\" /></form>";
		return $return;
	}
}
/**
 * unsubscribe the user from a given course
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $user_id The user id of the user that is performing the unsubscribe action
 * @param string $course_code the course code of the course the user wants to unsubscribe from
 * @return string we return the message that is displayed when the action is succesfull
*/
function remove_user_from_course($user_id, $course_code)
{
	$tbl_course_user         = Database::get_main_table(TABLE_MAIN_COURSE_USER);

	// we check (once again) if the user is not course administrator
	// because the course administrator cannot unsubscribe himself
	// (s)he can only delete the course
	$sql_check="SELECT * FROM $tbl_course_user WHERE user_id='".$user_id."' AND course_code='".$course_code."' AND status='1'";
	$result_check=Database::query($sql_check,__FILE__,__LINE__);
	$number_of_rows=Database::num_rows($result_check);

	if ($number_of_rows>0)
	{return false;}
	else
	{
		CourseManager::unsubscribe_user($user_id,$course_code);
		return get_lang("YouAreNowUnsubscribed");
	}
}


/**
 * handles the display of the courses to which the user can subscribe
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function courses_subscribing()
{
	browse_courses();
	display_search_courses();
}

/**
 * Allows you to browse through the course categories (faculties) and subscribe to the courses of
 * this category (faculty)
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*/
function browse_courses()
{
	browse_course_categories();
	if (!isset($_POST['search_course']))
	{
		browse_courses_in_category();
	}
}

/**
 * Counts the number of courses in a given course category
*/
function count_courses_in_category($category)
{
	$tbl_course         = Database::get_main_table(TABLE_MAIN_COURSE);
	$sql="SELECT * FROM $tbl_course WHERE category_code".(empty($category)?" IS NULL":"='".$category."'");

	//showing only the courses of the current Dokeos access_url_id
	global $_configuration;
	if ($_configuration['multiple_access_urls']==true) {
		$url_access_id = api_get_current_access_url_id();
		if ($url_access_id !=-1) {
			$tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
			$sql="SELECT * FROM $tbl_course as course INNER JOIN $tbl_url_rel_course as url_rel_course
					ON (url_rel_course.course_code=course.code)
					WHERE access_url_id = $url_access_id AND category_code".(empty($category)?" IS NULL":"='".$category."'");
		}
	}
	$result=Database::query($sql,__FILE__,__LINE__);
	return Database::num_rows($result);
}


/**
 * displays the browsing of the course categories (faculties)
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return HTML code containing a list with all the categories and subcategories and the navigation to go one category up(if needed)
*/
function browse_course_categories()
{
	global $stok;
	$tbl_courses_nodes   = Database::get_main_table(TABLE_MAIN_CATEGORY);
	$category = Database::escape_string($_GET['category']);
	$safe_url_categ = Security::remove_XSS($_GET['category']);

	echo "<p><b>".get_lang('CourseCategories')."</b>";

	$sql= "SELECT * FROM $tbl_courses_nodes WHERE parent_id ".(empty($category)?"IS NULL":"='".$category."'")." GROUP BY code, parent_id  ORDER BY tree_pos ASC";

	$result=Database::query($sql,__FILE__,__LINE__);
	echo "<ul>";
	while ($row=Database::fetch_array($result))	{
		$count_courses_in_categ = count_courses_in_category($row['code']);
		if ($row['children_count'] > 0 OR $count_courses_in_categ>0) {
			echo	"<li><a href=\"".api_get_self()."?action=subscribe&amp;category=".$row['code']."&amp;up=".$safe_url_categ."&amp;sec_token=".$stok."\">".$row['name']."</a>".
				" (".$count_courses_in_categ.")</li>";
		} elseif ($row['nbChilds'] > 0) {
			echo	"<li><a href=\"".api_get_self()."?action=subscribe&amp;category=".$row['code']."&amp;up=".$safe_url_categ."&amp;sec_token=".$stok."\">".$row['name']."</a></li>";
		} else {
			echo "<li>".$row['name']."</li>";
		}

	}
	echo "</ul>";
	if ($_GET['category']) {
		echo "<a href=\"".api_get_self()."?action=subscribe&amp;category=".Security::remove_XSS($_GET['up'])."&amp;sec_token=".$stok."\">".Display::return_icon('back.png',get_lang('UpOneCategory'),array('align'=>'absmiddle')).get_lang('UpOneCategory')."</a>";
	}
}

/**
 * Display all the courses in the given course category. I could have used a parameter here
 * but instead I use the already available $_GET['category']
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return HTML code: a table with all the courses in a given category (title, code, tutor) and a subscription icon if applicable)
*/
function browse_courses_in_category()
{
	$tbl_course         = Database::get_main_table(TABLE_MAIN_COURSE);
	$category = Database::escape_string($_GET['category']);

	echo "<p><b>".get_lang('CoursesInCategory')."</b>";
	$my_category = (empty($category)?" IS NULL":"='".$category."'");

	$sql="SELECT * FROM $tbl_course WHERE category_code".$my_category.' ORDER BY title, visual_code';

	//showing only the courses of the current Dokeos access_url_id
	global $_configuration;
	if ($_configuration['multiple_access_urls']==true) {
		$url_access_id = api_get_current_access_url_id();
		if ($url_access_id !=-1) {
			$tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
			$sql="SELECT * FROM $tbl_course as course INNER JOIN $tbl_url_rel_course as url_rel_course
					ON (url_rel_course.course_code=course.code)
					WHERE access_url_id = $url_access_id AND category_code".$my_category.' ORDER BY title, visual_code';
		}
	}

	$result=Database::query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result)) {
		if ($row['registration_code']=='') {
			$registration_code=false;
		} else {
			$registration_code=true;
		}
		$courses[]=array("code" => $row['code'], "directory" => $row['directory'], "db"=> $row['db_name'], "visual_code" => $row['visual_code'], "title" => $row['title'], "tutor" => $row['tutor_name'], "subscribe" => $row['subscribe'], "unsubscribe" => $row['unsubscribe'], 'registration_code'=> $registration_code);
	}
	display_subscribe_to_courses($courses);
}


/**
 * displays the form for searching for a course and the results if a query has been submitted.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return HTML code: the form for searching for a course
*/
function display_search_courses()
{
	global $_user,$charset,$stok;
	echo "<p><b>".get_lang("SearchCourse")."</b><br />";
	echo "<form class=\"course_list\" method=\"post\" action=\"".api_get_self()."?action=subscribe\">",
					'<input type="hidden" name="sec_token" value="'.$stok.'">',
					"<input type=\"hidden\" name=\"search_course\" value=\"1\" />",
					"<input type=\"text\" name=\"search_term\" value=\"".(empty($_POST['search_term'])?'':Security::remove_XSS($_POST['search_term']))."\" />",
					"&nbsp;<button class=\"search\" type=\"submit\">",get_lang("_search")," </button>",
					"</form>";
	if (isset($_POST['search_course']))
	{
		echo "<p><b>".get_lang("SearchResultsFor")." ".api_htmlentities($_POST['search_term'],ENT_QUOTES,$charset)."</b><br />";
		$result_search_courses_array=search_courses($_POST['search_term']);
		display_subscribe_to_courses($result_search_courses_array);
	}
}

/**
 * This function displays the list of course that can be subscribed to.
 * This list can come from the search results or from the browsing of the platform course categories
*/
function display_subscribe_to_courses($courses)
{

	global $_user;
	// getting all the courses to which the user is subscribed to
	$user_courses=get_courses_of_user($_user['user_id']);
	$user_coursecodes=array();

	// we need only the course codes as these will be used to match against the courses of the category
	if ($user_courses<>"") {
		foreach ($user_courses as $key=>$value) {
			$user_coursecodes[]=$value['code'];
		}
	}

	if ($courses==0) {
			return false;
	}

	echo "<table cellpadding=\"4\">\n";
	foreach ($courses as $key=>$course) {
		// displaying the course title, visual code and teacher/teaching staff
		echo "\t<tr>\n";
		echo "\t\t<td>\n";
		echo "<b>".$course['title']."</b><br />";
		if (api_get_setting("display_coursecode_in_courselist") == "true") {
			echo $course['visual_code'];
		}
		if (api_get_setting("display_coursecode_in_courselist") == "true" AND api_get_setting("display_teacher_in_courselist") == "true") {
			echo " - ";
		}
		if (api_get_setting("display_teacher_in_courselist") == "true")
		{
			echo $course['tutor'];
		}
		echo "\t\t</td>\n";

		echo "\t\t<td>\n";
		if ($course['registration_code'])
		{
			Display::display_icon('passwordprotected.png','',array('style'=>'float:left;'));
		}
		display_subscribe_icon($course, $user_coursecodes);
		echo "\t\t</td>\n";

		echo "</tr>";

	}
	echo "</table>";
}

/**
 * Search the courses database for a course that matches the search term.
 * The search is done on the code, title and tutor field of the course table.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param string $search_term: the string that the user submitted, what we are looking for
 * @return array an array containing a list of all the courses (the code, directory, dabase, visual_code, title, ... )
 * 			matching the the search term.
*/
function search_courses($search_term)
{
	$TABLECOURS = Database::get_main_table(TABLE_MAIN_COURSE);
	$search_term_safe=Database::escape_string($search_term);
	$sql_find="SELECT * FROM $TABLECOURS WHERE code LIKE '%".$search_term_safe."%' OR title LIKE '%".$search_term_safe."%' OR tutor_name LIKE '%".$search_term_safe."%' ORDER BY title, visual_code ASC";

	global $_configuration;
	if ($_configuration['multiple_access_urls']==true) {
		$url_access_id = api_get_current_access_url_id();
		if ($url_access_id !=-1) {
			$tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
			$sql_find="SELECT * FROM $TABLECOURS as course INNER JOIN $tbl_url_rel_course as url_rel_course
					ON (url_rel_course.course_code=course.code)
					WHERE access_url_id = $url_access_id AND  (code LIKE '%".$search_term_safe."%' OR title LIKE '%".$search_term_safe."%' OR tutor_name LIKE '%".$search_term_safe."%' ) ORDER BY title, visual_code ASC ";
		}
	}
	$result_find=Database::query($sql_find,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result_find)) {
		$courses[]=array("code" => $row['code'], "directory" => $row['directory'], "db"=> $row['db_name'], "visual_code" => $row['visual_code'], "title" => $row['title'], "tutor" => $row['tutor_name'], "subscribe" => $row['subscribe'], "unsubscribe" => $row['unsubscribe']);
	}
	return $courses;
}


/**
 * deletes a course category and moves all the courses that were in this category to main category
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $id: the id of the user_course_category
 * @return string a language variable saying that the deletion went OK.
*/
function delete_course_category($id)
{
	global $_user, $_configuration;

	$tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$TABLECOURSUSER=Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$id = intval($id);
	$sql_delete="DELETE FROM $tucc WHERE id='".$id."' and user_id='".$_user['user_id']."'";
	$sql_update="UPDATE $TABLECOURSUSER SET user_course_cat='0' WHERE user_course_cat='".$id."' AND user_id='".$_user['user_id']."'";
	Database::query($sql_delete,__FILE__,__LINE__);
	Database::query($sql_update,__FILE__,__LINE__);

	return get_lang("CourseCategoryDeleted");
}


/**
 * stores the user course category in the dokeos_user database
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return string a language variable saying that the user course category was stored
*/
function store_course_category()
{
	global $_user, $_configuration, $charset;

	$tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);

	// step 1: we determine the max value of the user defined course categories
	$sql="SELECT sort FROM $tucc WHERE user_id='".$_user['user_id']."' ORDER BY sort DESC";
	$result=Database::query($sql,__FILE__,__LINE__);
	$maxsort=Database::fetch_array($result);
	$nextsort=$maxsort['sort']+1;

	// step 2: we check if there is already a category with this name, if not we store it, else we give an error.
	$sql="SELECT * FROM $tucc WHERE user_id='".$_user['user_id']."' AND title='".Database::escape_string($_POST['title_course_category'])."'ORDER BY sort DESC";
	$result=Database::query($sql,__FILE__,__LINE__);
	if (Database::num_rows($result) == 0)
	{
		$sql_insert="INSERT INTO $tucc (user_id, title,sort) VALUES ('".$_user['user_id']."', '".api_htmlentities($_POST['title_course_category'],ENT_QUOTES,$charset)."', '".$nextsort."')";
		Database::query($sql_insert,__FILE__,__LINE__);
		Display::display_confirmation_message(get_lang("CourseCategoryStored"));
	}
	else
	{
		Display::display_error_message(get_lang('ACourseCategoryWithThisNameAlreadyExists'));
	}
}


/**
 * displays the form that is needed to create a course category.
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return HTML the form (input field + submit button) to create a user course category
*/
function display_create_course_category_form()
{
	global $_user, $_configuration,$stok;

	echo "<form name=\"create_course_category\" method=\"post\" action=\"".api_get_self()."?action=sortmycourses\">\n";
	echo '<input type="hidden" name="sec_token" value="'.$stok.'">';
	echo "<input type=\"text\" name=\"title_course_category\" />\n";
	echo "<button type=\"submit\" class=\"save\" name=\"create_course_category\">".get_lang('Ok')." </button>\n";
	echo "</form>\n";

	echo get_lang("ExistingCourseCategories");
	$tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$sql="SELECT * FROM $tucc WHERE user_id='".$_user['user_id']."'";
	$result=Database::query($sql, __LINE__, __FILE__);
	if (Database::num_rows($result)>0)
	{
		echo "<ul>\n";
		while ($row=Database::fetch_array($result))
		{
			echo "\t<li>".$row['title']."</li>\n";
		}
		echo "</ul>\n";
	}
}
// ***************************************************************************
// this function stores the changes in a course category
//
// ***************************************************************************

/**
 * stores the changes in a course category (moving a course to a different course category)
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param string $course_code : the course_code of the course we are moving
 *		  int $newcategory : the id of the user course category we are moving the course to.
 * @return string a language variable saying that the course was moved.
*/
function store_changecoursecategory($course_code, $newcategory)
{
	global $_user;
	$course_code = Database::escape_string($course_code);
	$newcategory = Database::escape_string($newcategory);

	$TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);

	$max_sort_value=api_max_sort_value($newcategory,$_user['user_id']); //max_sort_value($newcategory);
	$sql="UPDATE $TABLECOURSUSER SET user_course_cat='".$newcategory."', sort='".($max_sort_value+1)."' WHERE course_code='".$course_code."' AND user_id='".$_user['user_id']."'";
	$result=Database::query($sql,__FILE__,__LINE__);
	return get_lang("EditCourseCategorySucces");
}
/**
 * moves the course one place up or down
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param string $direction : the direction we are moving the course to (up or down)
 *		  string $course2move : the course we are moving
 * @return string a language variable saying that the course was moved.
*/
function move_course($direction, $course2move, $category)
{
	global $_user;
	$TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);

	$all_user_courses=get_courses_of_user($_user['user_id']);

	// we need only the courses of the category we are moving in
	$user_courses = array();
	foreach ($all_user_courses as $key=>$course)
	{
		if ($course['user_course_category']==$category)
		{
			$user_courses[]=$course;
		}
	}

	foreach ($user_courses as $key=>$course)
	{
		if ($course2move==$course['code'])
		{
			// source_course is the course where we clicked the up or down icon
			$source_course=$course;
			// target_course is the course before/after the source_course (depending on the up/down icon)
			if ($direction=="up")
				{$target_course=$user_courses[$key-1];}
			else
				{$target_course=$user_courses[$key+1];}
		} // if ($course2move==$course['code'])
	}

	if(count($target_course)>0 && count($source_course)>0)
	{
		$sql_update1="UPDATE $TABLECOURSUSER SET sort='".$target_course['sort']."' WHERE course_code='".$source_course['code']."' AND user_id='".$_user['user_id']."'";
		$sql_update2="UPDATE $TABLECOURSUSER SET sort='".$source_course['sort']."' WHERE course_code='".$target_course['code']."' AND user_id='".$_user['user_id']."'";
		Database::query($sql_update2,__FILE__,__LINE__);
		Database::query($sql_update1,__FILE__,__LINE__);
		return get_lang("CourseSortingDone");
	}
	return '';
}


/**
 * Moves the course one place up or down
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param string $direction : the direction we are moving the course to (up or down)
 *		  string $course2move : the course we are moving
 * @return string a language variable saying that the course was moved.
 */
function move_category($direction, $category2move)
{
	global $_user;
	// the database definition of the table that stores the user defined course categories
	$table_user_defined_category = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);

	$user_coursecategories=get_user_course_categories();
	$user_course_categories_info = get_user_course_categories_info();

	foreach ($user_coursecategories as $key=>$category_id)
	{
		if ($category2move==$category_id)
		{
			// source_course is the course where we clicked the up or down icon
			//$source_category=get_user_course_category($category2move);
			$source_category = $user_course_categories_info[$category2move];
			// target_course is the course before/after the source_course (depending on the up/down icon)
			if ($direction=="up")
			{
				$target_category=$user_course_categories_info[$user_coursecategories[$key-1]];
			}
			else
			{
				$target_category=$user_course_categories_info[$user_coursecategories[$key+1]];
			}
		} // if ($course2move==$course['code'])
	} // foreach ($user_courses as $key=>$course)

	if(count($target_category)>0 && count($source_category)>0)
	{
		$sql_update1="UPDATE $table_user_defined_category SET sort='".$target_category['sort']."' WHERE id='".$source_category['id']."' AND user_id='".$_user['user_id']."'";
		$sql_update2="UPDATE $table_user_defined_category SET sort='".$source_category['sort']."' WHERE id='".$target_category['id']."' AND user_id='".$_user['user_id']."'";
		Database::query($sql_update2,__FILE__,__LINE__);
		Database::query($sql_update1,__FILE__,__LINE__);
		return get_lang("CategorySortingDone");
	}
	return '';
}

/**
 * displays everything that is needed when the user wants to manage his current courses (sorting, subscribing, unsubscribing, ...)
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $user_id: the user_id of the current user
 *		  string $parameter: determines weither we are displaying for the sorting, subscribing or unsubscribin
 		  array $user_courses:  the courses to which the user is subscribed
 * @return html a table containing courses and the appropriate icons (sub/unsub/move)
*/

function display_courses($user_id, $show_course_icons, $user_courses)
{
	global $_user, $_configuration;

	echo "<table cellpadding=\"4\">\n";

	// building an array that contains all the id's of the user defined course categories
	// initially this was inside the display_courses_in_category function but when we do it here we have fewer
	// sql executions = performance increase.
	$all_user_categories=get_user_course_categories();

	// step 0: we display the course without a user category
	display_courses_in_category(0,'true');

	// Step 1: we get all the categories of the user
	$tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$sql="SELECT * FROM $tucc WHERE user_id='".$_user['user_id']."' ORDER BY sort ASC";
	$result=Database::query($sql,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result))
	{
		if ($show_course_icons=true)
		{

			// the edit link is clicked: we display the edit form for the category
			if (isset($_GET['categoryid']) AND $_GET['categoryid']==$row['id'])
			{
				echo "<tr><td colspan=\"2\"  class=\"user_course_category\">";
				echo '<a name="category'.$row['id'].'"></a>'; // display an internal anchor.
				display_edit_course_category_form($row['id']);
			}
			// we simply display the title of the catgory
			else
			{
				echo "<tr><td colspan=\"2\"  class=\"user_course_category\">";
				echo '<a name="category'.$row['id'].'"></a>'; // display an internal anchor.
				echo $row['title'];
			}
			echo "</td><td class=\"user_course_category\">";
			display_category_icons($row['id'],$all_user_categories);
			echo "</td></tr>";
		}
		// Step 2: show the courses inside this category
		display_courses_in_category($row['id'], $show_course_icons);
	}
	echo "</table>\n";
}

/**
 * This function displays all the courses in the particular user category;
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int id: the id of the user defined course category
 * @return string: the name of the user defined course category
*/
function display_courses_in_category($user_category_id, $showicons)
{
	global $_user;

	// table definitions
	$TABLECOURS=Database::get_main_table(TABLE_MAIN_COURSE);
	$TABLECOURSUSER=Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$TABLE_USER_COURSE_CATEGORY = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);



	$sql_select_courses="SELECT course.code, course.visual_code, course.subscribe subscr, course.unsubscribe unsubscr,
								course.title title, course.tutor_name tutor, course.db_name, course.directory, course_rel_user.status status,
								course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
		                        FROM    $TABLECOURS       course,
										$TABLECOURSUSER  course_rel_user
		                        WHERE course.code = course_rel_user.course_code
		                        AND   course_rel_user.user_id = '".$_user['user_id']."'
		                        AND course_rel_user.user_course_cat='".$user_category_id."'
		                        ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC";
	$result = Database::query($sql_select_courses,__FILE__,__LINE__);
	$number_of_courses=Database::num_rows($result);
	$key=0;
	while ($course=Database::fetch_array($result))
	{
		echo "\t<tr>\n";
		echo "\t\t<td>\n";
		echo '<a name="course'.$course['code'].'"></a>'; // display an internal anchor.
		echo "<b>".$course['title']."</b><br />";
		if (api_get_setting("display_coursecode_in_courselist") == "true")
		{
			echo $course['visual_code'];
		}
		if (api_get_setting("display_coursecode_in_courselist") == "true" AND api_get_setting("display_teacher_in_courselist") == "true")
		{
			echo " - ";
		}
		if (api_get_setting("display_teacher_in_courselist") == "true")
		{
			echo $course['tutor'];
		}
		echo "\t\t</td>\n";
		// displaying the up/down/edit icons when we are sorting courses
		echo "\t\t<td valign=\"top\">\n";
		//if ($parameter=="sorting")
		//{
			display_course_icons($key, $number_of_courses, $course);
		//}
		// displaying the delete icon when we are unsubscribing from courses
		//if($parameter=="deleting")
		//{
		//	display_unsubscribe_icons($course);
		//}
		// display the subscribing icon when we are to courses.
		//if ($parameter=="subscribing")
		//{
		//	display_subscribe_icon($course);
		//}
		echo "\t\t</td>\n";
		echo "\t</tr>\n";
		$key++;
	}
}

/**
 * gets the title of the user course category
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int id: the id of the user defined course category
 * @return string: the name of the user defined course category
*/
function get_user_course_category($id)
{
	global $_user, $_configuration;

	$tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$id = intval($id);
	$sql="SELECT * FROM $tucc WHERE user_id='".$_user['user_id']."' AND id='$id'";
	$result=Database::query($sql,__FILE__,__LINE__);
	$row=Database::fetch_array($result);
	return $row;
}


/**
 * displays the subscribe icon if the subscribing is allowed and if the user is not yet
 * subscribe to this course
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param string $current_course: the course code of the course we need to display the subscribe icon for
 * @return string a subscribe icon or the text that subscribing is not allowed or the user is already subscribed
*/
function display_subscribe_icon($current_course, $user_coursecodes)
{
	global $stok;
	// we display the icon to subscribe or the text already subscribed
	if (in_array($current_course['code'],$user_coursecodes)) {
		Display::display_icon('enroll_na.gif', get_lang('AlreadySubscribed'));
	} else {
		if ($current_course['subscribe'] == SUBSCRIBE_ALLOWED)
		{
			echo "<form action=\"".$_SERVER["REQUEST_URI"]."\" method=\"post\">";
			echo '<input type="hidden" name="sec_token" value="'.$stok.'">';
			echo "<input type=\"hidden\" name=\"subscribe\" value=\"".$current_course['code']."\" />";
			if(!empty($_POST['search_term'])) {
				echo '<input type="hidden" name="search_course" value="1" />';
				echo '<input type="hidden" name="search_term" value="'.Security::remove_XSS($_POST['search_term']).'" />';
			}
			echo "<input style=\"border-color:#fff\" type=\"image\" name=\"unsub\" src=\"../img/enroll.gif\" title=\"".get_lang("Subscribe")."\" alt=\"".get_lang("Subscribe")."\" /></form>";
		} else {
		//	echo get_lang("SubscribingNotAllowed");
			Display::display_icon('enroll_na.gif', get_lang('SubscribingNotAllowed'));

		}
	}
}

/**
 * Displays the subscribe icon if the subscribing is allowed and if the user is not yet
 * subscribed to this course
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param  $key:
 *		   $number_of_courses
 *		   $course
 *		   $user_courses
 * @return html a small table containing the up/down icons and the edit icon (for moving to a different user course category)
 * @todo complete the comments on this function: the parameter section
*/
function display_course_icons($key, $number_of_courses, $course)
{
	//print_r($course);
	global $safe,$charset,$stok;
	echo "<table><tr><td>";
	// the up icon
	if ($key>0)
	{
		echo "<a href=\"courses.php?action=".$safe['action']."&amp;move=up&amp;course=".$course['code']."&amp;category=".$course['user_course_cat']."&amp;sec_token=".$stok."\">";
		Display::display_icon('up.gif', get_lang('Up'));
		echo '</a>';
	}
	echo "</td>";
	// the edit icon OR the edit dropdown list
	if (isset($_GET['edit']) and $course['code']==$_GET['edit'])
	{
		echo "<td rowspan=\"2\" valign=\"top\">".display_change_course_category_form($_GET['edit'])."</td>";
	}
	else
	{
		echo "<td rowspan=\"2\" valign=\"middle\"><a href=\"courses.php?action=".$safe['action']."&amp;edit=".$course['code']."&amp;sec_token=".$stok."\">";
		Display::display_icon('edit.gif',get_lang('Edit'));
		echo "</a></td>";
	}
	echo "<td rowspan=\"2\" valign=\"top\" class=\"invisible\">";
	if ($course['status'] != 1) {
		if ($course['unsubscr'] == 1) {
				// changed link to submit to avoid action by the search tool indexer
				echo	"<form action=\"".api_get_self()."\" method=\"post\" onsubmit=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("ConfirmUnsubscribeFromCourse"),ENT_QUOTES,$charset))."')) return false;\">";
				echo    '<input type="hidden" name="sec_token" value="'.$stok.'">';
				echo 	"<input type=\"hidden\" name=\"unsubscribe\" value=\"".$course['code']."\" />";
				echo 	'<input type="image" name="unsub" style="border-color:#fff"  src="../img/delete.gif" title="'.get_lang("_unsubscribe").'"  alt="'.get_lang("_unsubscribe").'" /></form>';
		} else {
			display_info_text(get_lang("UnsubscribeNotAllowed"));}
	}
	else
	{
		display_info_text(get_lang("CourseAdminUnsubscribeNotAllowed"));
	}
	echo "</td>";
	echo "</tr><tr><td>";
	if ($key<$number_of_courses-1)
	{
		echo "<a href=\"courses.php?action=".$safe['action']."&amp;move=down&amp;course=".$course['code']."&amp;category=".$course['user_course_cat']."&amp;sec_token=".$stok."\">";
		Display::display_icon('down.gif', get_lang('Down'));
		echo '</a>';
	}
	echo "</td></tr></table>";
}

/**
 * displays the relevant icons for the category (if applicable):move up, move down, edit, delete
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param 	$current_category the id of the current category
 * 			$allcategories an associative array containing all the categories.
 * @return html: a small table containing the up/down icons and the edit icon (for moving to a different user course category)
 * @todo complete the comments on this function: the parameter section
*/
function display_category_icons($current_category, $all_user_categories)
{
	global $safe,$charset,$stok;
	$max_category_key=count($all_user_categories);

	if ($safe['action']<>'unsubscribe') // we are in the unsubscribe section then we do not show the icons.
	{
		echo "<table>";
		echo "<tr>";
		echo "<td>";
		if ($current_category<>$all_user_categories[0])
		{
			echo "<a href=\"courses.php?action=".$safe['action']."&amp;move=up&amp;category=".$current_category."&amp;sec_token=".$stok."\">";
			echo Display::return_icon('up.gif', get_lang('Up')).'</a>';
		}
		echo "</td>";
   		echo " <td rowspan=\"2\">";
 		echo " <a href=\"courses.php?action=sortmycourses&amp;categoryid=".$current_category."&amp;sec_token=".$stok."#category".$current_category."\">";
		Display::display_icon('edit.gif',get_lang('Edit'));
		echo "</a>";
   		echo "</td>";
		echo "<td rowspan=\"2\">";
		echo " <a href=\"courses.php?action=deletecoursecategory&amp;id=".$current_category."&amp;sec_token=".$stok."\">";
		Display::display_icon('delete.gif',get_lang('Delete'),array('onclick'=>"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("CourseCategoryAbout2bedeleted"),ENT_QUOTES,$charset))."')) return false;"));
		echo "</a>";
		echo "</td>";
 		echo "</tr>";
  		echo "<tr>";
		echo " <td>";
		if ($current_category<>$all_user_categories[$max_category_key-1])
		{
			echo "<a href=\"courses.php?action=".$safe['action']."&amp;move=down&amp;category=".$current_category."&amp;sec_token=".$stok."\">";
			echo Display::return_icon('down.gif', get_lang('Down')).'</a>';
		}
		echo "</td>";
 		echo " </tr>";
		echo "</table>";
	}
}

/**
 * This function displays the form (dropdown list) to move a course to a
 * different course_category (after the edit icon has been changed)
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param string $edit_course:
 * @return html a dropdown list containing all the user defined course categories and a submit button
 * @todo when editing (moving) a course inside a user defined course category to a different user defined category
 *			the dropdown list should have the current course category selected.
*/
function display_change_course_category_form($edit_course)
{
	global $_user, $_configuration, $safe, $stok;
	$edit_course = Security::remove_XSS($edit_course);

	$DATABASE_USER_TOOLS = $_configuration['user_personal_database'];
	$tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$sql="SELECT * FROM $tucc WHERE user_id='".$_user['user_id']."'";
	$result=Database::query($sql,__FILE__,__LINE__);


	$output="<form name=\"edit_course_category\" method=\"post\" action=\"courses.php?action=".$safe['action']."\">\n";
	$output.= '<input type="hidden" name="sec_token" value="'.$stok.'">';
	$output.="<input type=\"hidden\" name=\"course_2_edit_category\" value=\"".$edit_course."\" />";
	$output.="\t<select name=\"course_categories\">\n";
	$output.="\t\t<option value=\"0\">".get_lang("NoCourseCategory")."</option>";
	while ($row=Database::fetch_array($result))
		{$output.="\t\t<option value=\"".$row['id']."\">".$row['title']."</option>";}
	$output.="\t</select>\n";
	$output.="\t<button class=\"save\" type=\"submit\" name=\"submit_change_course_category\">".get_lang("Ok")." </button>\n";
	$output.="</form>";
	return $output;
}


/**
 * This function displays the unsubscribe part which can be
 * 1. the unsubscribe link
 * 2. text: you are course admin of this course (=> unsubscription is not possible
 * 3. text: you are not allowed to unsubscribe from this course
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param array $course: the array with all the course & course_rel_user information
 * @return html a delete icon or a text that unsubscribing is not possible (course admin) or not allowed.
*/
function display_unsubscribe_icons($course)
{
	global $charset, $stok;
	if ($course['status'] != 1)
	{
		if ($course['unsubscribe'] == 1)
			{	// changed link to submit to avoid action by the search tool indexer
				echo	"<form action=\"".api_get_self()."\" method=\"post\" onsubmit=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("ConfirmUnsubscribeFromCourse"),ENT_QUOTES,$charset))."')) return false;\">";
				echo    '<input type="hidden" name="sec_token" value="'.$stok.'">';
				echo 	"<input type=\"hidden\" name=\"unsubscribe\" value=\"".$course['code']."\" />";
				echo 	"<input type=\"image\" name=\"unsub\" src=\"../img/delete.gif\" alt=\"".get_lang("_unsubscribe")."\" /></form>";
			}
		else
			{display_info_text(get_lang("UnsubscribeNotAllowed"));}
	}
	else
	{
		display_info_text(get_lang("CourseAdminUnsubscribeNotAllowed"));
	}
}


/**
 * retrieves all the courses that the user has already subscribed to
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $user_id: the id of the user
 * @return array an array containing all the information of the courses of the given user
*/
function get_courses_of_user($user_id)
{
	$TABLECOURS=Database::get_main_table(TABLE_MAIN_COURSE);
	$TABLECOURSUSER=Database::get_main_table(TABLE_MAIN_COURSE_USER);

	// Secondly we select the courses that are in a category (user_course_cat<>0) and sort these according to the sort of the category
	$user_id = intval($user_id);
	$sql_select_courses="SELECT course.code k, course.visual_code  vc, course.subscribe subscr, course.unsubscribe unsubscr,
								course.title i, course.tutor_name t, course.db_name db, course.directory dir, course_rel_user.status status,
								course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
		                        FROM    $TABLECOURS       course,
										$TABLECOURSUSER  course_rel_user
		                        WHERE course.code = course_rel_user.course_code
		                        AND   course_rel_user.user_id = '".$user_id."'
		                        ORDER BY course_rel_user.sort ASC";
	$result = Database::query($sql_select_courses,__FILE__,__LINE__);
	while ($row=Database::fetch_array($result))
	{
		// we only need the database name of the course
		$courses[]=array("db"=> $row['db'], "code" => $row['k'], "visual_code" => $row['vc'], "title" => $row['i'], "directory" => $row['dir'], "status" => $row['status'], "tutor" => $row['t'], "subscribe" => $row['subscr'], "unsubscribe" => $row['unsubscr'], "sort" => $row['sort'], "user_course_category" => $row['user_course_cat']);
		}

	return $courses;
}

/**
 * retrieves the user defined course categories
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return array containing all the IDs of the user defined courses categories, sorted by the "sort" field
*/
function get_user_course_categories()
{
	global $_user;
	$table_category = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$sql = "SELECT * FROM ".$table_category." WHERE user_id='".$_user['user_id']."' ORDER BY sort ASC";
	$result = Database::query($sql,__FILE__,__LINE__);
	while ($row = Database::fetch_array($result))
	{
		$output[] = $row['id'];
	}
	return $output;
}


/**
 * Retrieves the user defined course categories and all the info that goes with it
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return array containing all the info of the user defined courses categories with the id as key of the array
*/
function get_user_course_categories_info()
{
	global $_user;
	$table_category = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$sql = "SELECT * FROM ".$table_category." WHERE user_id='".$_user['user_id']."' ORDER BY sort ASC";
	$result = Database::query($sql,__FILE__,__LINE__);
	while ($row = Database::fetch_array($result))
	{
		$output[$row['id']] = $row;
	}
	return $output;
}

/**
 * @author unknown
 * @param string $text: the text that has to be written in grey
 * @return string: the text with the grey formatting
 * @todo move this to a stylesheet
 * Added id grey to CSS
*/
function display_info_text($text)
{
	//echo "<font color=\"#808080\">" . $text . "</font>\n";
	echo $text;
}

/**
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param string $edit_course:
 * @return html output: the form
*/
function display_edit_course_category_form($edit_course_category)
{
	global $safe, $stok;
	echo "<form name=\"edit_course_category\" method=\"post\" action=\"courses.php?action=".$safe['action']."\">\n";
	echo "\t<input type=\"hidden\" name=\"edit_course_category\" value=\"".$edit_course_category."\" />\n";
	echo '<input type="hidden" name="sec_token" value="'.$stok.'">';
	$info_this_user_course_category=get_user_course_category($edit_course_category);
	echo "\t<input type=\"text\" name=\"title_course_category\" value=\"".$info_this_user_course_category['title']."\" />";
	echo "\t<button class=\"save\" type=\"submit\" name=\"submit_edit_course_category\">".get_lang("Ok")." </button>\n";
	echo "</form>";
}

/**
 * Updates the user course category in the dokeos_user database
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return string a language variable saying that the user course category was stored
*/
function store_edit_course_category()
{
	global $_user, $_configuration, $charset;

	$tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$sql_update="UPDATE $tucc SET title='".api_htmlentities($_POST['title_course_category'],ENT_QUOTES,$charset)."' WHERE id='".(int)$_POST['edit_course_category']."'";
	Database::query($sql_update,__FILE__,__LINE__);
	return get_lang("CourseCategoryEditStored");
}
?>
