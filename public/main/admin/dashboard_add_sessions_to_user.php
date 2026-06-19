<?php
/* For licensing terms, see /license.txt */

/**
 *  Interface for assigning sessions to Human Resources Manager.
 */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Enums\ObjectIcon;

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
$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => '/admin/user-list', 'name' => get_lang('User list')];

// Database Table Definitions
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

// Initializing variables
$user_id = isset($_GET['user']) ? (int) ($_GET['user']) : null;

$user_anonymous = api_get_anonymous_id();
$current_user_id = api_get_user_id();
$user = api_get_user_entity($user_id);
$isSessionAdmin = api_is_session_admin($user);
$ajax_search = false;

// Setting the name of the tool
if (UserManager::is_admin($user_id)) {
    $tool_name = get_lang('Assign sessions to platform administrator');
} elseif ($isSessionAdmin) {
    $tool_name = get_lang('Assign sessions to sessions administrator');
} else {
    $tool_name = get_lang('Assign sessions to Human Resources manager');
}

$add_type = 'multiple';
if (isset($_GET['add_type']) && '' != $_GET['add_type']) {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

if (!api_is_platform_admin() && !api_is_session_admin()) {
    api_not_allowed(true);
}

function getDashboardAssignedSessionIdsForUser(int $userId): array
{
    $tblSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $tblSessionRelAccessUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

    if (api_is_multiple_url_enabled()) {
        $sql = "SELECT DISTINCT sru.session_id
                FROM $tblSessionRelUser sru
                INNER JOIN $tblSessionRelAccessUrl a ON (a.session_id = sru.session_id)
                WHERE
                    sru.user_id = $userId AND
                    sru.relation_type = ".Session::DRH." AND
                    a.access_url_id = ".api_get_current_access_url_id();
    } else {
        $sql = "SELECT DISTINCT session_id
                FROM $tblSessionRelUser
                WHERE
                    user_id = $userId AND
                    relation_type = ".Session::DRH;
    }

    $result = Database::query($sql);
    $sessionIds = [];

    while ($row = Database::fetch_assoc($result)) {
        $sessionIds[] = (int) $row['session_id'];
    }

    return $sessionIds;
}

function getDashboardAssignedSessionsForUser(int $userId): array
{
    $tblSession = Database::get_main_table(TABLE_MAIN_SESSION);
    $assignedSessionIds = getDashboardAssignedSessionIdsForUser($userId);

    if (empty($assignedSessionIds)) {
        return [];
    }

    $assignedSessionIds = array_map('intval', $assignedSessionIds);
    $sql = "SELECT id, title AS name
            FROM $tblSession
            WHERE id IN (".implode(',', $assignedSessionIds).")
            ORDER BY title";

    $result = Database::query($sql);
    $sessions = [];

    while ($row = Database::fetch_assoc($result)) {
        $sessions[(int) $row['id']] = $row;
    }

    return $sessions;
}

function saveDashboardAssignedSessionsForUser(int $userId, array $sessionIds): void
{
    $tblSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $tblSessionRelAccessUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

    $sessionIds = array_values(
        array_unique(
            array_filter(
                array_map('intval', $sessionIds),
                function ($sessionId) {
                    return $sessionId > 0;
                }
            )
        )
    );

    if (api_is_multiple_url_enabled()) {
        $assignedSessionIds = getDashboardAssignedSessionIdsForUser($userId);

        if (!empty($assignedSessionIds)) {
            $assignedSessionIds = array_map('intval', $assignedSessionIds);
            $sql = "DELETE FROM $tblSessionRelUser
                    WHERE
                        user_id = $userId AND
                        relation_type = ".Session::DRH." AND
                        session_id IN (".implode(',', $assignedSessionIds).")";
            Database::query($sql);
        }
    } else {
        $sql = "DELETE FROM $tblSessionRelUser
                WHERE
                    user_id = $userId AND
                    relation_type = ".Session::DRH;
        Database::query($sql);
    }

    foreach ($sessionIds as $sessionId) {
        $sql = "INSERT IGNORE INTO $tblSessionRelUser
                (session_id, user_id, relation_type, duration, registered_at)
                VALUES
                ($sessionId, $userId, ".Session::DRH.", 0, UTC_TIMESTAMP())";
        Database::query($sql);
    }
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
        $assigned_sessions_id = getDashboardAssignedSessionIdsForUser((int) $user_id);

        $without_assigned_sessions = '';
        if (count($assigned_sessions_id) > 0) {
            $without_assigned_sessions = ' AND s.id NOT IN('.implode(',', $assigned_sessions_id).')';
        }

        if (api_is_multiple_url_enabled()) {
            $sql = " SELECT s.id, s.title AS name FROM $tbl_session s
                     LEFT JOIN $tbl_session_rel_access_url a
                     ON (s.id = a.session_id)
                     WHERE
                        s.title LIKE '$needle%' $without_assigned_sessions AND
                        access_url_id = ".api_get_current_access_url_id();
        } else {
            $sql = "SELECT s.id, s.title AS name FROM $tbl_session s
                    WHERE  s.title LIKE '$needle%' $without_assigned_sessions ";
        }
        $rs = Database::query($sql);
        $return .= '<select class="form-control h-96 w-full min-w-0 rounded-xl border-gray-25 text-body-2 text-gray-90" id="origin" name="NoAssignedSessionsList[]" multiple="multiple" size="15">';
        while ($session = Database :: fetch_array($rs)) {
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
$firstLetterSession = isset($_POST['firstLetterSession']) ? Security::remove_XSS($_POST['firstLetterSession']) : null;
$errorMsg = '';
$UserList = [];

if (isset($_POST['formSent']) && 1 == (int) ($_POST['formSent']) && isset($_POST['assignmentChanged']) && '1' === (string) $_POST['assignmentChanged']) {
    if (array_key_exists('assignedItems', $_POST)) {
        $assignedItems = (string) Security::remove_XSS($_POST['assignedItems']);
        $sessions_list = '' === $assignedItems ? [] : array_filter(
            array_map('intval', explode(',', $assignedItems)),
            function ($sessionId) {
                return $sessionId > 0;
            }
        );
    } else {
        $sessions_list = isset($_POST['SessionsList']) ? array_map('intval', Security::remove_XSS($_POST['SessionsList'])) : [];
    }

    saveDashboardAssignedSessionsForUser((int) $user_id, $sessions_list);

    Display::addFlash(
        Display::return_message(get_lang('The assigned sessions have been updated'))
    );

    api_location(api_get_self().'?user='.$user_id);
}

// display header
Display::display_header($tool_name);

// Actions
if (!$isSessionAdmin) {
    $actionsLeft = '<a href="dashboard_add_users_to_user.php?user='.$user_id.'">'.
        Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assign users')).'</a>';
    $actionsLeft .= '<a href="dashboard_add_courses_to_user.php?user='.$user_id.'">'.
        Display::getMdiIcon(ObjectIcon::COURSE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Assign courses')).'</a>';

    echo Display::toolbarAction('toolbar-dashboard', [$actionsLeft]);
}

echo Display::page_header(
    sprintf(get_lang('Assign sessions to %s'), UserManager::formatUserFullName($user)),
    null,
    'h3'
);

$assigned_sessions_to_hrm = getDashboardAssignedSessionsForUser((int) $user_id);
$assigned_sessions_id = array_keys($assigned_sessions_to_hrm);

$without_assigned_sessions = '';
if (count($assigned_sessions_id) > 0) {
    $without_assigned_sessions = ' AND s.id NOT IN ('.implode(',', $assigned_sessions_id).') ';
}

$needle = '%';
if (!empty($firstLetterSession)) {
    $needle = Database::escape_string($firstLetterSession.'%');
}

if (api_is_multiple_url_enabled()) {
    $sql = "SELECT s.id, s.title AS name
	        FROM $tbl_session s
            LEFT JOIN $tbl_session_rel_access_url a ON (s.id = a.session_id)
            WHERE
                s.title LIKE '$needle%' $without_assigned_sessions AND
                access_url_id = ".api_get_current_access_url_id().'
            ORDER BY s.title';
} else {
    $sql = "SELECT s.id, s.title AS name FROM $tbl_session s
		    WHERE  s.title LIKE '$needle%' $without_assigned_sessions
            ORDER BY s.title";
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
            <section class="min-w-0 h-full rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
                <label for="origin" class="mb-3 block text-body-2 font-semibold text-gray-90">
                    <?php echo get_lang('List of sessions on the platform'); ?>
                </label>

                <div id="ajax_list_sessions_multiple">
                    <select id="origin" ondblclick="moveItem(document.getElementById(&quot;origin&quot;), document.getElementById(&quot;destination&quot;))" class="form-control h-96 w-full min-w-0 rounded-xl border-gray-25 text-body-2 text-gray-90" name="NoAssignedSessionsList[]" multiple="multiple" size="15">
                        <?php
                        while ($enreg = Database::fetch_array($result)) {
                            $sessionTitle = $enreg['name'] ?? $enreg['title'] ?? '';
                            ?>
                            <option value="<?php echo $enreg['id']; ?>" <?php echo 'title="'.htmlspecialchars($sessionTitle, ENT_QUOTES).'"'; ?>>
                                <?php echo $sessionTitle; ?>
                            </option>
                        <?php
                        } ?>
                    </select>
                </div>
            </section>
        </div>

        <div class="col-12 col-md-2 col-xl-1">
            <section class="min-w-0 h-full rounded-2xl border border-gray-20 bg-support-2 p-4 shadow-sm">
                <div class="h-full min-h-96 flex flex-col justify-center gap-3">
                    <?php if ('multiple' == $add_type) { ?>
                        <div class="mb-3">
                            <label for="firstLetterSession" class="mb-2 block text-body-2 font-semibold text-gray-90">
                                <?php echo get_lang('Session title\'s first letter'); ?>
                            </label>
                            <select id="firstLetterSession" class="selectpicker form-control w-full rounded-xl border-gray-25 text-body-2 text-gray-90" name="firstLetterSession" onchange="xajax_search_sessions(this.value, 'multiple')">
                                <option value="%">--</option>
                                <?php echo Display::get_alphabet_options($firstLetterSession); ?>
                            </select>
                        </div>
                    <?php } ?>

                    <div class="flex flex-col items-center justify-center gap-3">
                    <?php
                    if ($ajax_search) {
                        ?>
                        <button class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center border-0 bg-secondary text-secondary-button-text hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary" type="button" onclick="remove_item(document.getElementById('destination'))" title="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="mdi mdi-arrow-left-bold text-white text-lg" aria-hidden="true"></span>
                            <span class="sr-only"><?php echo get_lang('Remove'); ?></span>
                        </button>
                    <?php
                    } else {
                        ?>
                        <button class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center border-0 bg-secondary text-secondary-button-text hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary" type="button" onclick="moveItem(document.getElementById('origin'), document.getElementById('destination'))" title="<?php echo htmlspecialchars(get_lang('Add'), ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars(get_lang('Add'), ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="mdi mdi-arrow-right-bold text-white text-lg" aria-hidden="true"></span>
                            <span class="sr-only"><?php echo get_lang('Add'); ?></span>
                        </button>

                        <button class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center border-0 bg-secondary text-secondary-button-text hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-secondary" type="button" onclick="moveItem(document.getElementById('destination'), document.getElementById('origin'))" title="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars(get_lang('Remove'), ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="mdi mdi-arrow-left-bold text-white text-lg" aria-hidden="true"></span>
                            <span class="sr-only"><?php echo get_lang('Remove'); ?></span>
                        </button>
                    <?php
                    }
                    ?>

                        <button id="assign_user" class="inline-flex h-12 w-12 min-w-12 min-h-12 items-center justify-center rounded-xl p-0 text-center disabled:cursor-not-allowed disabled:opacity-50 border-0 bg-success text-success-button-text hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-success" type="button" onclick="valide()" disabled="disabled" title="<?php echo htmlspecialchars($tool_name, ENT_QUOTES); ?>" aria-label="<?php echo htmlspecialchars($tool_name, ENT_QUOTES); ?>" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="mdi mdi-content-save-outline text-white text-lg" aria-hidden="true"></span>
                            <span class="sr-only"><?php echo $tool_name; ?></span>
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-md-5 col-xl-6">
            <section class="min-w-0 h-full rounded-2xl border border-gray-20 bg-white p-4 shadow-sm">
                <label for="destination" class="mb-3 block text-body-2 font-semibold text-gray-90">
                    <?php
                    if (UserManager::is_admin($user_id)) {
                        echo get_lang('Assigned sessions list to platform administrator');
                    } elseif ($isSessionAdmin) {
                        echo get_lang('Assigned sessions list to sessions administrator');
                    } else {
                        echo get_lang('List of sessions assigned to the Human Resources manager');
                    }
                    ?>
                </label>

                <select id="destination" ondblclick="moveItem(document.getElementById(&quot;destination&quot;), document.getElementById(&quot;origin&quot;))" class="form-control h-96 w-full min-w-0 rounded-xl border-gray-25 text-body-2 text-gray-90" name="SessionsList[]" multiple="multiple" size="15">
                    <?php
                    if (is_array($assigned_sessions_to_hrm)) {
                        foreach ($assigned_sessions_to_hrm as $enreg) {
                            $sessionTitle = $enreg['name'] ?? $enreg['title'] ?? '';
                            ?>
                            <option value="<?php echo $enreg['id']; ?>" <?php echo 'title="'.htmlspecialchars($sessionTitle, ENT_QUOTES).'"'; ?>>
                                <?php echo $sessionTitle; ?>
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
