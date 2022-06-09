<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';
require_once 'userInfoLib.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$TABLECALHORAIRE = Database::get_course_table(cal_horaire);
$htmlHeadXtra[] = '<script>
function show_image(image,width,height) {
	width = parseInt(width) + 20;
	height = parseInt(height) + 20;
	window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
}
</script>';

$editMainUserInfo = Security::remove_XSS($_REQUEST['editMainUserInfo']);
$uInfo = $editMainUserInfo;
$this_section = SECTION_COURSES;

$nameTools = get_lang('Users');
api_protect_course_script(true);
$tool_info = api_get_tool_information_by_name(TOOL_USER);

if (api_is_anonymous()) {
    api_not_allowed(true);
}

//prepare variables used in userInfoLib.php functions
$TBL_USERINFO_DEF = Database::get_course_table(TABLE_USER_INFO_DEF);
$TBL_USERINFO_CONTENT = Database::get_course_table(TABLE_USER_INFO_CONTENT);

if ($tool_info['visibility'] == 1) {
    $interbreadcrumb[] = ['url' => 'user.php', 'name' => get_lang('Users')];
}

if ($origin != 'learnpath') { //so we are not in learnpath tool
    Display::display_header($nameTools, "User");
    $origin = Security::remove_XSS($_GET['origin']);
} else {
    ?> <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH); ?>css/default.css" /> <?php
}

$currentCourse = api_get_course_id();
$current_session_id = api_get_session_id();
/*
 * data  found  in settings  are :
 *	$uid
 *	$isAdmin
 *	$isAdminOfCourse
 *	$_configuration['main_database']
 *	$currentCourseID
 */

$userIdViewed = Security::remove_XSS($_REQUEST['uInfo']);

/**
 * Connection layer between Chamilo and the current script.
 */
$courseCode = api_get_course_id();
$userIdViewer = api_get_user_id(); // id fo the user currently online
$allowedToEditContent = ($userIdViewer == $userIdViewed) || $is_platformAdmin;
$allowedToEditDef = api_is_allowed_to_edit(null, true);
$is_allowedToTrack = api_is_allowed_to_edit(null, true);
$displayMode = "viewContentList";
$removeDef = Security::remove_XSS($_GET['removeDef']);
$editDef = Security::remove_XSS($_GET['editDef']);
$moveUpDef = Security::remove_XSS($_GET['moveUpDef']);
$moveDownDef = Security::remove_XSS($_GET['moveDownDef']);

if ($allowedToEditDef) {
    if (!empty($_POST['submitDef'])) {
        if (!empty($_POST['id'])) {
            edit_cat_def($_POST['id'], $_POST['title'], $_POST['comment'], $_POST['nbline']);
        } else {
            create_cat_def($_POST['title'], $_POST['comment'], $_POST['nbline']);
        }
        $displayMode = "viewDefList";
    } elseif (!empty($_GET['removeDef'])) {
        remove_cat_def($_GET['removeDef'], true);
        $displayMode = "viewDefList";
    } elseif (!empty($_GET['editDef'])) {
        $displayMode = "viewDefEdit";
    } elseif (!empty($_POST['addDef'])) {
        $displayMode = "viewDefEdit";
    } elseif (!empty($_GET['moveUpDef'])) {
        move_cat_rank($_GET['moveUpDef'], "up");
        $displayMode = "viewDefList";
    } elseif (!empty($_GET['moveDownDef'])) {
        move_cat_rank($_GET['moveDownDef'], "down");
        $displayMode = "viewDefList";
    } elseif (!empty($_POST['viewDefList'])) {
        $displayMode = "viewDefList";
    } elseif (!empty($_GET['editMainUserInfo'])) {
        $userIdViewed = strval(intval($_GET['editMainUserInfo']));
        $displayMode = "viewMainInfoEdit";
    } elseif (!empty($_REQUEST['submitMainUserInfo'])) {
        $userIdViewed = strval(intval($_REQUEST['submitMainUserInfo']));
        if ($current_session_id) {
        } else {
            if (!empty($_POST['promoteCourseAdmin']) && $_POST['promoteCourseAdmin']) {
                $userProperties['status'] = 1;
            } else {
                $userProperties['status'] = 5;
            }
            if (!empty($_POST['promoteTutor']) && $_POST['promoteTutor']) {
                $userProperties['tutor'] = 1;
            } else {
                $userProperties['tutor'] = 0;
            }
            $userhoraire_name = $_POST['hor_name'];
            $course_id = $_course['real_id'];
            update_user_course_properties($userIdViewed, $courseCode, $userProperties, $userhoraire_name, $course_id);
        }
        $displayMode = "viewContentList";
    }
}

if ($allowedToEditContent) {
    if (isset($_POST['submitContent'])) {
        if ($_POST['cntId']) {
            // submit a content change
            edit_cat_content($_POST['catId'], $userIdViewed, $_POST['content'], $_SERVER['REMOTE_ADDR']);
        } else {
            // submit a totally new content
            fill_new_cat_content($_POST['catId'], $userIdViewed, $_POST['content'], $_SERVER['REMOTE_ADDR']);
        }
        $displayMode = "viewContentList";
    } elseif (!empty($_GET['editContent'])) {
        $displayMode = "viewContentEdit";
        $userIdViewed = $userIdViewed;
    }
}
if (api_is_allowed_to_edit(null, true)) {
    echo '<div class="actions">';
    if ($origin == 'users') {
        echo '<a href="user.php?'.api_get_cidreq().'&origin='.$origin.'">'.
            Display::return_icon('back.png', get_lang('BackUser'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        echo '<a href="javascript:history.back(1)">'.
            Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
    }

    if (!is_numeric($_GET['editMainUserInfo'])) {
        if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'true') {
            echo '<a href="userInfo.php?'.api_get_cidreq(
                ).'&origin='.$origin.'&editMainUserInfo='.$userIdViewed.'">'.
                Display::return_icon('edit.png', get_lang('EditUser'), '', ICON_SIZE_MEDIUM).get_lang(
                    'EditUser'
                ).'</a>';
        }
    } else {
        if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'true') {
            echo '<a href="userInfo.php?'.api_get_cidreq().'&origin='.$origin.'&uInfo='.$userIdViewed.'">'.
                Display::return_icon(
                    'user.png',
                    get_lang('ViewUser'),
                    '',
                    ICON_SIZE_MEDIUM
                ).get_lang('ViewUser').'</a>';
        }
    }
    echo '<a href="../mySpace/myStudents.php?'.api_get_cidreq().'&origin=user_course&student='.$userIdViewed.'&details=true&course='.$_course['id'].'">'.Display::return_icon('statistics.png', get_lang('UserStatistics'), '', ICON_SIZE_MEDIUM).get_lang('UserStatistics').'</a>';
    echo '</div>';
} else {
    if ($tool_info['visibility'] == 1) {
        echo '<div class="actions">';
        if ($origin == 'users') {
            echo '<a href="user.php?'.api_get_cidreq().'&origin='.$origin.'">'.
                Display::return_icon(
                    'back.png',
                    get_lang('BackUser'),
                    '',
                    ICON_SIZE_MEDIUM
                ).'</a>';
        } else {
            echo '<a href="javascript:history.back(1)">'.Display::return_icon(
                    'back.png',
                    get_lang('Back'),
                    '',
                    ICON_SIZE_MEDIUM
                ).'</a>';
        }
        echo '</div>';
    }
}

// get information about user id viewed
$user_info_viewed = api_get_user_info($userIdViewed);
$is_session_course_coach = UserManager::is_session_course_coach(
    $userIdViewed,
    $_course['sysCode'],
    $current_session_id
);

if ($displayMode == "viewDefEdit") {
    /* CATEGORIES DEFINITIONS : EDIT */
    $catToEdit = get_cat_def($_GET['editDef']);
    $edit_heading_form = new FormValidator('edit_heading_form');
    $edit_heading_form->addElement('hidden', 'id');
    $edit_heading_form->add_textfield('title', get_lang('Title'));
    $edit_heading_form->addElement('textarea', 'comment', get_lang('Comment'), ['cols' => 60, 'rows' => 4]);
    $possible_line_nrs[1] = '1 '.get_lang('Line');
    $possible_line_nrs[3] = '3 '.get_lang('Lines');
    $possible_line_nrs[5] = '5 '.get_lang('Lines');
    $possible_line_nrs[10] = '10 '.get_lang('Lines');
    $possible_line_nrs[15] = '15 '.get_lang('Lines');
    $edit_heading_form->addElement('select', 'nbline', get_lang('LineNumber'), $possible_line_nrs);
    $edit_heading_form->addElement('submit', 'submitDef', get_lang('Ok'));
    $edit_heading_form->setDefaults($catToEdit);
    $edit_heading_form->display();
} elseif ($displayMode == "viewDefList") {
    $catList = get_cat_def_list();
    if ($catList) {
        foreach ($catList as $thisCat) {
            // displays Title and comments
            echo "<p>", "<b>".htmlize($thisCat['title'])."</b><br>\n", "<i>".htmlize($thisCat['comment'])."</i>\n", "</p>";
            // displays lines
            echo "<blockquote>\n", "<font color=\"gray\">\n";
            for ($i = 1; $i <= $thisCat['nbline']; $i++) {
                echo "<br>__________________________________________\n";
            }
            echo "</font>\n", "</blockquote>\n";
            // displays commands
            echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&removeDef=", $thisCat['catId'], "\">", "<img src=\"../img/delete.gif\" border=\"0\" alt=\"".get_lang('Remove')."\" onclick=\"javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset))."')) return false;\">", "</a>", "<a href=\"".api_get_self()."?".api_get_cidreq()."&editDef=", $thisCat['catId'], "\">", "<img src=\"../img/edit.gif\" border=\"0\" alt=\"".get_lang('Edit')."\" />", "</a>", "<a href=\"".api_get_self()."?".api_get_cidreq()."&moveUpDef=", $thisCat['catId'], "\">", "<img src=\"../img/up.gif\" border=\"0\" alt=\"".get_lang('MoveUp')."\">", "</a>", "<a href=\"".api_get_self()."?".api_get_cidreq()."&moveDownDef=", $thisCat['catId'], "\">", "<img src=\"../img/down.gif\" border=\"0\" alt=\"".get_lang('MoveDown')."\">", "</a>\n";
        } // end for each
    } // end if ($catList)

    echo "<center>\n",
        "<form method=\"post\" action=\"".api_get_self()."\">",
        "<input type=\"submit\" name=\"addDef\" class=\"plus\" value=\"".get_lang('AddNewHeading')."\" />",
    "</form>\n",
    "<center>\n";
} elseif ($displayMode == 'viewContentEdit') {
    /* CATEGORIES CONTENTS : EDIT */
    $catToEdit = get_cat_content($userIdViewed, $_GET['editContent']);
    $content_heading_form = new FormValidator('content_heading_form');
    $content_heading_form->addElement('hidden', 'cntId');
    $content_heading_form->addElement('hidden', 'catId');
    $content_heading_form->addElement('hidden', 'uInfo');
    $content_heading_form->addElement('static', null, $catToEdit['title'], htmlize($catToEdit['comment']));
    if ($catToEdit['nbline'] == 1) {
        $content_heading_form->addElement('text', 'content', null, ['size' => 80]);
    } else {
        $content_heading_form->addElement(
            'textarea',
            'content',
            null,
            ['cols' => 60, 'rows' => $catToEdit['nbline']]
        );
    }
    $content_heading_form->addElement('submit', 'submitContent', get_lang('Ok'));
    $defaults = $catToEdit;
    $defaults['cntId'] = $catToEdit['contentId'];
    $defaults['uInfo'] = $userIdViewed;
    $content_heading_form->setDefaults($defaults);
    $content_heading_form->display();
} elseif ($displayMode == "viewMainInfoEdit") {
    $mainUserInfo = api_get_user_info($userIdViewed);
    if ($mainUserInfo) {
        ($mainUserInfo['status'] == COURSEMANAGER) ? $courseAdminChecked = "checked" : $courseAdminChecked = "";
        $image_array = UserManager::get_user_picture_path_by_id($userIdViewed, 'web', false, true);
        // get the path,width and height from original picture
        $big_image = $image_array['dir'].'big_'.$image_array['file'];
        $big_image_size = api_getimagesize($big_image);
        $big_image_width = $big_image_size['width'];
        $big_image_height = $big_image_size['height'];
        $url_big_image = $big_image.'?rnd='.time();

        if ($image_array['file'] == 'unknown.jpg') {
            echo '<img src="'.$image_array['dir'].$image_array['file'].'" border="1">';
        } else {
            echo '<input type="image" src="'.$image_array['dir'].$image_array['file'].'" onclick="return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
        }

        echo "<form action=\"".api_get_self()."\" method=\"post\">\n",
            "<input type=\"hidden\" name=\"submitMainUserInfo\" value=\"$userIdViewed\" />\n",
            "<table width=\"80%\" border=\"0\">",
            "<tr align=\"center\" bgcolor=\"#E6E6E6\">\n",
            "<td align=\"left\">", get_lang('Name'), "</td>\n",
            "<td width=\"100px\" align=\"left\">", get_lang('Schedule'), "</td>\n";
        echo "</tr>\n",
            "<tr align=\"center\">",
            "<td align=\"left\"><b>", htmlize(api_get_person_name($mainUserInfo['firstName'], $mainUserInfo['lastName'])), "</b></td>\n",
         "<td align=\"left\"><b>", $mainUserInfo['official_code'], "</td>";
        //récupérer horaire de l'élève
        $course_id = $_course['real_id']; ?>
        <SELECT NAME='hor_name'>
            <OPTION VALUE='<?php echo $mainUserInfo['official_code']; ?>'><?php echo get_lang('select_calendar_student'); ?></OPTION>
            <?php
            $sql = "SELECT distinct name FROM $TABLECALHORAIRE
            where c_id = $course_id ";
        $result2 = Database::query($sql);
        while ($row = Database::fetch_array($result2)) {
            $horaire_name = $row["name"]; ?>
                    <OPTION VALUE='<?php echo $horaire_name; ?>'><?php echo "$horaire_name "; ?></OPTION>
            <?php
        } ?>
        </SELECT>
         <?php
         echo '<p></p>  ';
        if (!($is_courseAdmin && $_user['user_id'] == $userIdViewed)) {
        } else {
            echo "<td>", get_lang('CourseManager'), "</td>\n";
        }
        echo "<td><button class=\"save\" type=\"submit\" name=\"submit\">".get_lang('SaveChanges')."</button></td>\n", "</tr>", "</table>", "</form>\n";
        if (api_get_setting('show_email_addresses') == 'true') {
            echo "<p>".Display::encrypted_mailto_link($mainUserInfo['email'], $mainUserInfo['email'])."</p>";
        }

        if (api_get_setting('extended_profile') == 'true') {
            //    MY PERSONAL OPEN AREA
            if (!empty($mainUserInfo['openarea'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div><div>'.$mainUserInfo['openarea'].'</div>';
            }
            //    MY COMPETENCES
            if (!empty($mainUserInfo['competences'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyCompetences').'</strong></div><div>'.$mainUserInfo['competences'].'</div>';
            }
            //    MY DIPLOMAS
            if (!empty($mainUserInfo['diplomas'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div><div>'.$mainUserInfo['diplomas'].'</div>';
            }
            // WHAT I AM ABLE TO TEACH
            if (!empty($mainUserInfo['teach'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyTeach').'</strong></div><div>'.$mainUserInfo['teach'].'</div>';
            }
            //    MY PRODUCTIONS
            if (!empty($mainUserInfo['competences'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyProductions').'</strong></div><div>'.UserManager::build_production_list($mainUserInfo['user_id']).'</div>';
            }
        }
    } else {
        Display::return_message(get_lang('ThisStudentIsSubscribeThroughASession'));
    }
} elseif ($displayMode == "viewContentList") {
    // default display
    $virtual_course_code = $_GET["virtual_course"];
    if (isset($virtual_course_code)) {
        $courseCode = $virtual_course_code;
        $allowedToEditDef = false;
    }
    $mainUserInfo = api_get_user_info($userIdViewed);
    if ($mainUserInfo) {
        $image_array = UserManager::get_user_picture_path_by_id($userIdViewed, 'web', false, true);
        // get the path,width and height from original picture
        $big_image = $image_array['dir'].'big_'.$image_array['file'];
        $big_image_size = api_getimagesize($big_image);
        $big_image_width = $big_image_size['width'];
        $big_image_height = $big_image_size['height'];
        $url_big_image = $big_image.'?rnd='.time();

        if ($image_array['file'] == 'unknown.jpg') {
            echo '<img src="'.$image_array['dir'].$image_array['file'].'" border="1">';
        } else {
            echo '<input type="image" src="'.$image_array['dir'].$image_array['file'].'" onclick="return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
        }

        // is the user online?
        $online = '';
        if (user_is_online($userIdViewed)) {
            $online = Display::return_icon('online.gif', get_lang('OnLine'), ['style' => 'with="8"; height="8"']);
        }

        // DISPLAY TABLE HEADING
        if ($origin == 'learnpath') {
            $allowedToEditDef = false;
            $is_allowedToTrack = false;
        }

        echo "<table width=\"80%\" border=\"0\">",
        "<tr align=\"center\" bgcolor=\"#E6E6E6\">\n",
            "<td align=\"left\">".get_lang('Name')."</td>\n",
                "<td width=\"20%\" align=\"left\">".get_lang('Description')."</td>\n",
         "<td width=\"100px\" align=\"left\">", get_lang('OfficialCode'), "</td>\n";

        echo "</tr>\n",
        "<tr align=\"center\">\n",
            "<td  align=\"left\"><b>".$online.' '.htmlize(api_get_person_name($mainUserInfo['firstName'], $mainUserInfo['lastName']))."</b></td>\n",
        "<td align=\"left\"><b>", $mainUserInfo['official_code'], "</td>";
        // DISPLAY TABLE CONTENT
        if ($current_session_id) {
            if ($user_info_viewed['status'] == COURSEMANAGER) {
                echo "<td> - </td>\n";
            }
        } else {
            if ($user_info_viewed['status'] == STUDENT) {
                echo "<td> - </td>\n";
            } else {
                echo "<td> - </td>\n";
            }
        }

        echo "</tr></table>";
        if (api_get_setting("show_email_addresses") == "true") {
            echo "<p>".Display::encrypted_mailto_link($mainUserInfo['email'], $mainUserInfo['email'])."</p>";
        }

        if (api_get_setting('extended_profile') == 'true') {
            //    MY PERSONAL OPEN AREA
            if (!empty($mainUserInfo['openarea'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyPersonalOpenArea').'</strong></div><div>'.$mainUserInfo['openarea'].'</div>';
            }
            //    MY COMPETENCES
            if (!empty($mainUserInfo['competences'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyCompetences').'</strong></div><div>'.$mainUserInfo['competences'].'</div>';
            }
            //    MY DIPLOMAS
            if (!empty($mainUserInfo['diplomas'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyDiplomas').'</strong></div><div>'.$mainUserInfo['diplomas'].'</div>';
            }
            // WHAT I AM ABLE TO TEACH
            if (!empty($mainUserInfo['teach'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyTeach').'</strong></div><div>'.$mainUserInfo['teach'].'</div>';
            }
            //    MY PRODUCTIONS
            if (!empty($mainUserInfo['competences'])) {
                echo '<div style="margin-top:10px;" class="actions-message"><strong>'.get_lang('MyProductions').'</strong></div><div>'.UserManager::build_production_list($mainUserInfo['user_id']).'</div>';
            }
        }
    } else {
        Display::return_message(get_lang('ThisStudentIsSubscribeThroughASession'), 'normal');
    }

    if (api_get_setting('allow_user_headings') == 'true' && $allowedToEditDef) {
        // only course administrators see this line
        echo "<div align=right>",
            "<form method=\"post\" action=\"".api_get_self()."\">",
        get_lang('CourseAdministratorOnly')," : ",
            "<input type=\"submit\" class=\"save\" name=\"viewDefList\" value=\"".get_lang('DefineHeadings')."\" />",
        "</form>",
        "<hr noshade size=\"1\" style=\"color:#99CCFF\">",
        "</div>\n";
    }

    $catList = get_course_user_info($userIdViewed);
    if ($catList) {
        foreach ($catList as $thisCat) {
            // Category title
            echo "<p><b>", $thisCat['title'], "</b></p>\n";
            // Category content
            echo "<blockquote>\n";
            if ($thisCat['content']) {
                echo htmlize($thisCat['content'])."\n";
            } else {
                echo "....";
            }
            // Edit command
            if ($allowedToEditContent) {
                echo "<br /><br />\n",
                    "<a href=\"".api_get_self()."?".api_get_cidreq(
                    )."&editContent=", $thisCat['catId'], "&uInfo=", $userIdViewed, "\">",
                "<img src=\"../img/edit.gif\" border=\"0\" alt=\"edit\">",
                "</a>\n";
            }
            echo "</blockquote>\n";
        }
    }
}

Display::display_footer();
