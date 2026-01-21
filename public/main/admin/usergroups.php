<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ObjectIcon;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$usergroup = new UserGroupModel();
$usergroup->protectScript();

// -----------------------------
// Session context handling
// -----------------------------
$fromSessionId = isset($_GET['from_session']) ? (int) $_GET['from_session'] : 0;
$isFromSession = $fromSessionId > 0;

$returnTo = isset($_GET['return_to']) ? Security::remove_XSS((string) $_GET['return_to']) : '';
if ($isFromSession && empty($returnTo)) {
    // Default "Back" target when coming from a session context.
    $returnTo = api_get_path(WEB_CODE_PATH).'session/add_users_to_session.php?id_session='.$fromSessionId.'&add=true';
}

$sessionEntity = null;
if ($isFromSession) {
    $sessionEntity = api_get_session_entity($fromSessionId);
    if ($sessionEntity) {
        SessionManager::protectSession($sessionEntity);
    } else {
        // Invalid session id => fallback to normal mode
        $isFromSession = false;
        $fromSessionId = 0;
    }
}

/**
 * Fallback renderer (legacy-safe): session header + tabs.
 */
function render_session_context_header_and_tabs(int $sessionId, ?object $sessionEntity, string $returnTo, string $activeTab): void
{
    $sessionTitle = '';
    if ($sessionEntity && method_exists($sessionEntity, 'getTitle')) {
        $sessionTitle = (string) $sessionEntity->getTitle();
    }

    $headerTitle = get_lang('Subscribe users to this session');

    $baseCard = 'rounded-lg border border-gray-30 bg-white shadow-sm';
    $btnNeutral = 'inline-flex items-center gap-2 rounded-md border border-gray-30 bg-white px-3 py-1.5 text-sm font-medium text-gray-90 shadow-sm hover:bg-gray-10';
    $tabBase = 'inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium';
    $tabActive = 'bg-gray-10 text-gray-90 font-semibold';
    $tabIdle = 'text-gray-80 hover:bg-gray-10';

    $usersUrl = api_get_path(WEB_CODE_PATH).'session/add_users_to_session.php?id_session='.$sessionId.'&add=true';
    $classesUrl = api_get_path(WEB_CODE_PATH).'admin/usergroups.php?from_session='.$sessionId.'&return_to='.rawurlencode($returnTo);
    $teachersUrl = api_get_path(WEB_CODE_PATH).'session/add_teachers_to_session.php?id='.$sessionId;
    $studentsUrl = api_get_path(WEB_CODE_PATH).'session/add_students_to_session.php?id='.$sessionId;

    echo '<div class="space-y-4">';

    // Header card
    echo '<div class="'.$baseCard.' p-4">';
    echo '  <div class="flex items-start justify-between gap-3">';
    echo '    <div class="min-w-0">';
    echo '      <h2 class="text-lg font-semibold text-gray-90">'.htmlspecialchars($headerTitle, ENT_QUOTES, api_get_system_encoding()).'</h2>';
    echo '      <p class="text-sm text-gray-50">'.htmlspecialchars($sessionTitle, ENT_QUOTES, api_get_system_encoding()).'</p>';
    echo '    </div>';
    echo '    <a class="'.$btnNeutral.'" href="'.htmlspecialchars($returnTo, ENT_QUOTES, api_get_system_encoding()).'">'.get_lang('Back').'</a>';
    echo '  </div>';
    echo '</div>';

    // Tabs card
    echo '<div class="'.$baseCard.' p-3">';
    echo '  <div class="flex flex-wrap items-center gap-2">';

    $tabs = [
        'users' => [
            'url' => $usersUrl,
            'label' => get_lang('Users'),
            'icon' => ObjectIcon::USER,
        ],
        'classes' => [
            'url' => $classesUrl,
            'label' => get_lang('Enrolment by classes'),
            'icon' => ObjectIcon::MULTI_ELEMENT,
        ],
        'teachers' => [
            'url' => $teachersUrl,
            'label' => get_lang('Enroll trainers from existing sessions'),
            'icon' => ObjectIcon::TEACHER,
        ],
        'students' => [
            'url' => $studentsUrl,
            'label' => get_lang('Enroll students from existing sessions'),
            'icon' => ObjectIcon::USER,
        ],
    ];

    foreach ($tabs as $key => $tab) {
        $cls = $tabBase.' '.($key === $activeTab ? $tabActive : $tabIdle);
        echo '    <a class="'.$cls.'" href="'.htmlspecialchars($tab['url'], ENT_QUOTES, api_get_system_encoding()).'">';
        echo          Display::getMdiIcon($tab['icon'], 'ch-tool-icon', null, ICON_SIZE_SMALL, $tab['label']);
        echo '      <span>'.$tab['label'].'</span>';
        echo '    </a>';
    }

    echo '  </div>';
    echo '</div>';
}

function render_session_context_footer(): void
{
    echo '</div>'; // space-y-4
}

// Setting breadcrumbs
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
$userGroupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// jqgrid will use this URL to do the selects
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_usergroups';

//The order is important you need to check the the $column variable in the model.ajax.php file
$columns = [
    get_lang('Title'),
    get_lang('Users'),
    get_lang('Courses'),
    get_lang('Course sessions'),
    get_lang('Type'),
    get_lang('Detail'),
];

//Column config
$column_model = [
    ['name' => 'title', 'index' => 'title', 'align' => 'left', 'width' => '400'],
    ['name' => 'users', 'index' => 'users', 'align' => 'left', 'search' => 'false'],
    ['name' => 'courses', 'index' => 'courses', 'align' => 'left', 'search' => 'false'],
    ['name' => 'sessions', 'index' => 'sessions', 'align' => 'left', 'search' => 'false'],
    ['name' => 'group_type', 'index' => 'group_type', 'align' => 'center', 'search' => 'false'],
    [
        'name' => 'actions',
        'index' => 'actions',
        'width' => '250',
        'align' => 'center',
        'sortable' => 'false',
        'formatter' => 'action_formatter',
        'search' => 'false',
    ],
];

// Autowidth
$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';
$extra_params['sortname'] = 'title';
$extra_params['sortorder'] = 'desc';

// Preserve session context in links (if any)
$ctx = '';
if ($isFromSession) {
    $ctx = '&from_session='.$fromSessionId.'&return_to='.rawurlencode($returnTo);
}

// With this function we can add actions to the jgrid
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
    return \''
    .' <a href="add_users_to_usergroup.php?id=\'+options.rowId+\''.$ctx.'">'.Display::getMdiIcon(ObjectIcon::USER, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Subscribe users to class')).'</a>'
    .' <a href="add_courses_to_usergroup.php?id=\'+options.rowId+\''.$ctx.'">'.Display::getMdiIcon(ObjectIcon::COURSE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Subscribe class to courses')).'</a>'
    .' <a href="add_sessions_to_usergroup.php?id=\'+options.rowId+\''.$ctx.'">'.Display::getMdiIcon(ObjectIcon::SESSION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Subscribe class to sessions')).'</a>'
    .' <a href="?action=edit&id=\'+options.rowId+\''.$ctx.'">'.Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>'
    .' <a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("Please confirm your choice"), ENT_QUOTES))."\'".')) return false;"  href="?action=delete&id=\'+options.rowId+\''.$ctx.'">'.Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a>\';
}';

$usergroup->showGroupTypeSetting = true;
$content = '';

// Add breadcrumbs when coming from session
if ($isFromSession) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'session/session_list.php',
        'name' => get_lang('Session list'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'session/resume_session.php?id_session='.$fromSessionId,
        'name' => get_lang('Session overview'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'session/add_users_to_session.php?id_session='.$fromSessionId.'&add=true',
        'name' => get_lang('Subscribe users to this session'),
    ];
}

// Action handling
switch ($action) {
    case 'add':
        $interbreadcrumb[] = [
            'url' => 'usergroups.php'.($isFromSession ? '?from_session='.$fromSessionId.'&return_to='.rawurlencode($returnTo) : ''),
            'name' => get_lang('Classes'),
        ];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add')];

        if (0 != api_get_session_id() && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }

        $formAction = api_get_self().'?action='.$action.($isFromSession ? $ctx : '');
        $form = new FormValidator('usergroup', 'post', $formAction);
        $usergroup->setForm($form, 'add');

        $form->setDefaults(['visibility' => 2]);

        if ($form->validate()) {
            $values = $form->exportValues();
            $res = $usergroup->save($values);
            if ($res) {
                Display::addFlash(Display::return_message(get_lang('Item added'), 'confirmation'));
            } else {
                Display::addFlash(Display::return_message(
                    Security::remove_XSS($values['title']).': '.get_lang('Already exists'),
                    'warning'
                ));
            }

            header('Location: '.api_get_self().($isFromSession ? '?from_session='.$fromSessionId.'&return_to='.rawurlencode($returnTo) : ''));
            exit;
        } else {
            $backUrl = api_get_self().($isFromSession ? '?from_session='.$fromSessionId.'&return_to='.rawurlencode($returnTo) : '');
            $actions = '<a href="'.$backUrl.'">'.
                Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')).'</a>';
            $content .= Display::toolbarAction('toolbar', [$actions]);

            $token = Security::get_token();
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);

            $content .= $form->returnForm();
        }
        break;

    case 'edit':
        $interbreadcrumb[] = [
            'url' => 'usergroups.php'.($isFromSession ? '?from_session='.$fromSessionId.'&return_to='.rawurlencode($returnTo) : ''),
            'name' => get_lang('Classes'),
        ];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];

        $defaults = $usergroup->get($userGroupId);
        if (empty($defaults)) {
            api_not_allowed(true);
        }

        $usergroup->protectScript($defaults);

        $formAction = api_get_self().'?action='.$action.'&id='.$userGroupId.($isFromSession ? $ctx : '');
        $form = new FormValidator('usergroup', 'post', $formAction);

        $repo = Container::getUsergroupRepository();
        $usergroup->setForm($form, 'edit', $repo->find($userGroupId));

        $form->addElement('hidden', 'id', $userGroupId);
        $form->setDefaults($defaults);

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $res = $usergroup->update($values);

            if ($res) {
                Display::addFlash(Display::return_message(get_lang('Update successful'), 'confirmation'));
            } else {
                Display::addFlash(Display::return_message(
                    Security::remove_XSS($values['title']).': '.get_lang('Already exists'),
                    'warning'
                ));
            }

            header('Location: '.api_get_self().($isFromSession ? '?from_session='.$fromSessionId.'&return_to='.rawurlencode($returnTo) : ''));
            exit;
        } else {
            $backUrl = api_get_self().($isFromSession ? '?from_session='.$fromSessionId.'&return_to='.rawurlencode($returnTo) : '');
            $actions = '<a href="'.$backUrl.'">'.Display::getMdiIcon(
                    ActionIcon::BACK,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_MEDIUM,
                    get_lang('Back')
                ).'</a>';
            $content .= Display::toolbarAction('toolbar', [$actions]);
            $content .= $form->returnForm();
        }
        break;

    case 'delete':
        $defaults = $usergroup->get($userGroupId);
        $usergroup->protectScript($defaults);

        $res = $usergroup->delete($userGroupId);
        if ($res) {
            Display::addFlash(Display::return_message(get_lang('Deleted'), 'confirmation'));
        }

        header('Location: '.api_get_self().($isFromSession ? '?from_session='.$fromSessionId.'&return_to='.rawurlencode($returnTo) : ''));
        exit;
        break;

    default:
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Classes')];
        $content = $usergroup->returnGrid();
        break;
}

// Tool name + header
$tool_name = $isFromSession ? get_lang('Enrolment by classes') : get_lang('Classes');

Display::display_header($tool_name);

// If some legacy flow still uses session variables, keep it compatible.
if (!empty($_SESSION['usergroup_flash_message'])) {
    echo Display::return_message(
        $_SESSION['usergroup_flash_message'],
        $_SESSION['usergroup_flash_type']
    );
    unset($_SESSION['usergroup_flash_message'], $_SESSION['usergroup_flash_type']);
}

// JS grid init
?>
    <script>
        $(function() {
            <?php
            echo Display::grid_js(
                'usergroups',
                $url,
                $columns,
                $column_model,
                $extra_params,
                [],
                $action_links,
                true
            );
            ?>
            $('#usergroups').jqGrid(
                'filterToolbar',
                {stringResult: true, searchOnEnter: false, defaultSearch : "cn"}
            );
        });
    </script>
<?php

// Session wrapper rendering (prefer shared helper when available)
if ($isFromSession) {
    $sessionTitle = ($sessionEntity && method_exists($sessionEntity, 'getTitle')) ? (string) $sessionEntity->getTitle() : '';

    $usersUrl = api_get_path(WEB_CODE_PATH).'session/add_users_to_session.php?id_session='.$fromSessionId.'&add=true';
    $classesUrl = api_get_path(WEB_CODE_PATH).'admin/usergroups.php?from_session='.$fromSessionId.'&return_to='.rawurlencode($returnTo);
    $teachersUrl = api_get_path(WEB_CODE_PATH).'session/add_teachers_to_session.php?id='.$fromSessionId;
    $studentsUrl = api_get_path(WEB_CODE_PATH).'session/add_students_to_session.php?id='.$fromSessionId;

    $pageContent = '<div class="rounded-lg border border-gray-30 bg-white p-4 shadow-sm">'.$content.'</div>';

    if (method_exists('Display', 'sessionSubscriptionPage')) {
        echo Display::sessionSubscriptionPage(
            $fromSessionId,
            $sessionTitle,
            $returnTo,
            'classes',
            $pageContent,
            [
                'users_url' => $usersUrl,
                'classes_url' => $classesUrl,
                'teachers_url' => $teachersUrl,
                'students_url' => $studentsUrl,
                'header_title' => get_lang('Subscribe users to this session'),
            ]
        );
    } else {
        // Fallback: inline session header/tabs rendering (legacy-safe)
        render_session_context_header_and_tabs($fromSessionId, $sessionEntity, $returnTo, 'classes');
        echo $pageContent;
        render_session_context_footer();
    }
} else {
    echo $content;
}

Display::display_footer();
