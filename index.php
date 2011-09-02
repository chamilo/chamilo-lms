<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.main
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Refactoring
 * @todo check the different @todos in this page and really do them
 * @todo check if the news management works as expected
 */

// Only this script should have this constant defined. This is used to activate the javascript that
// gives the login name automatic focus in header.inc.html.
/** @todo Couldn't this be done using the $HtmlHeadXtra array? */
define('CHAMILO_HOMEPAGE', true);

$language_file = array('courses', 'index');

/* Flag forcing the 'current course' reset, as we're not inside a course anymore. */
// Maybe we should change this into an api function? an example: Coursemanager::unset();
$cidReset = true;

/* Included libraries */

/** @todo Make all the library files consistent, use filename.lib.php and not filename.lib.inc.php. */
require_once 'main/inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'system_announcements.lib.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

require_once api_get_path(LIBRARY_PATH).'userportal.lib.php';

require_once 'main/chat/chat_functions.lib.php';

$loginFailed = isset($_GET['loginFailed']) ? true : isset($loginFailed);
$setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';

// The section (for the tabs).
$this_section = SECTION_CAMPUS;
unset($_SESSION['this_section']);//for hmtl editor repository

/* Action Handling */

/** @todo   Wouldn't it make more sense if this would be done in local.inc.php so that local.inc.php become the only place where authentication is done?
 *          by doing this you could logout from any page instead of only from index.php. From the moment there is a logout=true in the url you will be logged out
 *          this can be usefull when you are on an open course and you need to log in to edit something and you immediately want to check how anonymous users
 *          will see it.
 */
$my_user_id = api_get_user_id();

if (!empty($_GET['logout'])) {
    logout();
}

/* Table definitions */
$main_course_table      = Database :: get_main_table(TABLE_MAIN_COURSE);
$main_category_table    = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$track_login_table      = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

/* Constants and CONFIGURATION parameters */
/** @todo these configuration settings should move to the Chamilo config settings. */

/** Defines wether or not anonymous visitors can see a list of the courses on the Chamilo homepage that are open to the world. */
$_setting['display_courses_to_anonymous_users'] = 'true';

/** @todo Remove this piece of code because this is not used. */
if (isset($_user['user_id'])) {
    $nameTools = api_get_setting('siteName');
}

/* LOGIN */

/**
 * @todo This piece of code should probably move to local.inc.php where the actual login / logout procedure is handled.
 * @todo Consider removing this piece of code because does nothing.
 */
if (isset($_GET['submitAuth']) && $_GET['submitAuth'] == 1) {
    // nice lie!!!
    echo 'Attempted breakin - sysadmins notified.';
    session_destroy();
    die();
}

// Delete session neccesary for legal terms
if (api_get_setting('allow_terms_conditions') == 'true') {
    unset($_SESSION['update_term_and_condition']);
    unset($_SESSION['info_current_user']);
}

/**
 * @todo This piece of code should probably move to local.inc.php where the actual login procedure is handled.
 * @todo Check if this code is used. I think this code is never executed because after clicking the submit button
 *       the code does the stuff in local.inc.php and then redirects to index.php or user_portal.php depending
 *       on api_get_setting('page_after_login').
 */

if (!empty($_POST['submitAuth'])) {
    // The user has been already authenticated, we are now to find the last login of the user.
    if (isset ($_user['user_id'])) {
        $sql_last_login = "SELECT UNIX_TIMESTAMP(login_date)
                                FROM $track_login_table
                                WHERE login_user_id = '".$_user['user_id']."'
                                ORDER BY login_date DESC LIMIT 1";
        $result_last_login = Database::query($sql_last_login);
        if (!$result_last_login) {
            if (Database::num_rows($result_last_login) > 0) {
                $user_last_login_datetime = Database::fetch_array($result_last_login);
                $user_last_login_datetime = $user_last_login_datetime[0];
                api_session_register('user_last_login_datetime');
            }
        }
        Database::free_result($result_last_login);

        //event_login();
        if (api_is_platform_admin()) {
            // decode all open event informations and fill the track_c_* tables
            include api_get_path(LIBRARY_PATH).'stats.lib.inc.php';
            decodeOpenInfos();
        }
    }
    // End login -- if ($_POST['submitAuth'])
} else {
    // Only if login form was not sent because if the form is sent the user was already on the page.
    event_open();
}

// The header.
/*$header_title = get_lang('Homepage');
//$sitename = api_get_setting('siteName');
if (!api_get_user_id()) { 
    $header_title = null;
}*/
$header_title = null;
if (!api_is_anonymous()) {
    $header_title = " ";
}

$htmlHeadXtra[] = api_get_jquery_libraries_js(array('bxslider'));
$htmlHeadXtra[] ='
<script type="text/javascript">
$(document).ready(function(){
	$("#slider").bxSlider({
		infiniteLoop	: true,
		auto			: true,
		pager			: true,
		autoHover		: true,
		pause			: 10000
	});
});
</script>';

Display::display_header($header_title);


$index = new IndexManager($header_title, false);


/* MAIN CODE */

echo '<div id="content" class="maincontent">';

//check if javascript is enabled
echo '<noscript>';
echo Display::display_error_message(get_lang("NoJavascript"));
echo '</noscript>';

//check if cookies are enabled
?>
<script language="JavaScript">
if(navigator.cookieEnabled==false){
        document.writeln('<?php Display::display_error_message(get_lang("NoCookies")); ?>');
}
</script>
<?php


// Plugins for loginpage_main AND campushomepage_main.
if (!api_get_user_id()) {
    api_plugin('loginpage_main');
} else {
    api_plugin('campushomepage_main');
}

echo $index->return_home_page();


// Display courses and category list.
//if (!$page_included) {

    // Display System announcements
    $announcement = isset($_GET['announcement']) ? $_GET['announcement'] : -1;
    $announcement = intval($announcement);

    if (isset($_user['user_id'])) {
        $visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
        SystemAnnouncementManager :: display_announcements_slider($visibility, $announcement);
    } else {
        SystemAnnouncementManager :: display_announcements_slider(VISIBLE_GUEST, $announcement);
    }

    if (api_get_setting('display_categories_on_homepage') == 'true') {
        echo '<div class="home_cats">';
        $index->display_anonymous_course_list();
        echo '</div>';
    }
//}

echo '</div>';


echo '<div id="menu-wrapper">';

echo $index->return_profile_block();

// Display right menu: language form, login section + useful weblinks.
$index->display_anonymous_right_menu();

echo '</div>';

/* Footer */

Display :: display_footer();