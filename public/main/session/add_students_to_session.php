<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ObjectIcon;

// Resetting the course id
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// setting breadcrumbs
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('Session list'),
];

// Setting the name of the tool
$tool_name = get_lang('Subscribe students to session(s)');
$add_type = 'multiple';
if (isset($_REQUEST['add_type']) && '' != $_REQUEST['add_type']) {
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}
$form_sent = 0;
$errorMsg = '';
$users = $sessions = [];

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$session = api_get_session_entity($id);
SessionManager::protectSession($session);

// Extra breadcrumbs for consistency with session flow
$interbreadcrumb[] = [
    'url' => 'resume_session.php?id_session='.$id,
    'name' => get_lang('Session overview'),
];
$interbreadcrumb[] = [
    'url' => api_get_self().'?id='.$id,
    'name' => $tool_name,
];

// Process form
$htmlResult = '';
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = (int) $_POST['form_sent'];

    if (1 === $form_sent) {
        $sessionSourceList = $_POST['sessions'] ?? [];
        $sessionDestinationList = $_POST['sessions_destination'] ?? [];

        // Copy students from source sessions to destination sessions
        $result = SessionManager::copyStudentsFromSession($sessionSourceList, $sessionDestinationList);

        if (is_array($result)) {
            foreach ($result as $message) {
                $htmlResult .= $message;
            }
        }
    }
}

// Build options
$session_list = SessionManager::get_sessions_list([], ['title']);
$sessionList = [];
foreach ($session_list as $row) {
    $sessionList[(int) $row['id']] = $row['title'];
}

Display::display_header($tool_name);

// Tabs URLs
$urlUsers = api_get_path(WEB_CODE_PATH).'session/add_users_to_session.php?id_session='.$id.'&add=true';
$urlByClasses = api_get_path(WEB_CODE_PATH).'admin/usergroups.php?from_session='.$id.'&return_to='.rawurlencode($urlUsers);
$urlFromTeachers = api_get_path(WEB_CODE_PATH).'session/add_teachers_to_session.php?id='.$id;
$urlFromStudents = api_get_path(WEB_CODE_PATH).'session/add_students_to_session.php?id='.$id;

$sessionTitle = $session && method_exists($session, 'getTitle') ? (string) $session->getTitle() : '';
$backUrl = api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$id;

// Page wrapper
echo '<div class="mx-auto w-full p-4 space-y-4">';

// Header card
echo '  <div class="rounded-lg border border-gray-30 bg-white p-4 shadow-sm">';
echo '    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">';
echo '      <div class="min-w-0">';
echo '        <h1 class="text-lg font-semibold text-gray-90">'.htmlspecialchars($tool_name, ENT_QUOTES, api_get_system_encoding()).'</h1>';
echo '        <p class="text-sm text-gray-50">'.htmlspecialchars($sessionTitle, ENT_QUOTES, api_get_system_encoding()).'</p>';
echo '      </div>';
echo '      <div class="flex items-center gap-2">';
echo '        <a href="'.$backUrl.'" class="inline-flex items-center gap-2 rounded-md border border-gray-30 bg-white px-3 py-1.5 text-sm font-medium text-gray-90 shadow-sm hover:bg-gray-10">';
echo            get_lang('Back');
echo '        </a>';
echo '      </div>';
echo '    </div>';
echo '  </div>';

// Tabs (options)
echo '  <div class="rounded-lg border border-gray-30 bg-white shadow-sm">';
echo '    <div class="flex flex-wrap items-center gap-2 border-b border-gray-20 px-3 py-2">';

echo '      <a href="'.$urlUsers.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-10">';
echo            Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Users'));
echo '        <span>'.get_lang('Users').'</span>';
echo '      </a>';

echo '      <a href="'.$urlByClasses.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-10">';
echo            Display::getMdiIcon(ObjectIcon::MULTI_ELEMENT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enrolment by classes'));
echo '        <span>'.get_lang('Enrolment by classes').'</span>';
echo '      </a>';

echo '      <a href="'.$urlFromTeachers.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-90 hover:bg-gray-10">';
echo            Display::getMdiIcon(ObjectIcon::TEACHER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enroll trainers from existing sessions'));
echo '        <span>'.get_lang('Enroll trainers from existing sessions').'</span>';
echo '      </a>';

// Active tab: students
echo '      <a href="'.$urlFromStudents.'" class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold bg-gray-10 text-gray-90">';
echo            Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Enroll students from existing sessions'));
echo '        <span>'.get_lang('Enroll students from existing sessions').'</span>';
echo '      </a>';

echo '    </div>';

// Content
echo '    <div class="p-4 space-y-4">';

if (!empty($htmlResult)) {
    echo '      <div class="rounded-md border border-gray-20 bg-white p-3">';
    echo            $htmlResult;
    echo '      </div>';
}

echo '      <div class="rounded-md border border-gray-20 bg-gray-10 p-3 text-sm text-gray-70">';
echo '        <span class="font-semibold text-gray-90">'.get_lang('Tip').':</span> ';
echo          get_lang('Select one or more source sessions and one or more destination sessions to copy students').'.';
echo '      </div>';

echo '      <form name="formulaire" method="post" action="'.api_get_self().'?id='.$id.'" class="space-y-4">';
echo            Display::input('hidden', 'form_sent', '1');

echo '        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">';
echo '          <div class="space-y-2">';
echo '            <label for="sessions" class="block text-sm font-medium text-gray-90">'.get_lang('Course sessions').'</label>';
echo              Display::select(
    'sessions[]',
    $sessionList,
    '',
    [
        'id' => 'sessions',
        'multiple' => 'multiple',
        'size' => '14',
        'class' => 'w-full rounded-md border border-gray-30 bg-white p-2 text-sm text-gray-90 focus:outline-none focus:ring-1 focus:ring-primary',
    ],
    false
);
echo '            <p class="text-xs text-gray-60">'.get_lang('Hold Ctrl (or Cmd) to select multiple items').'.</p>';
echo '          </div>';

echo '          <div class="space-y-2">';
echo '            <label for="sessions_destination" class="block text-sm font-medium text-gray-90">'.get_lang('Course sessions').'</label>';
echo              Display::select(
    'sessions_destination[]',
    $sessionList,
    '',
    [
        'id' => 'sessions_destination',
        'multiple' => 'multiple',
        'size' => '14',
        'class' => 'w-full rounded-md border border-gray-30 bg-white p-2 text-sm text-gray-90 focus:outline-none focus:ring-1 focus:ring-primary',
    ],
    false
);
echo '            <p class="text-xs text-gray-60">'.get_lang('Select destination sessions').'.</p>';
echo '          </div>';
echo '        </div>';

echo '        <div class="pt-2">';
echo '          <button type="submit" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90">';
echo              get_lang('Subscribe students to session(s)');
echo '          </button>';
echo '        </div>';

echo '      </form>';

echo '    </div>';
echo '  </div>';

echo '</div>';
?>
    <script>
        function moveItem(origin , destination) {
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
            for (i = 0; i < options.length; i++) {
                newOptions[i] = options[i];
            }

            newOptions = newOptions.sort(mysort);
            options.length = 0;
            for (i = 0; i < newOptions.length; i++) {
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
            var options = document.getElementById('session_in_promotion').options;
            for (i = 0; i < options.length; i++) {
                options[i].selected = true;
            }
            document.forms.formulaire.submit();
        }

        function loadUsersInSelect(select) {
            var xhr_object = null;
            if (window.XMLHttpRequest) { // Firefox
                xhr_object = new XMLHttpRequest();
            } else if (window.ActiveXObject) { // Internet Explorer
                xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
            } else {
                alert("Your browser does not support XMLHTTPRequest objects...");
            }

            xhr_object.open("POST", "loadUsersInSelect.ajax.php");
            xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            nosessionUsers = makepost(document.getElementById('session_not_in_promotion'));
            sessionUsers = makepost(document.getElementById('session_in_promotion'));
            nosessionClasses = makepost(document.getElementById('origin_classes'));
            sessionClasses = makepost(document.getElementById('destination_classes'));
            xhr_object.send("nosessionusers=" + nosessionUsers + "&sessionusers=" + sessionUsers + "&nosessionclasses=" + nosessionClasses + "&sessionclasses=" + sessionClasses);

            xhr_object.onreadystatechange = function() {
                if (xhr_object.readyState == 4) {
                    document.getElementById('content_source').innerHTML = result = xhr_object.responseText;
                }
            }
        }

        function makepost(select) {
            var options = select.options;
            var ret = "";
            for (i = 0; i < options.length; i++) {
                ret = ret + options[i].value + '::' + options[i].text + ";;";
            }
            return ret;
        }
    </script>
<?php
Display::display_footer();
