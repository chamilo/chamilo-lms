<?php
/* For licensing terms, see /license.txt */

/**
 *	This script allows platform admins to add users to courses.
 *	It displays a list of users and a list of courses;
 *	you can select multiple users and courses and then click on
 *	'Add to this(these) course(s)'.
 *
 *	@package chamilo.admin
 *
 * 	@todo use formvalidator for the form
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$form_sent = 0;
$first_letter_user = '';
$first_letter_course = '';
$courses = [];
$users = [];

$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);

/* Header */
$tool_name = get_lang('AddUsersToACourse');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$htmlHeadXtra[] = '<script>
function validate_filter() {
    document.formulaire.form_sent.value=0;
    document.formulaire.submit();
}
</script>';

// displaying the header
Display::display_header($tool_name);

$link_add_group = '<a href="usergroups.php">'.
    Display::return_icon('multiple.gif', get_lang('RegistrationByUsersGroups')).get_lang('RegistrationByUsersGroups').'</a>';
echo '<div class="actions">'.$link_add_group.'</div>';

$form = new FormValidator('subscribe_user2course');
$form->addElement('header', '', $tool_name);
$form->display();

//checking for extra field with filter on
$extra_field_list = UserManager::get_extra_fields();

$new_field_list = [];
if (is_array($extra_field_list)) {
    foreach ($extra_field_list as $extra_field) {
        // if is enabled to filter and is a "<select>" field type
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

/* React on POSTed request */
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];
    $users = isset($_POST['UserList']) && is_array($_POST['UserList']) ? $_POST['UserList'] : [];
    $courses = isset($_POST['CourseList']) && is_array($_POST['CourseList']) ? $_POST['CourseList'] : [];
    $first_letter_user = Database::escape_string($_POST['firstLetterUser']);
    $first_letter_course = Database::escape_string($_POST['firstLetterCourse']);

    foreach ($users as $key => $value) {
        $users[$key] = intval($value);
    }

    if ($form_sent == 1) {
        if (count($users) == 0 || count($courses) == 0) {
            echo Display::return_message(get_lang('AtLeastOneUserAndOneCourse'), 'error');
        } else {
            foreach ($courses as $course_code) {
                foreach ($users as $user_id) {
                    $user = api_get_user_info($user_id);
                    CourseManager::subscribeUser($user_id, $course_code);
                }
            }
            echo Display::return_message(get_lang('UsersAreSubscibedToCourse'), 'confirm');
        }
    }
}

/* Display GUI */
if (empty($first_letter_user)) {
    $sql = "SELECT count(*) as nb_users FROM $tbl_user";
    $result = Database::query($sql);
    $num_row = Database::fetch_array($result);
    if ($num_row['nb_users'] > 1000) {
        //if there are too much users to gracefully handle with the HTML select list,
        // assign a default filter on users names
        $first_letter_user = 'A';
    }
    unset($result);
}

$where_filter = null;
$extra_field_result = [];
//Filter by Extra Fields
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
                $final_result = array_intersect($extra_field_result[$i], $extra_field_result[$i + 1]);
            }
        }
    } else {
        $final_result = $extra_field_result[0];
    }

    if (api_is_multiple_url_enabled()) {
        if (is_array($final_result) && count($final_result) > 0) {
            $where_filter = " AND u.user_id IN  ('".implode("','", $final_result)."') ";
        } else {
            //no results
            $where_filter = " AND u.user_id  = -1";
        }
    } else {
        if (is_array($final_result) && count($final_result) > 0) {
            $where_filter = " AND user_id IN  ('".implode("','", $final_result)."') ";
        } else {
            //no results
            $where_filter = " AND user_id  = -1";
        }
    }
}

$target_name = 'lastname';
$orderBy = $target_name;
$showOfficialCode = false;
$orderListByOfficialCode = api_get_setting('order_user_list_by_official_code');
if ($orderListByOfficialCode === 'true') {
    $showOfficialCode = true;
    $orderBy = " official_code, lastname, firstname";
}

$sql = "SELECT user_id, lastname, firstname, username, official_code
        FROM $tbl_user
        WHERE user_id<>2 AND ".$target_name." LIKE '".$first_letter_user."%' $where_filter
        ORDER BY ".(count($users) > 0 ? "(user_id IN(".implode(',', $users).")) DESC," : "")." ".$orderBy;

if (api_is_multiple_url_enabled()) {
    $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $sql = "SELECT u.user_id,lastname,firstname,username, official_code
                FROM $tbl_user u
                INNER JOIN $tbl_user_rel_access_url user_rel_url
                ON (user_rel_url.user_id = u.user_id)
                WHERE
                    u.user_id<>2 AND
                    access_url_id =  $access_url_id AND
                    (".$target_name." LIKE '".$first_letter_user."%' )
                    $where_filter
                ORDER BY ".(count($users) > 0 ? "(u.user_id IN(".implode(',', $users).")) DESC," : "")." ".$orderBy;
    }
}

$result = Database::query($sql);
$db_users = Database::store_result($result);
unset($result);

$sql = "SELECT code,visual_code,title
        FROM $tbl_course
        WHERE visual_code LIKE '".$first_letter_course."%'
        ORDER BY ".(count($courses) > 0 ? "(code IN('".implode("','", $courses)."')) DESC," : "")." visual_code";

if (api_is_multiple_url_enabled()) {
    $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $sql = "SELECT code, visual_code, title
                FROM $tbl_course as course
                INNER JOIN $tbl_course_rel_access_url course_rel_url
                ON (course_rel_url.c_id = course.id)
                WHERE
                    access_url_id =  $access_url_id  AND
                    (visual_code LIKE '".$first_letter_course."%' )
                ORDER BY ".(count($courses) > 0 ? "(code IN('".implode("','", $courses)."')) DESC," : "")." visual_code";
    }
}

$result = Database::query($sql);
$db_courses = Database::store_result($result);
unset($result);
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
<?php
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
            $extraHidden = $fieldtype == ExtraField::FIELD_TYPE_TAG ? '<input type="hidden" name="field_id" value="'.$option['field_id'].'" />' : '';
            echo $extraHidden;
            echo '&nbsp;&nbsp;';
        }
        echo '<input class="btn btn-primary" type="button" value="'.get_lang('Filter').'" onclick="validate_filter()" ></input>';
        echo '<br /><br />';
    }
}
?>
 <input type="hidden" name="form_sent" value="1"/>
  <table border="0" cellpadding="5" cellspacing="0" width="100%">
   <tr>
    <td width="40%" align="center">
     <b><?php echo get_lang('UserList'); ?></b>
     <br/><br/>
        <?php echo get_lang('FirstLetterUser'); ?> :
     <select name="firstLetterUser"
        onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();"
        aria-label="<?php echo get_lang('FirstLetterUser'); ?>">
      <option value="">--</option>
      <?php
        echo Display::get_alphabet_options($first_letter_user);
      ?>
     </select>
    </td>
    <td width="20%">&nbsp;</td>
    <td width="40%" align="center">
     <b><?php echo get_lang('CourseList'); ?> :</b>
     <br/><br/>
        <?php echo get_lang('FirstLetterCourse'); ?> :
     <select name="firstLetterCourse"
        onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();"
        aria-label="<?php echo get_lang('FirstLetterCourse'); ?>">
      <option value="">--</option>
      <?php
      echo Display::get_alphabet_options($first_letter_course);
      ?>
     </select>
    </td>
   </tr>
   <tr>
    <td width="40%" align="center">
     <select name="UserList[]" multiple="multiple" size="20" style="width:300px;">
    <?php foreach ($db_users as $user) {
          ?>
          <option value="<?php echo $user['user_id']; ?>" <?php if (in_array($user['user_id'], $users)) {
              echo 'selected="selected"';
          } ?>>
      <?php
        $userName = $user['lastname'].' '.$user['firstname'].' ('.$user['username'].')';
          if ($showOfficialCode) {
              $officialCode = !empty($user['official_code']) ? $user['official_code'].' - ' : '? - ';
              $userName = $officialCode.$userName;
          }
          echo $userName; ?>
          </option>
    <?php
      } ?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">
    <button type="submit" class="btn btn-primary" value="<?php echo get_lang('AddToThatCourse'); ?> &gt;&gt;">
        <em class="fa fa-plus"></em> <?php echo get_lang('AddToThatCourse'); ?>
    </button>
   </td>
   <td width="40%" align="center">
    <select name="CourseList[]" multiple="multiple" size="20" style="width:300px;">
    <?php foreach ($db_courses as $course) {
          ?>
         <option value="<?php echo $course['code']; ?>" <?php if (in_array($course['code'], $courses)) {
              echo 'selected="selected"';
          } ?>>
             <?php echo '('.$course['visual_code'].') '.$course['title']; ?>
         </option>
    <?php
      } ?>
    </select>
   </td>
  </tr>
 </table>
</form>
<?php

Display::display_footer();
