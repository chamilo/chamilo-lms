<?php
/* For licensing terms, see /license.txt */

/**
 *  Interface for assigning users to Human Resources Manager.
 */

use Chamilo\CoreBundle\Enums\ObjectIcon;

// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$ajax_search = false;
// create an ajax object
$xajax = new xajax();
$xajax->registerFunction('search_users');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => '/admin/user-list', 'name' => get_lang('User list')];

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_access_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

// initializing variables
$user_id = isset($_GET['user']) ? (int) $_GET['user'] : 0;
$user_info = api_get_user_info($user_id);
$user_anonymous = api_get_anonymous_id();
$current_user_id = api_get_user_id();
$userStatus = $user_info['status'];

$user = api_get_user_entity($user_id);
$isSessionAdmin = api_is_session_admin($user);
$firstLetterUser = isset($_POST['firstLetterUser']) ? Security::remove_XSS($_POST['firstLetterUser']) : null;

// setting the name of the tool
$isAdmin = UserManager::is_admin($user_id);
if ($isAdmin) {
    $userStatus = PLATFORM_ADMIN;
    $tool_name = get_lang('Assign users to the platform administrator');
} elseif ($isSessionAdmin) {
    $tool_name = get_lang('Assign users to sessions administrator');
} elseif (api_is_student_boss($user)) {
    $tool_name = get_lang('Assign users to superior');
} else {
    $tool_name = get_lang('Assign users to Human Resources manager');
}

$add_type = 'multiple';
if (isset($_GET['add_type']) && '' != $_GET['add_type']) {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

function search_users($needle, $type = 'multiple')
{
    global $tbl_access_url_rel_user, $tbl_user, $user_anonymous, $current_user_id, $user_id, $userStatus;

    $xajax_response = new xajaxResponse();
    $return = '';
    if (!empty($needle) && !empty($type)) {
        $assigned_users_to_hrm = [];

        switch ($userStatus) {
            case STUDENT_BOSS:
                $assigned_users_to_hrm = UserManager::getUsersFollowedByStudentBoss($user_id);

                break;
            case DRH:
            case PLATFORM_ADMIN:
            default:
                $assigned_users_to_hrm = UserManager::get_users_followed_by_drh($user_id);

                break;
        }

        $assigned_users_id = array_keys($assigned_users_to_hrm);
        $without_assigned_users = '';

        $westernOrder = api_is_western_name_order();
        if ($westernOrder) {
            $order_clause = ' ORDER BY firstname, lastname';
        } else {
            $order_clause = ' ORDER BY lastname, firstname';
        }

        if (count($assigned_users_id) > 0) {
            $without_assigned_users = ' AND user.id NOT IN('.implode(',', $assigned_users_id).')';
        }

        if (api_is_multiple_url_enabled()) {
            $sql = "SELECT user.id as user_id, username, lastname, firstname
                    FROM $tbl_user user
                    LEFT JOIN $tbl_access_url_rel_user au ON (au.user_id = user.id)
                    WHERE user.active <> ".USER_SOFT_DELETED." AND
                        ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%' AND
                        status NOT IN(".DRH.', '.SESSIONADMIN.', '.STUDENT_BOSS.") AND
                        user.id NOT IN ($user_anonymous, $current_user_id, $user_id)
                        $without_assigned_users AND
                        access_url_id = ".api_get_current_access_url_id()."
                    $order_clause
                    ";
        } else {
            $sql = "SELECT id as user_id, username, lastname, firstname
                    FROM $tbl_user user
                    WHERE user.active <> ".USER_SOFT_DELETED." AND
                        ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%' AND
                        status NOT IN(".DRH.', '.SESSIONADMIN.', '.STUDENT_BOSS.") AND
                        id NOT IN ($user_anonymous, $current_user_id, $user_id)
                    $without_assigned_users
                    $order_clause
            ";
        }
        $rs = Database::query($sql);
        $xajax_response->addAssign('ajax_list_users_multiple', 'innerHTML', api_utf8_encode($return));

        if ('single' == $type) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();

            $sql = 'SELECT user.id as user_id, username, lastname, firstname
                    FROM '.$tbl_user.' user
                    INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.id)
                    WHERE user.active <> '.USER_SOFT_DELETED.' AND
                        access_url_id = '.$access_url_id.'  AND
                        (
                            username LIKE "'.$needle.'%" OR
                            firstname LIKE "'.$needle.'%" OR
                            lastname LIKE "'.$needle.'%"
                        ) AND ';

            switch ($userStatus) {
                case DRH:
                    $sql .= ' user.status <> 6 AND user.status <> '.DRH;

                    break;
                case STUDENT_BOSS:
                    $sql .= ' user.status <> 6 AND user.status <> '.STUDENT_BOSS;

                    break;
            }

            $sql .= " $order_clause LIMIT 11";

            $rs = Database::query($sql);
            $i = 0;
            while ($user = Database :: fetch_array($rs)) {
                $i++;
                if ($i <= 10) {
                    $person_name = api_get_person_name($user['firstname'], $user['lastname']);
                    $return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_user(\''.$user['user_id'].'\',\''.$person_name.' ('.$user['username'].')'.'\')">'.$person_name.' ('.$user['username'].')</a><br />';
                } else {
                    $return .= '...<br />';
                }
            }
            $xajax_response->addAssign(
                'ajax_list_users_single',
                'innerHTML',
                api_utf8_encode($return)
            );
        } else {
            $return .= '<select id="origin" ondblclick="moveItem(document.getElementById(&quot;origin&quot;), document.getElementById(&quot;destination&quot;))" class="form-control h-96 w-full min-w-0 rounded-xl border-gray-25 text-body-2 text-gray-90" name="NoAssignedUsersList[]" multiple="multiple" size="15">';
            while ($user = Database :: fetch_array($rs)) {
                $person_name = api_get_person_name($user['firstname'], $user['lastname']);
                $return .= '<option value="'.$user['user_id'].'" title="'.htmlspecialchars($person_name, ENT_QUOTES).'">'.$person_name.' ('.$user['username'].')</option>';
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
var assignmentFormChanged = false;

function setAssignmentFormChanged() {
    assignmentFormChanged = true;
    var field = document.getElementById("assignmentChanged");
    if (field) {
        field.value = "1";
    }

    var saveButton = document.getElementById("assign_user");
    if (saveButton) {
        saveButton.disabled = false;
    }
}

function add_user_to_user (code, content) {
    document.getElementById("user_to_add").value = "";
    document.getElementById("ajax_list_users_single").innerHTML = "";

    destination = document.getElementById("destination");

    for (i=0;i<destination.length;i++) {
        if(destination.options[i].text == content) {
                return false;
        }
    }
    destination.options[destination.length] = new Option(content,code);
    destination.selectedIndex = -1;
    sortOptions(destination.options);
    setAssignmentFormChanged();
}
function moveItem(origin , destination) {
    var moved = false;

    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
            origin.options[i]=null;
            i = i-1;
            moved = true;
        }
    }

    if (moved) {
        destination.selectedIndex = -1;
        sortOptions(destination.options);
        setAssignmentFormChanged();
    }

    return moved;
}
function sortOptions(options) {
    var newOptions = new Array();
    for (i = 0 ; i<options.length ; i++) {
        newOptions[i] = options[i];
    }
    newOptions = newOptions.sort(mysort);
    options.length = 0;
    for(i = 0 ; i < newOptions.length ; i++){
        options[i] = newOptions[i];
    }
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

function syncAssignedItems() {
    var destination = document.getElementById("destination");
    var assignedItems = document.getElementById("assignedItems");

    if (!destination || !assignedItems) {
        return;
    }

    var values = [];
    for (var i = 0; i < destination.options.length; i++) {
        values.push(destination.options[i].value);
    }

    assignedItems.value = values.join(",");
}

function valide() {
    var changedField = document.getElementById("assignmentChanged");

    if (!assignmentFormChanged && (!changedField || changedField.value !== "1")) {
        return false;
    }

    var options = document.getElementById("destination").options;
    for (i = 0 ; i<options.length ; i++) {
        options[i].selected = true;
    }

    syncAssignedItems();
    document.forms.formulaire.submit();
}
function remove_item(origin) {
    var removed = false;

    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
            removed = true;
        }
    }

    if (removed) {
        setAssignmentFormChanged();
    }

    return removed;
}
</script>';

$formSent = 0;
$errorMsg = '';
$UserList = [];

// Filters
$filters = [
    ['type' => 'text', 'name' => 'username', 'label' => get_lang('Username')],
    ['type' => 'text', 'name' => 'firstname', 'label' => get_lang('First name')],
    ['type' => 'text', 'name' => 'lastname', 'label' => get_lang('Last name')],
    ['type' => 'text', 'name' => 'official_code', 'label' => get_lang('Code')],
    ['type' => 'text', 'name' => 'email', 'label' => get_lang('E-mail')],
];

$searchForm = new FormValidator('search', 'get', api_get_self().'?user='.$user_id);
$searchForm->addHeader(get_lang('Advanced search'));
$renderer = &$searchForm->defaultRenderer();

$searchForm->addElement('hidden', 'user', $user_id);
foreach ($filters as $param) {
    $searchForm->addElement($param['type'], $param['name'], $param['label']);
}
$searchForm->addButtonSearch(get_lang('Search'));

$filterData = [];
if ($searchForm->validate()) {
    $filterData = $searchForm->getSubmitValues();
}

$conditions = [];
if (!empty($filters) && !empty($filterData)) {
    foreach ($filters as $filter) {
        if (isset($filter['name']) && isset($filterData[$filter['name']])) {
            $value = $filterData[$filter['name']];
            if (!empty($value)) {
                $conditions[$filter['name']] = $value;
            }
        }
    }
}

if (isset($_POST['formSent']) && 1 == (int) ($_POST['formSent']) && isset($_POST['assignmentChanged']) && '1' === (string) $_POST['assignmentChanged']) {
    if (isset($_POST['assignedItems']) && '' !== (string) $_POST['assignedItems']) {
        $user_list = array_filter(
            array_map('intval', explode(',', (string) Security::remove_XSS($_POST['assignedItems']))),
            function ($userId) {
                return $userId > 0;
            }
        );
    } else {
        $user_list = isset($_POST['UsersList']) ? array_map('intval', Security::remove_XSS($_POST['UsersList'])) : [];
    }

    switch ($userStatus) {
        case STUDENT_BOSS:
            UserManager::subscribeBossToUsers($user_id, $user_list);

            break;
        case DRH:
        case PLATFORM_ADMIN:
        default:
            UserManager::subscribeUsersToHRManager($user_id, $user_list);

            break;
    }

    Display::addFlash(
        Display::return_message(
            get_lang('The assigned users have been updated'),
            'normal'
        )
    );
}

// Display header
Display::display_header($tool_name);

// actions
$actionsLeft = '';
if (STUDENT_BOSS != $userStatus) {
    $actionsLeft = Display::url(
        Display::getMdiIcon(ObjectIcon::COURSE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assign courses')),
        "dashboard_add_courses_to_user.php?user=$user_id"
    );

    $actionsLeft .= Display::url(
        Display::getMdiIcon(ObjectIcon::SESSION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assign sessions')),
        "dashboard_add_sessions_to_user.php?user=$user_id"
    );
}

$actionsRight = Display::url(
    '<em class="fa fa-search"></em> '.get_lang('Advanced search'),
    '#',
    ['class' => 'btn btn--plain advanced_options', 'id' => 'advanced_search']
);

$toolbar = Display::toolbarAction('toolbar-dashboard', [$actionsLeft, $actionsRight]);
echo $toolbar;

echo '<div id="advanced_search_options" style="display:none">';
$searchForm->display();
echo '</div>';

echo Display::page_header(
    sprintf(
        get_lang('Assign users to %s'),
        UserManager::formatUserFullName($user)
    ),
    null,
    'h3'
);

$assigned_users_to_hrm = [];

switch ($userStatus) {
    case STUDENT_BOSS:
        $assigned_users_to_hrm = UserManager::getUsersFollowedByStudentBoss($user_id);

        break;
    case DRH:
    case PLATFORM_ADMIN:
    default:
        $assigned_users_to_hrm = UserManager::get_users_followed_by_drh($user_id);

        break;
}

$assigned_users_id = array_keys($assigned_users_to_hrm);
$without_assigned_users = '';
if (count($assigned_users_id) > 0) {
    $without_assigned_users = ' user.id NOT IN('.implode(',', $assigned_users_id).') AND ';
}

$search_user = '';
$needle = '';
if (!empty($firstLetterUser)) {
    $needle = Database::escape_string($firstLetterUser);
    $search_user = 'AND '.(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%'";
}

$sqlConditions = null;
if (!empty($conditions)) {
    $temp_conditions = [];
    foreach ($conditions as $field => $value) {
        $field = Database::escape_string($field);
        $value = Database::escape_string($value);
        $temp_conditions[] = $field.' LIKE \'%'.$value.'%\'';
    }
    if (!empty($temp_conditions)) {
        $sqlConditions .= implode(' AND ', $temp_conditions);
    }
    if (!empty($sqlConditions)) {
        $sqlConditions = " AND $sqlConditions";
    }
}

if (api_is_multiple_url_enabled()) {
    $sql = "SELECT user.id as user_id, username, lastname, firstname
            FROM $tbl_user user
            LEFT JOIN $tbl_access_url_rel_user au
            ON (au.user_id = user.id)
            WHERE user.active <> ".USER_SOFT_DELETED." AND
                $without_assigned_users
                user.id NOT IN ($user_anonymous, $current_user_id, $user_id) AND
                status NOT IN(".DRH.', '.SESSIONADMIN.', '.ANONYMOUS.") $search_user AND
                access_url_id = ".api_get_current_access_url_id()."
                $sqlConditions
            ORDER BY firstname";
} else {
    $sql = "SELECT id as user_id, username, lastname, firstname
            FROM $tbl_user user
            WHERE user.active <> -1 AND
                $without_assigned_users
                id NOT IN ($user_anonymous, $current_user_id, $user_id) AND
                status NOT IN(".DRH.', '.SESSIONADMIN.', '.ANONYMOUS.")
                $search_user
                $sqlConditions
            ORDER BY firstname ";
}
$result = Database::query($sql);
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?user=<?php echo $user_id; ?>" class="form-horizontal w-full w-100" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
}?>>
<input type="hidden" name="formSent" value="1" />
<input type="hidden" id="assignmentChanged" name="assignmentChanged" value="0" />
<input type="hidden" id="assignedItems" name="assignedItems" value="" />

<div class="row g-4 align-items-stretch w-full w-100">
    <div class="col-12 col-md-5 col-xl-5">
        <section class="min-w-0 h-full h-100 rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
            <label for="origin" class="mb-3 block text-body-2 font-semibold text-gray-90">
                <?php echo get_lang('Portal users list'); ?>
            </label>
            <div id="ajax_list_users_multiple">
                <select id="origin" ondblclick="moveItem(document.getElementById(&quot;origin&quot;), document.getElementById(&quot;destination&quot;))" class="form-control h-96 w-full min-w-0 rounded-xl border-gray-25 text-body-2 text-gray-90" name="NoAssignedUsersList[]" multiple="multiple" size="15">
                    <?php
                    while ($enreg = Database::fetch_array($result)) {
                        $person_name = api_get_person_name($enreg['firstname'], $enreg['lastname']); ?>
                        <option value="<?php echo $enreg['user_id']; ?>" <?php echo 'title="'.htmlspecialchars($person_name, ENT_QUOTES).'"'; ?>>
                        <?php echo $person_name.' ('.$enreg['username'].')'; ?>
                        </option>
                    <?php
                    } ?>
                </select>
            </div>
        </section>
    </div>

    <div class="col-12 col-md-2 col-xl-1">
        <section class="min-w-0 h-full h-100 rounded-2xl border border-gray-20 bg-support-2 p-4 shadow-sm">
            <div class="h-full min-h-96 flex flex-col justify-center gap-3 h-100">
                <?php if ('multiple' == $add_type) {
                            ?>
                    <div>
                        <label for="firstLetterUser" class="mb-2 block text-body-2 font-semibold text-gray-90">
                            <?php echo get_lang('First letter (last name)'); ?>
                        </label>
                        <select id="firstLetterUser" class="selectpicker show-tick form-control w-full rounded-xl border-gray-25 text-body-2 text-gray-90" name="firstLetterUser" onchange="xajax_search_users(this.value,'multiple')">
                            <option value="%">--</option>
                            <?php echo Display::get_alphabet_options($firstLetterUser); ?>
                        </select>
                    </div>
                <?php
                        } ?>

                <div class="flex flex-col items-center justify-center gap-3">
                <?php if ($ajax_search) {
                            ?>
                    <button class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center border-0 bg-secondary text-secondary-button-text hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary" type="button" onclick="remove_item(document.getElementById('destination'))" title="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                        <span class="mdi mdi-arrow-left-bold text-white text-lg" aria-hidden="true"></span>
                        <span class="sr-only"><?php echo get_lang('Remove'); ?></span>
                    </button>
                <?php
                        } else {
                            ?>
                    <button id="add_user_button" class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center border-0 bg-secondary text-secondary-button-text hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" title="<?php echo htmlspecialchars(get_lang('Add'), ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars(get_lang('Add'), ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                        <span class="mdi mdi-arrow-right-bold text-white text-lg" aria-hidden="true"></span>
                        <span class="sr-only"><?php echo get_lang('Add'); ?></span>
                    </button>

                    <button id="remove_user_button" class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center border-0 bg-secondary text-secondary-button-text hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" title="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                        <span class="mdi mdi-arrow-left-bold text-white text-lg" aria-hidden="true"></span>
                        <span class="sr-only"><?php echo get_lang('Remove'); ?></span>
                    </button>
                <?php
                        } ?>

                    <button id="assign_user" class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center disabled:cursor-not-allowed disabled:opacity-50 border-0 bg-success text-success-button-text hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success" type="button" disabled="disabled" value="" onclick="valide()" title="<?php echo htmlspecialchars($tool_name, ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars($tool_name, ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                        <span class="mdi mdi-content-save-outline text-white text-lg" aria-hidden="true"></span>
                        <span class="sr-only"><?php echo $tool_name; ?></span>
                    </button>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-md-5 col-xl-6">
        <section class="min-w-0 h-full h-100 rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
            <label for="destination" class="mb-3 block text-body-2 font-semibold text-gray-90">
            <?php
            if (UserManager::is_admin($user_id)) {
                echo get_lang('Users assigned to the platform administrator');
            } else {
                if ($isSessionAdmin) {
                    echo get_lang('Assign a users list to the sessions administrator');
                } else {
                    if (api_is_student_boss($user)) {
                        echo get_lang('Users assigned to their superior');
                    } else {
                        echo get_lang('List of users assigned to Human Resources manager');
                    }
                }
            }
            ?>
            </label>
            <select id="destination" ondblclick="moveItem(document.getElementById(&quot;destination&quot;), document.getElementById(&quot;origin&quot;))" class="form-control h-96 w-full min-w-0 rounded-xl border-gray-25 text-body-2 text-gray-90" name="UsersList[]" multiple="multiple" size="15">
                <?php
                if (is_array($assigned_users_to_hrm)) {
                    foreach ($assigned_users_to_hrm as $enreg) {
                        $person_name = api_get_person_name($enreg['firstname'], $enreg['lastname']); ?>
                        <option value="<?php echo $enreg['user_id']; ?>" <?php echo 'title="'.htmlspecialchars($person_name, ENT_QUOTES).'"'; ?>>
                        <?php echo $person_name.' ('.$enreg['username'].')'; ?>
                        </option>
                    <?php
                    }
                }?>
            </select>
        </section>
    </div>
</div>
</form>
<?php
Display::display_footer();
