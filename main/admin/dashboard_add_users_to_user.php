<?php
/* For licensing terms, see /license.txt */

/**
 *  Interface for assigning users to Human Resources Manager.
 */

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
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'user_list.php', 'name' => get_lang('UserList')];

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_access_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

// initializing variables
$user_id = isset($_GET['user']) ? (int) $_GET['user'] : 0;
$user_info = api_get_user_info($user_id);
$user_anonymous = api_get_anonymous_id();
$current_user_id = api_get_user_id();

$userStatus = api_get_user_status($user_id);

$firstLetterUser = isset($_POST['firstLetterUser']) ? $_POST['firstLetterUser'] : null;

// setting the name of the tool
$isAdmin = UserManager::is_admin($user_id);
if ($isAdmin) {
    $userStatus = PLATFORM_ADMIN;
    $tool_name = get_lang('AssignUsersToPlatformAdministrator');
} elseif ($user_info['status'] == SESSIONADMIN) {
    $tool_name = get_lang('AssignUsersToSessionsAdministrator');
} elseif ($user_info['status'] == STUDENT_BOSS) {
    $tool_name = get_lang('AssignUsersToBoss');
} else {
    $tool_name = get_lang('AssignUsersToHumanResourcesManager');
}

$add_type = 'multiple';
if (isset($_GET['add_type']) && $_GET['add_type'] != '') {
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
    $needle = Database::escape_string($needle);
    $type = Database::escape_string($type);

    if (!empty($needle) && !empty($type)) {
        $assigned_users_to_hrm = [];

        switch ($userStatus) {
            case DRH:
            case PLATFORM_ADMIN:
                $assigned_users_to_hrm = UserManager::get_users_followed_by_drh($user_id);
                break;
            case STUDENT_BOSS:
                $assigned_users_to_hrm = UserManager::getUsersFollowedByStudentBoss($user_id);
                break;
        }

        $assigned_users_id = array_keys($assigned_users_to_hrm);
        $without_assigned_users = '';

        $westernOrder = api_is_western_name_order();
        if ($westernOrder) {
            $order_clause = " ORDER BY firstname, lastname";
        } else {
            $order_clause = " ORDER BY lastname, firstname";
        }

        if (count($assigned_users_id) > 0) {
            $without_assigned_users = " AND user.user_id NOT IN(".implode(',', $assigned_users_id).")";
        }

        if (api_is_multiple_url_enabled()) {
            $sql = "SELECT user.user_id, username, lastname, firstname
                    FROM $tbl_user user
                    LEFT JOIN $tbl_access_url_rel_user au ON (au.user_id = user.user_id)
                    WHERE
                        ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%' AND
                        status NOT IN(".DRH.", ".SESSIONADMIN.", ".STUDENT_BOSS.") AND
                        user.user_id NOT IN ($user_anonymous, $current_user_id, $user_id)
                        $without_assigned_users AND
                        access_url_id = ".api_get_current_access_url_id()."
                    $order_clause
                    ";
        } else {
            $sql = "SELECT user_id, username, lastname, firstname
                    FROM $tbl_user user
                    WHERE
                        ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%' AND
                        status NOT IN(".DRH.", ".SESSIONADMIN.", ".STUDENT_BOSS.") AND
                        user_id NOT IN ($user_anonymous, $current_user_id, $user_id)
                    $without_assigned_users
                    $order_clause
            ";
        }
        $rs = Database::query($sql);
        $xajax_response->addAssign('ajax_list_users_multiple', 'innerHTML', api_utf8_encode($return));

        if ($type == 'single') {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();

            $sql = 'SELECT user.user_id, username, lastname, firstname
                    FROM '.$tbl_user.' user
                    INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
                    WHERE
                        access_url_id = '.$access_url_id.'  AND
                        (
                            username LIKE "'.$needle.'%" OR
                            firstname LIKE "'.$needle.'%" OR
                            lastname LIKE "'.$needle.'%"
                        ) AND ';

            switch ($userStatus) {
                case DRH:
                    $sql .= " user.status <> 6 AND user.status <> ".DRH;
                    break;
                case STUDENT_BOSS:
                    $sql .= " user.status <> 6 AND user.status <> ".STUDENT_BOSS;
                    break;
            }

            $sql .= " $order_clause LIMIT 11";

            $rs = Database::query($sql);
            $i = 0;
            while ($user = Database::fetch_array($rs)) {
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
            $return .= '<select id="origin" class="form-control" name="NoAssignedUsersList[]" multiple="multiple" size="15" ">';
            while ($user = Database::fetch_array($rs)) {
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
}
function moveItem(origin , destination) {
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
            origin.options[i]=null;
            i = i-1;
        }
    }
    destination.selectedIndex = -1;
    sortOptions(destination.options);
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

function valide() {
    var options = document.getElementById("destination").options;
    for (i = 0 ; i<options.length ; i++) {
        options[i].selected = true;
    }
    document.forms.formulaire.submit();
}
function remove_item(origin) {
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
        }
    }
}
</script>';

$formSent = 0;
$errorMsg = '';
$UserList = [];

// Filters
$filters = [
    ['type' => 'text', 'name' => 'username', 'label' => get_lang('Username')],
    ['type' => 'text', 'name' => 'firstname', 'label' => get_lang('FirstName')],
    ['type' => 'text', 'name' => 'lastname', 'label' => get_lang('LastName')],
    ['type' => 'text', 'name' => 'official_code', 'label' => get_lang('OfficialCode')],
    ['type' => 'text', 'name' => 'email', 'label' => get_lang('Email')],
];

$searchForm = new FormValidator('search', 'get', api_get_self().'?user='.$user_id);
$searchForm->addHeader(get_lang('AdvancedSearch'));
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

if (isset($_POST['formSent']) && intval($_POST['formSent']) == 1) {
    $user_list = isset($_POST['UsersList']) ? $_POST['UsersList'] : null;
    switch ($userStatus) {
        case DRH:
        case PLATFORM_ADMIN:
            $affected_rows = UserManager::subscribeUsersToHRManager($user_id, $user_list);
            break;
        case STUDENT_BOSS:
            $affected_rows = UserManager::subscribeBossToUsers($user_id, $user_list);
            break;
        default:
            $affected_rows = 0;
    }

    Display::addFlash(
        Display::return_message(
            get_lang('AssignedUsersHaveBeenUpdatedSuccessfully'),
            'normal'
        )
    );
}

// Display header
Display::display_header($tool_name);

// actions
$actionsLeft = '';
if ($userStatus != STUDENT_BOSS) {
    $actionsLeft = Display::url(
        Display::return_icon('course-add.png', get_lang('AssignCourses'), null, ICON_SIZE_MEDIUM),
        "dashboard_add_courses_to_user.php?user=$user_id"
    );

    $actionsLeft .= Display::url(
        Display::return_icon('session-add.png', get_lang('AssignSessions'), null, ICON_SIZE_MEDIUM),
        "dashboard_add_sessions_to_user.php?user=$user_id"
    );
}

$actionsRight = Display::url(
    '<em class="fa fa-search"></em> '.get_lang('AdvancedSearch'),
    '#',
    ['class' => 'btn btn-default advanced_options', 'id' => 'advanced_search']
);

$toolbar = Display::toolbarAction('toolbar-dashboard', [$actionsLeft, $actionsRight]);
echo $toolbar;

echo '<div id="advanced_search_options" style="display:none">';
$searchForm->display();
echo '</div>';

echo Display::page_header(
    sprintf(
        get_lang('AssignUsersToX'),
        api_get_person_name($user_info['firstname'], $user_info['lastname'])
    ),
    null,
    'h3'
);

$assigned_users_to_hrm = [];

switch ($userStatus) {
    case DRH:
    case PLATFORM_ADMIN:
        $assigned_users_to_hrm = UserManager::get_users_followed_by_drh($user_id);
        break;
    case STUDENT_BOSS:
        $assigned_users_to_hrm = UserManager::getUsersFollowedByStudentBoss($user_id);
        break;
}

$assigned_users_id = array_keys($assigned_users_to_hrm);
$without_assigned_users = '';
if (count($assigned_users_id) > 0) {
    $without_assigned_users = " user.user_id NOT IN(".implode(',', $assigned_users_id).") AND ";
}

$search_user = '';
$needle = '';
if (!empty($firstLetterUser)) {
    $needle = Database::escape_string($firstLetterUser);
    $search_user = "AND ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%'";
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
    $sql = "SELECT user.user_id, username, lastname, firstname
            FROM $tbl_user user
            LEFT JOIN $tbl_access_url_rel_user au
            ON (au.user_id = user.user_id)
            WHERE
                $without_assigned_users
                user.user_id NOT IN ($user_anonymous, $current_user_id, $user_id) AND
                status NOT IN(".DRH.", ".SESSIONADMIN.", ".ANONYMOUS.") $search_user AND
                access_url_id = ".api_get_current_access_url_id()."
                $sqlConditions
            ORDER BY firstname";
} else {
    $sql = "SELECT user_id, username, lastname, firstname
            FROM $tbl_user user
            WHERE
                $without_assigned_users
                user_id NOT IN ($user_anonymous, $current_user_id, $user_id) AND
                status NOT IN(".DRH.", ".SESSIONADMIN.", ".ANONYMOUS.")
                $search_user
                $sqlConditions
            ORDER BY firstname ";
}
$result = Database::query($sql);
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?user=<?php echo $user_id; ?>" class="form-horizontal" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
}?>>
<input type="hidden" name="formSent" value="1" />
<div class="row">
    <div class="col-md-4">
        <?php echo get_lang('UserListInPlatform'); ?>
        <div class="form-group">
            <div class="col-sm-12">
                <div id="ajax_list_users_multiple">
                    <select id="origin" class="form-control" name="NoAssignedUsersList[]" multiple="multiple" size="15">
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
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="code-course">
            <?php if ($add_type == 'multiple') {
                            ?>
                <p><?php echo get_lang('FirstLetterUser'); ?></p>
                <select class="selectpicker show-tick form-control" name="firstLetterUser" onchange = "xajax_search_users(this.value,'multiple')">
                    <option value="%">--</option>
                    <?php echo Display::get_alphabet_options($firstLetterUser); ?>
                </select>
            <?php
                        } ?>
        </div>
        <div class="control-course">
        <?php if ($ajax_search) {
                            ?>
            <div class="separate-action">
                <button class="btn btn-primary" type="button" onclick="remove_item(document.getElementById('destination'))"></button>
            </div>
        <?php
                        } else {
                            ?>
            <div class="separate-action">
                <button id="add_user_button" class="btn btn-primary" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))">
                <em class="fa fa-chevron-right"></em>
            </button>
            </div>
            <div class="separate-action">
                <button id="remove_user_button" class="btn btn-primary" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))">
                <em class="fa fa-chevron-left"></em>
                </button>
            </div>
        <?php
                        } ?>
            <div class="separate-action">
        <?php
        echo '<button id="assign_user" class="btn btn-success" type="button" value="" onclick="valide()" >'.$tool_name.'</button>';
        ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
    <?php
    if (UserManager::is_admin($user_id)) {
        echo get_lang('AssignedUsersListToPlatformAdministrator');
    } else {
        if ($user_info['status'] == SESSIONADMIN) {
            echo get_lang('AssignedUsersListToSessionsAdministrator');
        } else {
            if ($user_info['status'] == STUDENT_BOSS) {
                echo get_lang('AssignedUsersListToStudentBoss');
            } else {
                echo get_lang('AssignedUsersListToHumanResourcesManager');
            }
        }
    }
    ?>
        <div class="form-group">
            <div class="col-sm-12">
                <br>
                <select id='destination' class="form-control" name="UsersList[]" multiple="multiple" size="15" >
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
            </div>
        </div>
    </div>
</div>
</form>
<?php
Display::display_footer();
