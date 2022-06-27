<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.admin
 */
// resetting the course id
$cidReset = true;

// including some necessary files
require_once __DIR__.'/../inc/global.inc.php';
$xajax = new xajax();
$xajax->registerFunction('search_users');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

$id_session = isset($_GET['id_session']) ? (int) $_GET['id_session'] : 0;
if (empty($id_session)) {
    api_not_allowed(true);
}
$addProcess = isset($_GET['add']) ? Security::remove_XSS($_GET['add']) : null;

SessionManager::protectSession($id_session);

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('SessionList')];
$interbreadcrumb[] = [
    'url' => "resume_session.php?id_session=".$id_session,
    "name" => get_lang('SessionOverview'),
];

// Database Table Definitions
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

// setting the name of the tool
$tool_name = get_lang('SubscribeUsersToSession');
$add_type = 'unique';
if (isset($_REQUEST['add_type']) && $_REQUEST['add_type'] != '') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$page = isset($_GET['page']) ? Security::remove_XSS($_GET['page']) : null;

// Checking for extra field with filter on

$extra_field_list = UserManager::get_extra_fields();

$new_field_list = [];
if (is_array($extra_field_list)) {
    foreach ($extra_field_list as $extra_field) {
        //if is enabled to filter and is a "<select>" field type
        if ($extra_field[8] == 1 && $extra_field[2] == ExtraField::FIELD_TYPE_SELECT) {
            $new_field_list[] = [
                'name' => $extra_field[3],
                'type' => $extra_field[2],
                'variable' => $extra_field[1],
                'data' => $extra_field[9],
            ];
        }
        if ($extra_field[8] == 1 && $extra_field[2] == ExtraField::FIELD_TYPE_TAG) {
            $options = UserManager::get_extra_user_data_for_tags($extra_field[1]);
            $new_field_list[] = [
                'name' => $extra_field[3],
                'type' => $extra_field[2],
                'variable' => $extra_field[1],
                'data' => $options['options'],
            ];
        }
    }
}

function search_users($needle, $type)
{
    global $id_session;

    $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
    $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

    $xajax_response = new xajaxResponse();
    $return = '';

    if (!empty($needle) && !empty($type)) {
        // Normal behaviour
        if ($type == 'any_session' && $needle == 'false') {
            $type = 'multiple';
            $needle = '';
        }

        $needle = Database::escape_string($needle);
        $order_clause = ' ORDER BY lastname, firstname, username';
        $showOfficialCode = false;

        $orderListByOfficialCode = api_get_setting('order_user_list_by_official_code');
        if ($orderListByOfficialCode === 'true') {
            $showOfficialCode = true;
            $order_clause = ' ORDER BY official_code, lastname, firstname, username';
        }

        if (api_is_session_admin()
            && api_get_setting('prevent_session_admins_to_manage_all_users') === 'true'
        ) {
            $order_clause = " AND user.creator_id = ".api_get_user_id().$order_clause;
        }

        $cond_user_id = '';

        // Only for single & multiple
        if (in_array($type, ['single', 'multiple'])) {
            if (!empty($id_session)) {
                $id_session = intval($id_session);
                // check id_user from session_rel_user table
                $sql = "
                    SELECT user_id FROM $tbl_session_rel_user
                    WHERE session_id = $id_session AND relation_type <> ".SESSION_RELATION_TYPE_RRHH;
                $res = Database::query($sql);
                $user_ids = [];
                if (Database::num_rows($res) > 0) {
                    while ($row = Database::fetch_row($res)) {
                        $user_ids[] = (int) $row[0];
                    }
                }
                if (count($user_ids) > 0) {
                    $cond_user_id = ' AND user.id NOT IN('.implode(",", $user_ids).')';
                }
            }
        }

        switch ($type) {
            case 'single':
                // search users where username or firstname or lastname begins likes $needle
                $sql = "
                    SELECT user.id, username, lastname, firstname, official_code
                    FROM $tbl_user user
                    WHERE
                        (
                            username LIKE '$needle%'
                            OR lastname LIKE '$needle%'
                            OR firstname LIKE '$needle%'
                        )
                        AND user.status <> 6
                        AND user.status <> ".DRH."
                    $order_clause LIMIT 11
                ";
                break;
            case 'multiple':
                $sql = "
                    SELECT user.id, username, lastname, firstname, official_code
                    FROM $tbl_user user
                    WHERE
                        lastname LIKE '$needle%'
                        AND user.status <> ".DRH."
                        AND user.status <> 6 $cond_user_id
                    $order_clause
                ";
                break;
            case 'any_session':
                $sql = "
                    SELECT DISTINCT user.id, username, lastname, firstname, official_code
                    FROM $tbl_user user
                    LEFT OUTER JOIN $tbl_session_rel_user s ON (s.user_id = user.id)
                    WHERE
                        s.user_id IS NULL
                        AND user.status <> ".DRH."
                        AND user.status <> 6 $cond_user_id
                    $order_clause
                ";
                break;
        }

        if (api_is_multiple_url_enabled()) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                switch ($type) {
                    case 'single':
                        $sql = "
                            SELECT user.id, username, lastname, firstname, official_code
                            FROM $tbl_user user
                            INNER JOIN $tbl_user_rel_access_url url_user
                                ON (url_user.user_id = user.id)
                            WHERE
                                access_url_id = '$access_url_id'
                                AND (
                                    username LIKE '$needle%'
                                    OR lastname LIKE '$needle%'
                                    OR firstname LIKE '$needle%'
                                )
                                AND user.status <> 6
                                AND user.status <> ".DRH."
                            $order_clause LIMIT 11
                        ";
                        break;
                    case 'multiple':
                        $sql = "
                            SELECT user.id, username, lastname, firstname, official_code
                            FROM $tbl_user user
                            INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=user.id)
                            WHERE
                                access_url_id = $access_url_id
                                AND lastname LIKE '$needle%'
                                AND user.status <> ".DRH."
                                AND user.status <> 6 $cond_user_id
                            $order_clause
                        ";
                        break;
                    case 'any_session':
                        $sql = "
                            SELECT DISTINCT user.id, username, lastname, firstname, official_code
                            FROM $tbl_user user
                            LEFT OUTER JOIN $tbl_session_rel_user s
                                ON (s.user_id = user.id)
                            INNER JOIN $tbl_user_rel_access_url url_user
                                ON (url_user.user_id = user.id)
                            WHERE
                                access_url_id = $access_url_id
                                AND s.user_id IS null
                                AND user.status <> ".DRH."
                                AND user.status <> 6 $cond_user_id
                            $order_clause
                        ";
                        break;
                }
            }
        }

        $rs = Database::query($sql);
        $i = 0;
        if ($type == 'single') {
            while ($user = Database::fetch_array($rs)) {
                $i++;
                if ($i <= 10) {
                    $person_name =
                        $user['lastname'].' '.$user['firstname'].' ('.$user['username'].') '.$user['official_code'];
                    if ($showOfficialCode) {
                        $officialCode = !empty($user['official_code']) ? $user['official_code'].' - ' : '? - ';
                        $person_name =
                            $officialCode.$user['lastname'].' '.$user['firstname'].' ('.$user['username'].')';
                    }

                    $return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_session(\''.$user['id']
                        .'\',\''.$person_name.' '.'\')">'.$person_name.' </a><br />';
                } else {
                    $return .= '...<br />';
                }
            }

            $xajax_response->addAssign('ajax_list_users_single', 'innerHTML', api_utf8_encode($return));
        } else {
            global $nosessionUsersList;
            $return .= '<select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:360px;">';
            while ($user = Database::fetch_array($rs)) {
                $person_name =
                    $user['lastname'].' '.$user['firstname'].' ('.$user['username'].') '.$user['official_code'];
                if ($showOfficialCode) {
                    $officialCode = !empty($user['official_code']) ? $user['official_code'].' - ' : '? - ';
                    $person_name = $officialCode.$user['lastname'].' '.$user['firstname'].' ('.$user['username'].')';
                }
                $return .= '<option value="'.$user['id'].'">'.$person_name.' </option>';
            }
            $return .= '</select>';
            $xajax_response->addAssign('ajax_list_users_multiple', 'innerHTML', api_utf8_encode($return));
        }
    }

    return $xajax_response;
}

$xajax->processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script>
function add_user_to_session (code, content) {
	document.getElementById("user_to_add").value = "";
	document.getElementById("ajax_list_users_single").innerHTML = "";
	destination = document.getElementById("destination_users");
	for (i=0;i<destination.length;i++) {
		if(destination.options[i].text == content) {
				return false;
		}
	}
	destination.options[destination.length] = new Option(content,code);
	destination.selectedIndex = -1;
	sortOptions(destination.options);
}

function remove_item(origin) {
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}

function validate_filter() {
    document.formulaire.add_type.value = \''.$add_type.'\';
    document.formulaire.form_sent.value=0;
    document.formulaire.submit();
}

function checked_in_no_session(checked) {
    $("#first_letter_user")
    .find("option")
    .attr("selected", false);
    xajax_search_users(checked, "any_session");
}

function change_select(val) {
    $("#user_with_any_session_id").attr("checked", false);
    xajax_search_users(val,"multiple");
}
</script>';

$form_sent = 0;
$errorMsg = $firstLetterUser = $firstLetterSession = '';
$UserList = $SessionList = [];
$sessions = [];
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $firstLetterUser = isset($_POST['firstLetterUser']) ? $_POST['firstLetterUser'] : '';
    $firstLetterSession = isset($_POST['firstLetterSession']) ? $_POST['firstLetterSession'] : '';
    $UserList = isset($_POST['sessionUsersList']) ? $_POST['sessionUsersList'] : [];

    if (!is_array($UserList)) {
        $UserList = [];
    }

    if ($form_sent == 1) {
        $notEmptyList = api_get_configuration_value('session_multiple_subscription_students_list_avoid_emptying');

        // Added a parameter to send emails when registering a user
        SessionManager::subscribeUsersToSession(
            $id_session,
            $UserList,
            null,
            !$notEmptyList
        );
        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: resume_session.php?id_session='.$id_session);
        exit;
    }
}

$session_info = SessionManager::fetch($id_session);
Display::display_header($tool_name);

$nosessionUsersList = $sessionUsersList = [];
$where_filter = null;
$ajax_search = $add_type == 'unique' ? true : false;

//$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
// On this screen, it doesn't make sense to order users by firstname. Always use lastname first
// api_get_person_name() calls have been removed because ordering users in a simple list must always
// be done by lastname, even if we like to show user names with the firstname first.
// By simple logic, lastnames are the smallest common denominator
$order_clause = ' ORDER BY lastname, firstname, username';

$showOfficialCode = false;
$orderListByOfficialCode = api_get_setting('order_user_list_by_official_code');
if ($orderListByOfficialCode === 'true') {
    $showOfficialCode = true;
    $order_clause = ' ORDER BY official_code, lastname, firstname, username';
}

if ($ajax_search) {
    $sql = "
        SELECT u.id, u.lastname, u.firstname, u.username, session_id, u.official_code
        FROM $tbl_user u
        INNER JOIN $tbl_session_rel_user su
            ON su.user_id = u.id
            AND su.relation_type <> ".SESSION_RELATION_TYPE_RRHH."
            AND su.session_id = ".intval($id_session)."
        WHERE u.status<>".DRH."
            AND u.status <> 6
        $order_clause
    ";

    if (api_is_multiple_url_enabled()) {
        $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $sql = "
                SELECT u.id, u.lastname, u.firstname, u.username, session_id, u.official_code
                FROM $tbl_user u
                INNER JOIN $tbl_session_rel_user su
                    ON su.user_id = u.id
                    AND su.relation_type <> ".SESSION_RELATION_TYPE_RRHH."
                    AND su.session_id = ".intval($id_session)."
                INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id = u.id)
                WHERE access_url_id = $access_url_id
                    AND u.status <> ".DRH."
                    AND u.status <> 6
                $order_clause
            ";
        }
    }
    $result = Database::query($sql);
    $users = Database::store_result($result);
    foreach ($users as $user) {
        $sessionUsersList[$user['id']] = $user;
    }

    $sessionUserInfo = SessionManager::getTotalUserCoursesInSession($id_session);

    // Filter the user list in all courses in the session
    foreach ($sessionUserInfo as $sessionUser) {
        // filter students in session
        if ($sessionUser['status_in_session'] != 0) {
            continue;
        }

        if (!array_key_exists($sessionUser['id'], $sessionUsersList)) {
            continue;
        }
    }

    unset($users); //clean to free memory
} else {
    // Filter by Extra Fields
    $extra_field_result = [];
    $use_extra_fields = false;
    if (is_array($extra_field_list)) {
        if (is_array($new_field_list) && count($new_field_list) > 0) {
            $result_list = [];
            foreach ($new_field_list as $new_field) {
                $varname = 'field_'.$new_field['variable'];
                $fieldtype = $new_field['type'];
                if (UserManager::is_extra_field_available($new_field['variable'])) {
                    if (isset($_POST[$varname]) && $_POST[$varname] != '0') {
                        $use_extra_fields = true;
                        if ($fieldtype == ExtraField::FIELD_TYPE_TAG) {
                            $extra_field_result[] = UserManager::get_extra_user_data_by_tags(
                                intval($_POST['field_id']),
                                $_POST[$varname]
                            );
                        } else {
                            $extra_field_result[] = UserManager::get_extra_user_data_by_value(
                                $new_field['variable'],
                                $_POST[$varname]
                            );
                        }
                    }
                }
            }
        }
    }

    if ($use_extra_fields) {
        $final_result = [];
        if (count($extra_field_result) > 1) {
            for ($i = 0; $i < count($extra_field_result) - 1; $i++) {
                if (is_array($extra_field_result[$i + 1])) {
                    $final_result = array_intersect(
                        $extra_field_result[$i],
                        $extra_field_result[$i + 1]
                    );
                }
            }
        } else {
            $final_result = $extra_field_result[0];
        }

        if (api_is_multiple_url_enabled()) {
            if (is_array($final_result) && count($final_result) > 0) {
                $where_filter = " AND u.id IN  ('".implode("','", $final_result)."') ";
            } else {
                //no results
                $where_filter = " AND u.id  = -1";
            }
        } else {
            if (is_array($final_result) && count($final_result) > 0) {
                $where_filter = " WHERE u.id IN  ('".implode("','", $final_result)."') ";
            } else {
                //no results
                $where_filter = " WHERE u.id  = -1";
            }
        }
    }
    if (api_is_session_admin() && api_get_setting('prevent_session_admins_to_manage_all_users') === 'true') {
        $order_clause = " AND u.creator_id = ".api_get_user_id().$order_clause;
    }
    if ($use_extra_fields) {
        $sql = "
            SELECT  u.id, lastname, firstname, username, session_id, official_code
            FROM $tbl_user u
            LEFT JOIN $tbl_session_rel_user su
                ON su.user_id = u.id
                AND su.session_id = $id_session
                AND su.relation_type <> ".SESSION_RELATION_TYPE_RRHH."
            $where_filter
                AND u.status <> ".DRH."
                AND u.status <> 6
            $order_clause
           ";
    } else {
        $sql = "
            SELECT  u.id, lastname, firstname, username, session_id, official_code
            FROM $tbl_user u
            LEFT JOIN $tbl_session_rel_user su
                ON su.user_id = u.id
                AND su.session_id = $id_session
                AND su.relation_type <> ".SESSION_RELATION_TYPE_RRHH."
            WHERE u.status <> ".DRH." AND u.status <> 6
            $order_clause
        ";
    }
    if (api_is_multiple_url_enabled()) {
        $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $sql = "
                SELECT  u.id, lastname, firstname, username, session_id, official_code
                FROM $tbl_user u
                LEFT JOIN $tbl_session_rel_user su
                    ON su.user_id = u.id
                    AND su.session_id = $id_session
                    AND su.relation_type <> ".SESSION_RELATION_TYPE_RRHH."
                INNER JOIN $tbl_user_rel_access_url url_user
                ON (url_user.user_id = u.id)
                WHERE access_url_id = $access_url_id $where_filter
                    AND u.status <> ".DRH."
                    AND u.status<>6
                $order_clause
            ";
        }
    }

    $result = Database::query($sql);
    $users = Database::store_result($result, 'ASSOC');
    foreach ($users as $uid => $user) {
        if ($user['session_id'] != $id_session) {
            $nosessionUsersList[$user['id']] = [
                'fn' => $user['firstname'],
                'ln' => $user['lastname'],
                'un' => $user['username'],
                'official_code' => $user['official_code'],
            ];
            unset($users[$uid]);
        }
    }
    unset($users); //clean to free memory

    // filling the correct users in list
    $sql = "
        SELECT  u.id, lastname, firstname, username, session_id, official_code
        FROM $tbl_user u
        LEFT JOIN $tbl_session_rel_user
        ON $tbl_session_rel_user.user_id = u.id
            AND $tbl_session_rel_user.session_id = $id_session
            AND $tbl_session_rel_user.relation_type <> ".SESSION_RELATION_TYPE_RRHH."
        WHERE u.status <> ".DRH." AND u.status <> 6 $order_clause
    ";

    if (api_is_multiple_url_enabled()) {
        $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $sql = "
                SELECT  u.id, lastname, firstname, username, session_id, official_code
                FROM $tbl_user u
                LEFT JOIN $tbl_session_rel_user
                    ON $tbl_session_rel_user.user_id = u.id
                    AND $tbl_session_rel_user.session_id = $id_session
                    AND $tbl_session_rel_user.relation_type <> ".SESSION_RELATION_TYPE_RRHH."
                INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id = u.id)
                WHERE access_url_id = $access_url_id
                    AND u.status <> ".DRH."
                    AND u.status <> 6
                $order_clause
            ";
        }
    }

    $result = Database::query($sql);
    $users = Database::store_result($result, 'ASSOC');
    foreach ($users as $uid => $user) {
        if ($user['session_id'] == $id_session) {
            $sessionUsersList[$user['id']] = $user;
            if (array_key_exists($user['id'], $nosessionUsersList)) {
                unset($nosessionUsersList[$user['id']]);
            }
        }
        unset($users[$uid]);
    }
    unset($users); //clean to free memory
}

if ($add_type == 'multiple') {
    $link_add_type_unique =
        '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.$addProcess.'&add_type=unique">'.
        Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'</a>';
    $link_add_type_multiple = Display::url(Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple'), '');
} else {
    $link_add_type_unique = Display::url(Display::return_icon('single.gif').get_lang('SessionAddTypeUnique'), '');
    $link_add_type_multiple =
        '<a href="'.api_get_self().'?id_session='.$id_session.'&amp;add='.$addProcess.'&amp;add_type=multiple">'
        .Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').'</a>';
}
$link_add_group = Display::url(
    Display::return_icon('multiple.gif', get_lang('RegistrationByUsersGroups')).get_lang('RegistrationByUsersGroups'),
    api_get_path(WEB_CODE_PATH).'admin/usergroups.php'
);

$newLinks = Display::url(
    Display::return_icon('teacher.png', get_lang('EnrollTrainersFromExistingSessions'), null, ICON_SIZE_TINY).
        get_lang('EnrollTrainersFromExistingSessions'),
    api_get_path(WEB_CODE_PATH).'session/add_teachers_to_session.php?id='.$id_session
);
$newLinks .= Display::url(
    Display::return_icon('user.png', get_lang('EnrollTrainersFromExistingSessions'), null, ICON_SIZE_TINY).
        get_lang('EnrollStudentsFromExistingSessions'),
    api_get_path(WEB_CODE_PATH).'session/add_students_to_session.php?id='.$id_session
);
?>
    <div class="actions">
        <?php
        echo $link_add_type_unique;
        echo $link_add_type_multiple;
        echo $link_add_group;
        echo $newLinks;
        ?>
    </div>
    <form name="formulaire" method="post"
          action="<?php echo api_get_self(); ?>?page=<?php echo $page; ?>&id_session=<?php echo $id_session; ?><?php if (!empty($addProcess)) {
            echo '&add=true';
        } ?>" <?php if ($ajax_search) {
            echo ' onsubmit="valide();"';
        } ?>>
        <?php echo '<legend>'.$tool_name.' ('.$session_info['name'].') </legend>'; ?>
        <?php
        if ($add_type == 'multiple') {
            if (is_array($extra_field_list)) {
                if (is_array($new_field_list) && count($new_field_list) > 0) {
                    echo '<h3>'.get_lang('FilterUsers').'</h3>';
                    foreach ($new_field_list as $new_field) {
                        echo $new_field['name'];
                        $varname = 'field_'.$new_field['variable'];
                        $fieldtype = $new_field['type'];
                        echo '&nbsp;<select name="'.$varname.'">';
                        echo '<option value="0">--'.get_lang('Select').'--</option>';
                        foreach ($new_field['data'] as $option) {
                            $checked = '';
                            if ($fieldtype == ExtraField::FIELD_TYPE_TAG) {
                                if (isset($_POST[$varname])) {
                                    if ($_POST[$varname] == $option['tag']) {
                                        $checked = 'selected="true"';
                                    }
                                }
                                echo '<option value="'.$option['tag'].'" '.$checked.'>'.$option['tag'].'</option>';
                            } else {
                                if (isset($_POST[$varname])) {
                                    if ($_POST[$varname] == $option[1]) {
                                        $checked = 'selected="true"';
                                    }
                                }
                                echo '<option value="'.$option[1].'" '.$checked.'>'.$option[2].'</option>';
                            }
                        }
                        echo '</select>';
                        $extraHidden =
                            $fieldtype == ExtraField::FIELD_TYPE_TAG ? '<input type="hidden" name="field_id" value="'
                                .$option['field_id'].'" />' : '';
                        echo $extraHidden;
                        echo '&nbsp;&nbsp;';
                    }
                    echo '<input type="button" value="'.get_lang('Filter').'" onclick="validate_filter()" />';
                    echo '<br /><br />';
                }
            }
        }
        ?>
        <input type="hidden" name="form_sent" value="1"/>
        <input type="hidden" name="add_type"/>

        <?php
        if (!empty($errorMsg)) {
            echo Display::return_message($errorMsg); //main API
        }
        ?>
        <div id="multiple-add-session" class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label><?php echo get_lang('UserListInPlatform'); ?> </label>
                    <?php
                    if (!($add_type == 'multiple')) {
                        ?>
                        <input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,'single')"
                               class="form-control"/>
                        <div id="ajax_list_users_single" class="select-list-ajax"></div>
                        <?php
                    } else {
                        ?>
                        <div id="ajax_list_users_multiple">
                            <select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15"
                                    class="form-control">
                                <?php
                                foreach ($nosessionUsersList as $uid => $enreg) {
                                    ?>
                                    <option value="<?php echo $uid; ?>" <?php if (in_array($uid, $UserList)) {
                                        echo 'selected="selected"';
                                    } ?>>
                                        <?php
                                        $personName = $enreg['ln'].' '.$enreg['fn'].' ('.$enreg['un'].') '
                                            .$enreg['official_code'];
                                    if ($showOfficialCode) {
                                        $officialCode =
                                                !empty($enreg['official_code']) ? $enreg['official_code'].' - '
                                                    : '? - ';
                                        $personName =
                                                $officialCode.$enreg['ln'].' '.$enreg['fn'].' ('.$enreg['un'].')';
                                    }
                                    echo $personName; ?>
                                    </option>
                                    <?php
                                } ?>
                            </select>
                        </div>
                        <input type="checkbox" onchange="checked_in_no_session(this.checked);"
                               name="user_with_any_session" id="user_with_any_session_id">
                        <label
                            for="user_with_any_session_id"><?php echo get_lang('UsersRegisteredInNoSession'); ?></label>
                        <?php
                    }
                    unset($nosessionUsersList);
                    ?>
                </div>
            </div>

            <div class="col-md-4">
                <?php if ($add_type == 'multiple') {
                        ?>
                    <?php echo get_lang('FirstLetterUser'); ?> :
                    <select id="first_letter_user" name="firstLetterUser" onchange="change_select(this.value);">
                        <option value="%">--</option>
                        <?php
                        echo Display::get_alphabet_options(); ?>
                    </select>
                    <br/>
                    <br/>
                <?php
                    } ?>
                <div class="control-course">
                    <?php
                    if ($ajax_search) {
                        ?>
                        <div class="separate-action">
                            <button name="remove_user" class="btn btn-primary" type="button"
                                    onclick="remove_item(document.getElementById('destination_users'))">
                                <em class="fa fa-chevron-left"></em>
                            </button>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="separate-action">
                            <button name="add_user" class="btn btn-primary" type="button"
                                    onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))"
                                    onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))">
                                <em class="fa fa-chevron-right"></em>
                            </button>
                        </div>
                        <div class="separate-action">
                            <button name="remove_user" class="btn btn-primary" type="button"
                                    onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))"
                                    onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))">
                                <em class="fa fa-chevron-left"></em>
                            </button>
                        </div>

                        <?php
                    }
                    if (!empty($addProcess)) {
                        echo '<button name="next" class="btn btn-success" type="button" value="" onclick="valide()" >'
                            .get_lang('FinishSessionCreation').'</button>';
                    } else {
                        echo '<button name="next" class="btn btn-success" type="button" value="" onclick="valide()" >'
                            .get_lang('SubscribeUsersToSession').'</button>';
                    }
                    ?>
                </div>
            </div>

            <div class="col-md-4">
                <label><?php echo get_lang('UserListInSession'); ?> :</label>
                <select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15"
                        class="form-control">
                    <?php
                    foreach ($sessionUsersList as $enreg) {
                        ?>
                        <option value="<?php echo $enreg['id']; ?>">
                            <?php
                            $personName = $enreg['lastname'].' '.$enreg['firstname'].' ('.$enreg['username'].') '
                                .$enreg['official_code'];
                        if ($showOfficialCode) {
                            $officialCode =
                                    !empty($enreg['official_code']) ? $enreg['official_code'].' - ' : '? - ';
                            $personName =
                                    $officialCode.$enreg['lastname'].' '.$enreg['firstname'].' ('.$enreg['username']
                                    .')';
                        }
                        echo $personName; ?>
                        </option>
                        <?php
                    }
                    unset($sessionUsersList);
                    ?>
                </select>
            </div>
        </div>
    </form>
    <script>
        function moveItem(origin, destination) {
            for (var i = 0; i < origin.options.length; i++) {
                if (origin.options[i].selected) {
                    destination.options[destination.length] = new Option(origin.options[i].text, origin.options[i].value);
                    origin.options[i] = null;
                    i = i - 1;
                }
            }
            destination.selectedIndex = -1;
            sortOptions(destination.options);
        }

        function sortOptions(options) {
            newOptions = new Array();
            for (i = 0; i < options.length; i++)
                newOptions[i] = options[i];

            newOptions = newOptions.sort(mysort);
            options.length = 0;
            for (i = 0; i < newOptions.length; i++)
                options[i] = newOptions[i];
        }

        function mysort(a, b) {
            if (a.text.toLowerCase() > b.text.toLowerCase()) {
                return 1;
            }
            if (a.text.toLowerCase() < b.text.toLowerCase()) {
                return -1;
            }
            return 0;
        }

        function valide() {
            var options = document.getElementById('destination_users').options;
            for (i = 0; i < options.length; i++)
                options[i].selected = true;
            document.forms.formulaire.submit();
        }

        function loadUsersInSelect(select) {
            var xhr_object = null;
            if (window.XMLHttpRequest) // Firefox
                xhr_object = new XMLHttpRequest();
            else if (window.ActiveXObject) // Internet Explorer
                xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
            else  // XMLHttpRequest non supporté par le navigateur
                alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

            xhr_object.open("POST", "loadUsersInSelect.ajax.php");
            xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            nosessionUsers = makepost(document.getElementById('origin_users'));
            sessionUsers = makepost(document.getElementById('destination_users'));
            nosessionClasses = makepost(document.getElementById('origin_classes'));
            sessionClasses = makepost(document.getElementById('destination_classes'));
            xhr_object.send("nosessionusers=" + nosessionUsers + "&sessionusers=" + sessionUsers + "&nosessionclasses=" + nosessionClasses + "&sessionclasses=" + sessionClasses);

            xhr_object.onreadystatechange = function () {
                if (xhr_object.readyState == 4) {
                    document.getElementById('content_source').innerHTML = result = xhr_object.responseText;
                }
            }
        }

        function makepost(select) {
            var options = select.options;
            var ret = "";
            for (i = 0; i < options.length; i++)
                ret = ret + options[i].value + '::' + options[i].text + ";;";
            return ret;
        }
    </script>
<?php

Display::display_footer();
