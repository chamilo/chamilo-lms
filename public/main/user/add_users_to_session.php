<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$xajax = new xajax();
$xajax->registerFunction('search_users');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
$id_session = (int) $_GET['id_session'];

SessionManager::protect_teacher_session_edit($id_session);

$session = api_get_session_entity($id_session);

// setting breadcrumbs
if (api_is_platform_admin()) {
    $interbreadcrumb[] = [
        'url' => 'index.php',
        'name' => get_lang('Administration'),
    ];
    $interbreadcrumb[] = [
        'url' => 'session_list.php',
        'name' => get_lang('Session list'),
    ];
    $interbreadcrumb[] = [
        'url' => "resume_session.php?id_session=".$id_session,
        "name" => get_lang('Session overview'),
    ];
}
$allowTutors = api_get_setting('allow_tutors_to_assign_students_to_session');
$extra_field_list = [];
if ('true' === $allowTutors) {
    // Database Table Definitions
    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
    $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
    $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
    $tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);

    // setting the name of the tool
    $tool_name = get_lang('Subscribe users to this session');
    $add_type = 'unique';

    if (isset($_REQUEST['add_type']) && '' != $_REQUEST['add_type']) {
        $add_type = Security::remove_XSS($_REQUEST['add_type']);
    }

    $page = isset($_GET['page']) ? Security::remove_XSS($_GET['page']) : null;

    // Checking for extra field with filter on
    $extra_field_list = UserManager::get_extra_fields();
    $new_field_list = [];
    if (is_array($extra_field_list)) {
        foreach ($extra_field_list as $extra_field) {
            //if is enabled to filter and is a "<select>" field type
            if (1 == $extra_field[8] && 4 == $extra_field[2]) {
                $new_field_list[] = [
                    'name' => $extra_field[3],
                    'variable' => $extra_field[1],
                    'data' => $extra_field[9],
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
            //normal behaviour
            if ('any_session' == $type && 'false' == $needle) {
                $type = 'multiple';
                $needle = '';
            }

            // xajax send utf8 datas... datas in db can be non-utf8 datas
            $charset = api_get_system_encoding();
            $needle = Database::escape_string($needle);
            $needle = api_convert_encoding($needle, $charset, 'utf-8');

            $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
            $cond_user_id = '';

            //Only for single & multiple
            if (in_array($type, ['single', 'multiple'])) {
                if (!empty($id_session)) {
                    $id_session = intval($id_session);
                    // check id_user from session_rel_user table
                    $sql = 'SELECT user_id FROM '.$tbl_session_rel_user.'
                            WHERE session_id ="'.$id_session.'" AND relation_type<>'.Session::DRH.' ';
                    $res = Database::query($sql);
                    $user_ids = [];
                    if (Database::num_rows($res) > 0) {
                        while ($row = Database::fetch_row($res)) {
                            $user_ids[] = (int) $row[0];
                        }
                    }
                    if (count($user_ids) > 0) {
                        $cond_user_id = ' AND user.user_id NOT IN('.implode(",", $user_ids).')';
                    }
                }
            }

            switch ($type) {
                case 'single':
                    // search users where username or firstname or lastname begins likes $needle
                    $sql = 'SELECT user.user_id, username, lastname, firstname
                            FROM '.$tbl_user.' user
                            WHERE (username LIKE "'.$needle.'%" OR firstname LIKE "'.$needle.'%"
                                OR lastname LIKE "'.$needle.'%") AND user.status<>6 AND user.status<>'.DRH.''.
                                $order_clause.
                                ' LIMIT 11';
                    break;
                case 'multiple':
                    $sql = 'SELECT user.user_id, username, lastname, firstname
                            FROM '.$tbl_user.' user
                            WHERE '.(api_sort_by_first_name() ? 'firstname' : 'lastname').'
                            LIKE "'.$needle.'%" AND
                            user.status<>'.DRH.' AND
                            user.status<>6 '.$cond_user_id.
                            $order_clause;
                    break;
                case 'any_session':
                    $sql = 'SELECT DISTINCT user.user_id, username, lastname, firstname
                            FROM '.$tbl_user.' user
                            LEFT OUTER JOIN '.$tbl_session_rel_user.' s ON (s.user_id = user.user_id)
                            WHERE
                                s.user_id IS NULL AND
                                user.status <>'.DRH.' AND
                                user.status <> 6 '.$cond_user_id.
                            $order_clause;
                    break;
            }

            if (api_is_multiple_url_enabled()) {
                $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
                $access_url_id = api_get_current_access_url_id();
                if (-1 != $access_url_id) {
                    switch ($type) {
                        case 'single':
                            $sql = 'SELECT user.user_id, username, lastname, firstname
                                    FROM '.$tbl_user.' user
                                    INNER JOIN '.$tbl_user_rel_access_url.' url_user
                                    ON (url_user.user_id=user.user_id)
                                    WHERE
                                        access_url_id = '.$access_url_id.' AND
                                        (username LIKE "'.$needle.'%" OR firstname LIKE "'.$needle.'%" OR lastname LIKE "'.$needle.'%") AND
                                        user.status<>6 AND
                                        user.status<>'.DRH.' '.
                                    $order_clause.
                                    ' LIMIT 11';
                            break;
                        case 'multiple':
                            $sql = 'SELECT user.user_id, username, lastname, firstname
                                    FROM '.$tbl_user.' user
                                    INNER JOIN '.$tbl_user_rel_access_url.' url_user
                                    ON (url_user.user_id=user.user_id)
                                    WHERE access_url_id = '.$access_url_id.' AND
                                    '.(api_sort_by_first_name() ? 'firstname' : 'lastname').' LIKE "'.$needle.'%" AND user.status<>'.DRH.' AND user.status<>6 '.$cond_user_id.
                                    $order_clause;
                            break;
                        case 'any_session':
                            $sql = 'SELECT DISTINCT user.user_id, username, lastname, firstname
                                    FROM '.$tbl_user.' user
                                    LEFT OUTER JOIN '.$tbl_session_rel_user.' s
                                    ON (s.user_id = user.user_id)
                                    INNER JOIN '.$tbl_user_rel_access_url.' url_user
                                    ON (url_user.user_id=user.user_id)
                                    WHERE
                                        access_url_id = '.$access_url_id.' AND
                                        s.user_id IS null AND
                                        user.status<>'.DRH.' AND
                                        user.status<>6 '.$cond_user_id.
                            $order_clause;
                            break;
                    }
                }
            }

            $rs = Database::query($sql);
            $i = 0;
            if ('single' === $type) {
                while ($user = Database::fetch_array($rs)) {
                    $i++;
                    if ($i <= 10) {
                        $person_name = api_get_person_name($user['firstname'], $user['lastname']);
                        $return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_session(\''.$user['user_id'].'\',\''.$person_name.' ('.$user['username'].')'.'\')">'.$person_name.' ('.$user['username'].')</a><br />';
                    } else {
                        $return .= '...<br />';
                    }
                }

                $xajax_response->addAssign('ajax_list_users_single', 'innerHTML', api_utf8_encode($return));
            } else {
                $return .= '<select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:360px;">';
                while ($user = Database::fetch_array($rs)) {
                    $person_name = api_get_person_name($user['firstname'], $user['lastname']);
                    $return .= '<option value="'.$user['user_id'].'">'.$person_name.' ('.$user['username'].')</option>';
                }
                $return .= '</select>';
                $xajax_response->addAssign('ajax_list_users_multiple', 'innerHTML', api_utf8_encode($return));
            }
        }

        return $xajax_response;
    }

    $xajax->processRequests();
    $htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
    $htmlHeadXtra[] = '<script>
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
    $firstLetterUser = $firstLetterSession = '';
    $UserList = $SessionList = [];
    $sessions = [];
    if (isset($_POST['form_sent']) && $_POST['form_sent']) {
        $form_sent = $_POST['form_sent'];
        $firstLetterUser = $_POST['firstLetterUser'];
        $firstLetterSession = $_POST['firstLetterSession'];
        $UserList = $_POST['sessionUsersList'];

        if (!is_array($UserList)) {
            $UserList = [];
        }

        if (1 == $form_sent) {
            //added a parameter to send emails when registering a user
            SessionManager::subscribeUsersToSession($id_session, $UserList, null, true);
            header('Location: resume_session.php?id_session='.$id_session);
            exit;
        }
    }

    $session_info = SessionManager::fetch($id_session);
    Display::display_header($tool_name);
    $nosessionUsersList = $sessionUsersList = [];
    $ajax_search = 'unique' === $add_type ? true : false;

    $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
    if ($ajax_search) {
        $sql = "SELECT u.id as user_id, lastname, firstname, username, session_id
                FROM $tbl_user u
                INNER JOIN $tbl_session_rel_user
                ON
                    $tbl_session_rel_user.user_id = u.id AND
                    $tbl_session_rel_user.relation_type<>".Session::DRH." AND
                    $tbl_session_rel_user.session_id = ".intval($id_session)."
                WHERE u.status <> ".DRH." AND u.status<>6 $order_clause";

        if (api_is_multiple_url_enabled()) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $sql = "SELECT u.id as user_id, lastname, firstname, username, session_id
                        FROM $tbl_user u
                        INNER JOIN $tbl_session_rel_user
                        ON
                            $tbl_session_rel_user.user_id = u.id AND
                            $tbl_session_rel_user.relation_type<>".Session::DRH." AND
                            $tbl_session_rel_user.session_id = ".intval($id_session)."
                        INNER JOIN $tbl_user_rel_access_url url_user
                        ON (url_user.user_id=u.user_id)
                        WHERE access_url_id = $access_url_id AND u.status<>".DRH." AND u.status<>6
                    $order_clause";
            }
        }
        $result = Database::query($sql);
        $users = Database::store_result($result);
        foreach ($users as $user) {
            $sessionUsersList[$user['user_id']] = $user;
        }
        unset($users); //clean to free memory
    } else {
        //Filter by Extra Fields
        $use_extra_fields = false;
        if (is_array($extra_field_list)) {
            if (is_array($new_field_list) && count($new_field_list) > 0) {
                $result_list = [];
                foreach ($new_field_list as $new_field) {
                    $varname = 'field_'.$new_field['variable'];
                    if (UserManager::is_extra_field_available($new_field['variable'])) {
                        if (isset($_POST[$varname]) && '0' != $_POST[$varname]) {
                            $use_extra_fields = true;
                            $extra_field_result[] = UserManager::get_extra_user_data_by_value(
                                $new_field['variable'],
                                $_POST[$varname]
                            );
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

            $where_filter = '';
            if (api_is_multiple_url_enabled()) {
                if (is_array($final_result) && count($final_result) > 0) {
                    $where_filter = " AND u.id IN  ('".implode("','", $final_result)."') ";
                } else {
                    //no results
                    $where_filter = " AND u.id = -1";
                }
            } else {
                if (is_array($final_result) && count($final_result) > 0) {
                    $where_filter = " WHERE u.id IN  ('".implode("','", $final_result)."') ";
                } else {
                    //no results
                    $where_filter = " WHERE u.id = -1";
                }
            }
        }

        if ($use_extra_fields) {
            $sql = "SELECT u.id as user_id, lastname, firstname, username, session_id
                    FROM $tbl_user u
                    LEFT JOIN $tbl_session_rel_user
                    ON $tbl_session_rel_user.user_id = u.id AND
                    $tbl_session_rel_user.session_id = '$id_session' AND
                    $tbl_session_rel_user.relation_type<>".Session::DRH."
                    $where_filter AND u.status<>".DRH." AND u.status<>6
                    $order_clause";
        } else {
            $sql = "SELECT u.id as user_id, lastname, firstname, username, session_id
                    FROM $tbl_user u
                    LEFT JOIN $tbl_session_rel_user
                    ON $tbl_session_rel_user.user_id = u.id AND
                    $tbl_session_rel_user.session_id = '$id_session' AND
                    $tbl_session_rel_user.relation_type<>".Session::DRH."
                    WHERE u.status <> ".DRH." AND u.status<>6
                    $order_clause";
        }

        if (api_is_multiple_url_enabled()) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $sql = "SELECT u.id as user_id, lastname, firstname, username, session_id
                        FROM $tbl_user u
                        LEFT JOIN $tbl_session_rel_user
                        ON
                            $tbl_session_rel_user.user_id = u.id AND
                            $tbl_session_rel_user.session_id = '$id_session' AND
                            $tbl_session_rel_user.relation_type<>".Session::DRH."
                        INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=u.user_id)
                        WHERE access_url_id = $access_url_id  $where_filter AND u.status<>".DRH." AND u.status<>6
                        $order_clause";
            }
        }

        $result = Database::query($sql);
        $users = Database::store_result($result, 'ASSOC');

        foreach ($users as $uid => $user) {
            if ($user['session_id'] != $id_session) {
                $nosessionUsersList[$user['user_id']] = [
                    'fn' => $user['firstname'],
                    'ln' => $user['lastname'],
                    'un' => $user['username'],
                ];
                unset($users[$uid]);
            }
        }
        unset($users); //clean to free memory

        //filling the correct users in list
        $sql = "SELECT  user_id, lastname, firstname, username, session_id
                FROM $tbl_user u
                LEFT JOIN $tbl_session_rel_user
                ON
                    $tbl_session_rel_user.user_id = u.id AND
                    $tbl_session_rel_user.session_id = '$id_session' AND
                    $tbl_session_rel_user.relation_type<>".Session::DRH."
                WHERE u.status <> ".DRH." AND u.status<>6 $order_clause";

        if (api_is_multiple_url_enabled()) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $sql = "SELECT u.id as user_id, lastname, firstname, username, session_id
                        FROM $tbl_user u
                        LEFT JOIN $tbl_session_rel_user
                        ON
                            $tbl_session_rel_user.user_id = u.id AND
                            $tbl_session_rel_user.session_id = '$id_session' AND
                            $tbl_session_rel_user.relation_type<>".Session::DRH."
                        INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=u.user_id)
                        WHERE access_url_id = $access_url_id AND u.status<>".DRH." AND u.status<>6
                    $order_clause";
            }
        }
        $result = Database::query($sql);
        $users = Database::store_result($result, 'ASSOC');
        foreach ($users as $uid => $user) {
            if ($user['session_id'] == $id_session) {
                $sessionUsersList[$user['user_id']] = $user;
                if (array_key_exists($user['user_id'], $nosessionUsersList)) {
                    unset($nosessionUsersList[$user['user_id']]);
                }
            }
            unset($users[$uid]);
        }
        unset($users); //clean to free memory
    }

    if ('multiple' === $add_type) {
        $link_add_type_unique = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=unique">'.
            Display::return_icon('single.gif').get_lang('Single registration').'</a>';
        $link_add_type_multiple = Display::return_icon('multiple.gif').get_lang('Multiple registration');
    } else {
        $link_add_type_unique = Display::return_icon('single.gif').get_lang('Single registration');
        $link_add_type_multiple = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=multiple">'.
            Display::return_icon('multiple.gif').get_lang('Multiple registration').'</a>';
    }
    $link_add_group = '<a href="usergroups.php">'.
            Display::return_icon('multiple.gif', get_lang('Enrolment by classes')).get_lang('Enrolment by classes').'</a>'; ?>
    <div class="actions">
        <?php echo $link_add_type_unique; ?>&nbsp;|&nbsp;<?php echo $link_add_type_multiple; ?>&nbsp;|&nbsp;<?php echo $link_add_group; ?>
    </div>
    <form name="formulaire" method="post"
          action="<?php echo api_get_self(); ?>?page=<?php echo $page; ?>&id_session=<?php echo $id_session; ?><?php if (!empty($_GET['add'])) {
                echo '&add=true';
            } ?>" style="margin:0px;" <?php if ($ajax_search) {
                echo ' onsubmit="valide();"';
            } ?>>
    <?php echo '<legend>'.$tool_name.' ('.$session->getName().') </legend>'; ?>
    <?php
    if ('multiple' === $add_type) {
        if (is_array($extra_field_list)) {
            if (is_array($new_field_list) && count($new_field_list) > 0) {
                echo '<h3>'.get_lang('Filter users').'</h3>';
                foreach ($new_field_list as $new_field) {
                    echo $new_field['name'];
                    $varname = 'field_'.$new_field['variable'];
                    echo '&nbsp;<select name="'.$varname.'">';
                    echo '<option value="0">--'.get_lang('Select').'--</option>';
                    foreach ($new_field['data'] as $option) {
                        $checked = '';
                        if (isset($_POST[$varname])) {
                            if ($_POST[$varname] == $option[1]) {
                                $checked = 'selected="true"';
                            }
                        }
                        echo '<option value="'.$option[1].'" '.$checked.'>'.$option[1].'</option>';
                    }
                    echo '</select>';
                    echo '&nbsp;&nbsp;';
                }
                echo '<input type="button" value="'.get_lang('Filter').'" onclick="validate_filter()" />';
                echo '<br /><br />';
            }
        }
    } ?>
    <input type="hidden" name="form_sent" value="1" />
    <input type="hidden" name="add_type"  />
    <div class="row">
        <div class="span5">
            <div class="multiple_select_header">
                <b><?php echo get_lang('Portal users list'); ?> :</b>
            <?php if ('multiple' == $add_type) {
        ?>
                <?php echo get_lang('First letter (last name)'); ?> :
                    <select id="first_letter_user" name="firstLetterUser" onchange = "change_select(this.value);" >
                    <option value = "%">--</option>
                    <?php
                        echo Display :: get_alphabet_options(); ?>
                    </select>
            <?php
    } ?>
            </div>
                <div id="content_source">
                <?php
                if (!('multiple' == $add_type)) {
                    ?>
                  <input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,'single')" />
                  <div id="ajax_list_users_single"></div>
                  <?php
                } else {
                    ?>
                <div id="ajax_list_users_multiple">
                <select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" class="span5">
                  <?php
                  foreach ($nosessionUsersList as $uid => $enreg) {
                      ?>
                      <option value="<?php echo $uid; ?>" <?php if (in_array($uid, $UserList)) {
                          echo 'selected="selected"';
                      } ?>><?php echo api_get_person_name($enreg['fn'], $enreg['ln']).' ('.$enreg['un'].')'; ?></option>
                  <?php
                  } ?>
                </select>
                </div>
                    <input type="checkbox" onchange="checked_in_no_session(this.checked);" name="user_with_any_session" id="user_with_any_session_id">
                    <label for="user_with_any_session_id"><?php echo get_lang('Users not registered in any session'); ?></label>
                <?php
                }
    unset($nosessionUsersList); ?>
            </div>
        </div>

        <div class="span2">
            <div style="padding-top:54px;width:auto;text-align: center;">
            <?php
                if ($ajax_search) {
                    ?>
                  <button class="btn btn-default" type="button" onclick="remove_item(document.getElementById('destination_users'))" ><em class="fa fa-arrow-left"></em></button>
                <?php
                } else {
                    ?>
                    <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))">
                        <em class="fa fa-arrow-right"></em>
                    </button>
                    <br /><br />
                    <button class="btn btn-default" type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))">
                        <em class="fa fa-arrow-left"></em>
                    </button>
                  <?php
                } ?>
            </div>
            <br />
            <br />
            <?php
            if (isset($_GET['add'])) {
                echo '<button class="btn btn-primary" type="button" value="" onclick="valide()" >'.get_lang('Finish session creation').'</button>';
            } else {
                //@todo see that the call to "valide()" doesn't duplicate the onsubmit of the form (necessary to avoid delete on "enter" key pressed)
                echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('Subscribe users to this session').'</button>';
            } ?>
        </div>
        <div class="span5">
            <div class="multiple_select_header">
                <b><?php echo get_lang('List of users registered in this session'); ?> :</b>
            </div>
            <select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" class="span5">
            <?php
            foreach ($sessionUsersList as $enreg) {
                ?>
                <option value="<?php echo $enreg['user_id']; ?>"><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>
            <?php
            }
    unset($sessionUsersList); ?>
            </select>
        </div>
    </div>
    </form>
    <script>
    function moveItem(origin , destination) {
        for (var i = 0 ; i<origin.options.length ; i++) {
            if (origin.options[i].selected) {
                destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
                origin.options[i]=null;
                i = i-1;
            }
        }
        destination.selectedIndex = -1;
        sortOptions(destination.options);
    }

    function sortOptions(options)
    {
        newOptions = new Array();
        for (i = 0 ; i<options.length ; i++)
            newOptions[i] = options[i];

        newOptions = newOptions.sort(mysort);
        options.length = 0;
        for (i = 0 ; i < newOptions.length ; i++)
            options[i] = newOptions[i];
    }

    function mysort(a, b)
    {
        if (a.text.toLowerCase() > b.text.toLowerCase()) {
            return 1;
        }
        if (a.text.toLowerCase() < b.text.toLowerCase()) {
            return -1;
        }
        return 0;
    }

    function valide()
    {
        var options = document.getElementById('destination_users').options;
        for (i = 0 ; i<options.length ; i++)
            options[i].selected = true;
        document.forms.formulaire.submit();
    }

    function loadUsersInSelect(select)
    {
        var xhr_object = null;
        if(window.XMLHttpRequest) // Firefox
            xhr_object = new XMLHttpRequest();
        else if(window.ActiveXObject) // Internet Explorer
            xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
        else  // XMLHttpRequest non supporté par le navigateur
        alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

        //xhr_object.open("GET", "loadUsersInSelect.ajax.php?id_session=<?php echo $id_session; ?>&letter="+select.options[select.selectedIndex].text, false);
        xhr_object.open("POST", "loadUsersInSelect.ajax.php");
        xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        nosessionUsers = makepost(document.getElementById('origin_users'));
        sessionUsers = makepost(document.getElementById('destination_users'));
        nosessionClasses = makepost(document.getElementById('origin_classes'));
        sessionClasses = makepost(document.getElementById('destination_classes'));
        xhr_object.send("nosessionusers="+nosessionUsers+"&sessionusers="+sessionUsers+"&nosessionclasses="+nosessionClasses+"&sessionclasses="+sessionClasses);
        xhr_object.onreadystatechange = function() {
            if (xhr_object.readyState == 4) {
                document.getElementById('content_source').innerHTML = result = xhr_object.responseText;
                //alert(xhr_object.responseText);
            }
        }
    }

    function makepost(select)
    {
        var options = select.options;
        var ret = "";
        for (i = 0 ; i<options.length ; i++)
            ret = ret + options[i].value +'::'+options[i].text+";;";
        return ret;
    }
    </script>
<?php
} else {
        api_not_allowed();
    }
Display::display_footer();
