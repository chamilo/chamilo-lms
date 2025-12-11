<?php

/* For licensing terms, see /license.txt */

/**
 *	This script allows platform admins to add users to courses.
 *	It displays a list of users and a list of courses;
 *	you can select multiple users and courses and then click on
 *	'Add to this(these) course(s)'.
 *
 * 	@todo use formvalidator for the form
 */

use Chamilo\CoreBundle\Enums\ObjectIcon;

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
$tool_name = get_lang('Add users to course');
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];

$htmlHeadXtra[] = '<script>
function validate_filter() {
  document.formulaire.form_sent.value=0;
  document.formulaire.submit();
}
</script>';

// displaying the header
Display::display_header($tool_name);

$link_add_group = '<a href="usergroups.php">'.
    Display::getMdiIcon(ObjectIcon::MULTI_ELEMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enrolment by classes')).get_lang('Enrolment by classes').'</a>';
echo Display::toolbarAction('subscribe', [$link_add_group]);

/**
 * We show this once at the top so admins are aware before selecting anything.
 */
$__globalLimit = (int) api_get_setting('platform.hosting_limit_users_per_course'); // 0 => disabled
if ($__globalLimit > 0) {
    echo Display::return_message(
        sprintf('A global limit of %d users applies to every course (teachers included).', $__globalLimit),
        'warning'
    );
}

$form = new FormValidator('subscribe_user2course');
$form->addElement('header', '', $tool_name);
$form->display();

//checking for extra field with filter on
$extra_field_list = UserManager::get_extra_fields();

$new_field_list = [];
if (is_array($extra_field_list)) {
    foreach ($extra_field_list as $extra_field) {
        // if is enabled to filter and is a "<select>" or "tag" type
        if (1 == $extra_field[8] && ExtraField::FIELD_TYPE_SELECT == $extra_field[2]) {
            $new_field_list[] = [
                'name' => $extra_field[3],
                'type' => $extra_field[2],
                'variable' => $extra_field[1],
                'data' => $extra_field[9],
            ];
        }
        if (1 == $extra_field[8] && ExtraField::FIELD_TYPE_TAG == $extra_field[2]) {
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
    $form_sent = (int) $_POST['form_sent'];
    $users = isset($_POST['UserList']) && is_array($_POST['UserList']) ? $_POST['UserList'] : [];
    $courses = isset($_POST['CourseList']) && is_array($_POST['CourseList']) ? $_POST['CourseList'] : [];
    $first_letter_user = Database::escape_string($_POST['firstLetterUser']);
    $first_letter_course = Database::escape_string($_POST['firstLetterCourse']);

    foreach ($users as $key => $value) {
        $users[$key] = (int) $value;
    }

    if (1 === $form_sent) {
        if (0 === count($users) || 0 === count($courses)) {
            echo Display::return_message(get_lang('You must select at least one user and one course'), 'error');
        } else {
            $errorDrh = 0;
            $successCount = 0;
            $skippedFull = 0;

            foreach ($courses as $course_code) {
                $courseInfo = api_get_course_info($course_code);
                if (empty($courseInfo)) {
                    // Defensive log
                    Display::addFlash(Display::return_message('Course not found: '.$course_code, 'warning'));
                    continue;
                }

                // Enforce global limit here as well, to avoid needless subscribe calls
                if ($__globalLimit > 0) {
                    $limitState = _compute_course_limit_state_by_real_id($courseInfo['real_id'], $__globalLimit);
                    if ($limitState['full']) {
                        // Avoid looping users for a known-full course, provide a single message and skip
                        Display::addFlash(Display::return_message(
                            sprintf('Course "%s" is full (%d/%d). Skipping subscriptions for this course.',
                                $courseInfo['title'], $limitState['current'], $limitState['limit']
                            ),
                            'warning'
                        ));
                        $skippedFull++;
                        continue;
                    }
                }

                foreach ($users as $user_id) {
                    $user = api_get_user_info($user_id);
                    if (DRH != $user['status']) {
                        $result = CourseManager::subscribeUser($user_id, $courseInfo['real_id']);

                        if (is_array($result)) {
                            // Expected keys: ok(bool), message(string)
                            if (isset($result['message'])) {
                                Display::addFlash(
                                    Display::return_message($result['message'], !empty($result['ok']) ? 'normal' : 'warning')
                                );
                            } else {
                                // assume ok by presence of array
                                $successCount++;
                            }
                        } else {
                            if ($result === true) {
                                $successCount++;
                            }
                        }

                    } else {
                        $errorDrh = 1;
                    }
                }
            }

            // Summaries
            if ($successCount > 0) {
                echo Display::return_message(
                    sprintf(get_lang('The selected users are subscribed to the selected course').' (%d %s)', $successCount, get_lang('operations')),
                    'confirm'
                );
            }

            if ($skippedFull > 0) {
                echo Display::return_message(
                    sprintf('%d course(s) skipped because they are full.', $skippedFull),
                    'warning'
                );
            }

            if (1 === $errorDrh) {
                echo Display::return_message(
                    get_lang(
                        'Human resources managers should not be registered to courses. The corresponding users you selected have not been subscribed.'
                    ),
                    'error'
                );
            }
        }
    }
}

/* Display GUI */
if (empty($first_letter_user)) {
    $sql = "SELECT count(*) as nb_users FROM $tbl_user";
    $result = Database::query($sql);
    $num_row = Database::fetch_array($result);
    if ($num_row['nb_users'] > 1000) {
        // If there are too many users, default filter to "A" to keep lists light
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
                if (isset($_POST[$varname]) && '0' != $_POST[$varname]) {
                    $use_extra_fields = true;
                    if (ExtraField::FIELD_TYPE_TAG == $fieldtype) {
                        $extra_field_result[] = UserManager::get_extra_user_data_by_tags(
                            (int) $_POST['field_id'],
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
            $where_filter = " AND u.id IN  ('".implode("','", $final_result)."') ";
        } else {
            $where_filter = " AND u.id  = -1";
        }
    } else {
        if (is_array($final_result) && count($final_result) > 0) {
            $where_filter = " AND id IN  ('".implode("','", $final_result)."') ";
        } else {
            $where_filter = " AND id  = -1";
        }
    }
}

$target_name = 'lastname';
$orderBy = $target_name;
$showOfficialCode = false;
$orderListByOfficialCode = api_get_setting('display.order_user_list_by_official_code');
if ('true' === $orderListByOfficialCode) {
    $showOfficialCode = true;
    $orderBy = " official_code, lastname, firstname";
}

$sql = "SELECT id as user_id, lastname, firstname, username, official_code
        FROM $tbl_user
        WHERE id <>2 AND ".$target_name." LIKE '".$first_letter_user."%' $where_filter
        ORDER BY ".(count($users) > 0 ? "(id IN(".implode(',', $users).")) DESC," : "")." ".$orderBy;

if (api_is_multiple_url_enabled()) {
    $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $access_url_id = api_get_current_access_url_id();
    if (-1 != $access_url_id) {
        $sql = "SELECT u.id as user_id,lastname,firstname,username, official_code
                FROM $tbl_user u
                INNER JOIN $tbl_user_rel_access_url user_rel_url
                ON (user_rel_url.user_id = u.id)
                WHERE
                    u.id <> 2 AND
                    access_url_id =  $access_url_id AND
                    (".$target_name." LIKE '".$first_letter_user."%' )
                    $where_filter
                ORDER BY ".(count($users) > 0 ? "(u.id IN(".implode(',', $users).")) DESC," : "")." ".$orderBy;
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
    if (-1 != $access_url_id) {
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
    <form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" class="w-full px-4 sm:px-6 lg:px-8">
        <input type="hidden" name="form_sent" value="1"/>

        <?php
        if (is_array($extra_field_list)) {
            if (is_array($new_field_list) && count($new_field_list) > 0) {
                echo '<div class="mb-6 rounded-2xl border border-gray-25 p-4 bg-white shadow-sm w-full overflow-hidden">';
                echo '<h3 class="text-lg font-semibold mb-3">'.get_lang('Filter users').'</h3>';
                echo '<div class="flex flex-wrap gap-3 min-w-0">';
                foreach ($new_field_list as $new_field) {
                    echo '<label class="text-sm font-medium">'.htmlspecialchars($new_field['name']).'</label>';
                    $varname = 'field_'.$new_field['variable'];
                    $fieldtype = $new_field['type'];

                    echo '<select name="'.$varname.'" class="form-select rounded-xl border border-gray-25 px-3 py-2 text-sm w-full sm:w-auto max-w-full">';
                    echo '<option value="0">--'.get_lang('Select').'--</option>';
                    foreach ($new_field['data'] as $option) {
                        $checked = '';
                        if (ExtraField::FIELD_TYPE_TAG == $fieldtype) {
                            if (isset($_POST[$varname]) && $_POST[$varname] == $option['tag']) {
                                $checked = 'selected="true"';
                            }
                            echo '<option value="'.Security::remove_XSS($option['tag']).'" '.$checked.'>'.$option['tag'].'</option>';
                        } else {
                            if (isset($_POST[$varname]) && $_POST[$varname] == $option[1]) {
                                $checked = 'selected="true"';
                            }
                            echo '<option value="'.Security::remove_XSS($option[1]).'" '.$checked.'>'.$option[2].'</option>';
                        }
                    }
                    echo '</select>';
                    $extraHidden = ExtraField::FIELD_TYPE_TAG == $fieldtype ? '<input type="hidden" name="field_id" value="'.(int) $option['field_id'].'" />' : '';
                    echo $extraHidden;
                }
                echo '</div>';
                echo '<div class="mt-4">';
                echo '<button type="button" onclick="validate_filter()" class="inline-flex items-center rounded-2xl bg-primary px-4 py-2 text-white text-sm font-medium hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary">'.get_lang('Filter').'</button>';
                echo '</div>';
                echo '</div>';
            }
        }
        ?>

        <div class="w-full flex flex-col md:flex-row items-stretch gap-6">

            <!-- Users pane (left) -->
            <div class="flex-1 md:basis-5/12 min-w-[420px] rounded-2xl border border-gray-25 p-4 bg-white shadow-sm">
                <label class="block text-sm font-medium mb-2"><?php echo get_lang('User list'); ?></label>

                <div class="flex items-center gap-2 mb-3">
                    <span class="text-sm text-gray-90"><?php echo get_lang('First letter (last name)'); ?>:</span>
                    <select name="firstLetterUser"
                            onchange="document.formulaire.form_sent.value='2'; document.formulaire.submit();"
                            aria-label="<?php echo get_lang('First letter (last name)'); ?>"
                            class="rounded-xl border border-gray-25 px-3 py-1 text-sm w-auto">
                        <option value="">--</option>
                        <?php echo Display::get_alphabet_options($first_letter_user); ?>
                    </select>
                </div>

                <select name="UserList[]" multiple size="20"
                        class="block w-full max-w-none min-w-[400px] h-[28rem] rounded-2xl border border-gray-25 bg-white px-3 py-2 text-sm">
                    <?php foreach ($db_users as $user) { ?>
                        <option value="<?php echo (int) $user['user_id']; ?>" <?php if (in_array($user['user_id'], $users)) echo 'selected="selected"'; ?>>
                            <?php
                            $userName = $user['lastname'].' '.$user['firstname'].' ('.$user['username'].')';
                            if ($showOfficialCode) {
                                $officialCode = !empty($user['official_code']) ? $user['official_code'].' - ' : '? - ';
                                $userName = $officialCode.$userName;
                            }
                            echo Security::remove_XSS($userName);
                            ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- Center action -->
            <div class="md:basis-2/12 flex items-center justify-center">
                <button type="submit"
                        class="w-full md:w-auto inline-flex items-center justify-center rounded-2xl bg-primary px-6 py-3 text-white font-semibold shadow hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary">
                    <svg class="mr-2 h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 5v14m-7-7h14"/></svg>
                    <?php echo get_lang('Add to the course(s)'); ?>
                </button>
            </div>

            <!-- Courses pane (right) -->
            <div class="flex-1 md:basis-5/12 min-w-[420px] rounded-2xl border border-gray-25 p-4 bg-white shadow-sm">
                <label class="block text-sm font-medium mb-2"><?php echo get_lang('Course list'); ?></label>

                <div class="flex items-center gap-2 mb-3">
                    <span class="text-sm text-gray-90"><?php echo get_lang('First letter (code)'); ?>:</span>
                    <select name="firstLetterCourse"
                            onchange="document.formulaire.form_sent.value='2'; document.formulaire.submit();"
                            aria-label="<?php echo get_lang('First letter (code)'); ?>"
                            class="rounded-xl border border-gray-25 px-3 py-1 text-sm w-auto">
                        <option value="">--</option>
                        <?php echo Display::get_alphabet_options($first_letter_course); ?>
                    </select>
                </div>

                <select name="CourseList[]" multiple size="20"
                        class="block w-full max-w-none min-w-[400px] h-[28rem] rounded-2xl border border-gray-25 bg-white px-3 py-2 text-sm">
                    <?php foreach ($db_courses as $course) {
                        $suffix = '';
                        if ($__globalLimit > 0) {
                            $state = _compute_course_limit_state_by_code($course['code'], $__globalLimit);
                            $suffix = $state['full']
                                ? ' — [full '.$state['current'].'/'.$state['limit'].']'
                                : ' — [seats left: '.$state['seatsLeft'].'/'.$state['limit'].']';
                        } ?>
                        <option value="<?php echo Security::remove_XSS($course['code']); ?>" <?php if (in_array($course['code'], $courses)) echo 'selected="selected"'; ?>>
                            <?php echo '('.Security::remove_XSS($course['visual_code']).') '.Security::remove_XSS($course['title']).$suffix; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

        </div>


    </form>
<?php

Display::display_footer();

/**
 * Compute occupancy state by course code (for option labels).
 *
 * @return array{limit:int,current:int,seatsLeft:int,full:bool}
 */
function _compute_course_limit_state_by_code(string $courseCode, int $globalLimit): array
{
    $info = api_get_course_info($courseCode);
    if (empty($info) || $globalLimit <= 0) {
        return ['limit' => max(0, $globalLimit), 'current' => 0, 'seatsLeft' => $globalLimit, 'full' => false];
    }
    return _compute_course_limit_state_by_real_id((int) $info['real_id'], $globalLimit);
}

/**
 * Compute occupancy state by real course id (for precheck and labels).
 * Counts all users (teachers included), excluding RRHH relation type.
 */
function _compute_course_limit_state_by_real_id(int $courseRealId, int $globalLimit): array
{
    $current = 0;
    if ($globalLimit > 0) {
        $sqlCount = "SELECT COUNT(*) AS total
                     FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                     WHERE c_id = $courseRealId
                       AND relation_type <> ".COURSE_RELATION_TYPE_RRHH;
        $row = Database::fetch_array(Database::query($sqlCount), 'ASSOC');
        $current = (int) ($row['total'] ?? 0);
    }
    $seatsLeft = max(0, $globalLimit - $current);
    return [
        'limit' => $globalLimit,
        'current' => $current,
        'seatsLeft' => $seatsLeft,
        'full' => $globalLimit > 0 && $current >= $globalLimit,
    ];
}
