<?php
/* For licensing terms, see /license.txt */

/**
 *  Interface for assigning sessions to Human Resources Manager.
 */
// resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// create an ajax object
$xajax = new xajax();
$xajax->registerFunction('search_sessions');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'user_list.php', 'name' => get_lang('UserList')];

// Database Table Definitions
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

// Initializing variables
$user_id = isset($_GET['user']) ? intval($_GET['user']) : null;
$user_info = api_get_user_info($user_id);
$user_anonymous = api_get_anonymous_id();
$current_user_id = api_get_user_id();
$ajax_search = false;

// Setting the name of the tool
if (UserManager::is_admin($user_id)) {
    $tool_name = get_lang('AssignSessionsToPlatformAdministrator');
} elseif ($user_info['status'] == SESSIONADMIN) {
    $tool_name = get_lang('AssignSessionsToSessionsAdministrator');
} else {
    $tool_name = get_lang('AssignSessionsToHumanResourcesManager');
}

$add_type = 'multiple';
if (isset($_GET['add_type']) && $_GET['add_type'] != '') {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

if (!api_is_platform_admin() && !api_is_session_admin()) {
    api_not_allowed(true);
}

function search_sessions($needle, $type)
{
    global $user_id;
    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
    $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
    $xajax_response = new xajaxResponse();
    $return = '';
    if (!empty($needle) && !empty($type)) {
        $needle = Database::escape_string($needle);
        $assigned_sessions_to_hrm = SessionManager::get_sessions_followed_by_drh($user_id);
        $assigned_sessions_id = array_keys($assigned_sessions_to_hrm);

        $without_assigned_sessions = '';
        if (count($assigned_sessions_id) > 0) {
            $without_assigned_sessions = " AND s.id NOT IN(".implode(',', $assigned_sessions_id).")";
        }

        if (api_is_multiple_url_enabled()) {
            $sql = " SELECT s.id, s.name FROM $tbl_session s
                     LEFT JOIN $tbl_session_rel_access_url a 
                     ON (s.id = a.session_id)
                     WHERE  
                        s.name LIKE '$needle%' $without_assigned_sessions AND 
                        access_url_id = ".api_get_current_access_url_id();
        } else {
            $sql = "SELECT s.id, s.name FROM $tbl_session s
                    WHERE  s.name LIKE '$needle%' $without_assigned_sessions ";
        }
        $rs = Database::query($sql);
        $return .= '<select class="form-control" id="origin" name="NoAssignedSessionsList[]" multiple="multiple" size="20">';
        while ($session = Database::fetch_array($rs)) {
            $return .= '<option value="'.$session['id'].'" title="'.htmlspecialchars($session['name'], ENT_QUOTES).'">'.$session['name'].'</option>';
        }
        $return .= '</select>';
        $xajax_response->addAssign(
            'ajax_list_sessions_multiple',
            'innerHTML',
            api_utf8_encode($return)
        );
    }

    return $xajax_response;
}

$xajax->processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '<script>
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
$firstLetterSession = isset($_POST['firstLetterSession']) ? $_POST['firstLetterSession'] : null;
$errorMsg = '';
$UserList = [];

if (isset($_POST['formSent']) && intval($_POST['formSent']) == 1) {
    $sessions_list = $_POST['SessionsList'];
    $userInfo = api_get_user_info($user_id);
    $affected_rows = SessionManager::subscribeSessionsToDrh(
        $userInfo,
        $sessions_list
    );
    if ($affected_rows) {
        Display::addFlash(
            Display::return_message(get_lang('AssignedSessionsHaveBeenUpdatedSuccessfully'))
        );
    }
}

// display header
Display::display_header($tool_name);

// Actions
if ($user_info['status'] != SESSIONADMIN) {
    $actionsLeft = '<a href="dashboard_add_users_to_user.php?user='.$user_id.'">'.
        Display::return_icon('add-user.png', get_lang('AssignUsers'), null, ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="dashboard_add_courses_to_user.php?user='.$user_id.'">'.
        Display::return_icon('course-add.png', get_lang('AssignCourses'), null, ICON_SIZE_MEDIUM).'</a>';

    echo Display::toolbarAction('toolbar-dashboard', [$actionsLeft]);
}

echo Display::page_header(
    sprintf(get_lang('AssignSessionsToX'), api_get_person_name($user_info['firstname'], $user_info['lastname'])),
    null,
    'h3'
);

$assigned_sessions_to_hrm = SessionManager::get_sessions_followed_by_drh($user_id);
$assigned_sessions_id = array_keys($assigned_sessions_to_hrm);

$without_assigned_sessions = '';
if (count($assigned_sessions_id) > 0) {
    $without_assigned_sessions = " AND s.id NOT IN (".implode(',', $assigned_sessions_id).") ";
}

$needle = '%';
if (!empty($firstLetterSession)) {
    $needle = Database::escape_string($firstLetterSession.'%');
}

if (api_is_multiple_url_enabled()) {
    $sql = "SELECT s.id, s.name
	        FROM $tbl_session s
            LEFT JOIN $tbl_session_rel_access_url a ON (s.id = a.session_id)
            WHERE
                s.name LIKE '$needle%' $without_assigned_sessions AND
                access_url_id = ".api_get_current_access_url_id()."
            ORDER BY s.name";
} else {
    $sql = "SELECT s.id, s.name FROM $tbl_session s
		    WHERE  s.name LIKE '$needle%' $without_assigned_sessions
            ORDER BY s.name";
}
$result = Database::query($sql);
?>
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?user=<?php echo $user_id; ?>" style="margin:0px;" <?php if ($ajax_search) {
    echo ' onsubmit="valide();"';
}?>>
    <input type="hidden" name="formSent" value="1" />

    <div class="row">
        <div class="col-md-4">
            <h5><?php echo get_lang('SessionsListInPlatform'); ?> :</h5>
            <div id="ajax_list_sessions_multiple">
                <select id="origin" name="NoAssignedSessionsList[]" multiple="multiple" size="20" style="width:340px;">
                    <?php
                    while ($enreg = Database::fetch_array($result)) {
                        ?>
                        <option value="<?php echo $enreg['id']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['name'], ENT_QUOTES).'"'; ?>>
                            <?php echo $enreg['name']; ?>
                        </option>
                    <?php
                    } ?>
                </select>
            </div>
        </div>
        <div class="col-md-4">
            <div class="code-course">
                <?php if ($add_type == 'multiple') {
                        ?>
                <p><?php echo get_lang('FirstLetterSession'); ?> :</p>
                <select class="selectpicker form-control" name="firstLetterSession" onchange = "xajax_search_sessions(this.value, 'multiple')">
                    <option value="%">--</option>
                        <?php echo Display::get_alphabet_options($firstLetterSession); ?>
                </select>
                <?php
                    } ?>
            </div>
            <div class="control-course">
            <?php
                if ($ajax_search) {
                    ?>
                    <div class="separate-action">
                        <button class="btn btn-primary" type="button" onclick="remove_item(document.getElementById('destination'))">
                            <em class="fa fa-arrow-left"></em>
                        </button>
                    </div>
                <?php
                } else {
                    ?>
                <div class="separate-action">
                    <button class="btn btn-primary" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))">
                        <em class="fa fa-arrow-right"></em>
                    </button>
                </div>
                <div class="separate-action">
                    <button class="btn btn-primary" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))">
                        <em class="fa fa-arrow-left"></em>
                    </button>
                </div>

                <?php
                }
                echo '<button class="btn btn-success" type="button" value="" onclick="valide()" >'.$tool_name.'</button>';
                ?>
            </div>
        </div>
        <div class="col-md-4">
            <h5>
                <?php
                if (UserManager::is_admin($user_id)) {
                    echo get_lang('AssignedSessionsListToPlatformAdministrator');
                } elseif ($user_info['status'] == SESSIONADMIN) {
                    echo get_lang('AssignedSessionsListToSessionsAdministrator');
                } else {
                    echo get_lang('AssignedSessionsListToHumanResourcesManager');
                }
                ?>
            :</h5>
             <select id='destination' name="SessionsList[]" multiple="multiple" size="20" style="width:320px;">
                <?php
                if (is_array($assigned_sessions_to_hrm)) {
                    foreach ($assigned_sessions_to_hrm as $enreg) {
                        ?>
                        <option value="<?php echo $enreg['id']; ?>" <?php echo 'title="'.htmlspecialchars($enreg['name'], ENT_QUOTES).'"'; ?>>
                            <?php echo $enreg['name']; ?>
                        </option>
                <?php
                    }
                }?>
            </select>
        </div>
    </div>
</form>
<?php
Display::display_footer();
