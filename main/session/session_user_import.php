<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.admin
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

set_time_limit(0);

$this_section = SECTION_PLATFORM_ADMIN;

$session_id = isset($_GET['id_session']) ? intval($_GET['id_session']) : null;
SessionManager::protectSession($session_id);

$form_sent = 0;
$tool_name = get_lang('ImportUsers');

//$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = ['url' => "session_list.php", "name" => get_lang('SessionList')];
$interbreadcrumb[] = [
    'url' => "resume_session.php?id_session=".$session_id,
    "name" => get_lang('SessionOverview'),
];

if (isset($_POST['formSent']) && $_POST['formSent']) {
    if (isset($_FILES['import_file']['tmp_name']) &&
        !empty($_FILES['import_file']['tmp_name'])
    ) {
        $form_sent = $_POST['formSent'];

        // CSV
        $users = Import::csvToArray($_FILES['import_file']['tmp_name']);
        $user_list = [];
        foreach ($users as $user_data) {
            $username = $user_data['username'];
            $user_id = UserManager::get_user_id_from_username($username);
            if ($user_id) {
                $user_list[] = $user_id;
            }
        }

        if (!empty($user_list)) {
            SessionManager::subscribeUsersToSession(
                $session_id,
                $user_list,
                null,
                false
            );

            foreach ($user_list as &$user_id) {
                $user_info = api_get_user_info($user_id);
                $user_id = $user_info['complete_name'];
            }
            $error_message = get_lang('UsersAdded').' : '.implode(', ', $user_list);
        }
    } else {
        $error_message = get_lang('NoInputFile');
    }
}

// Display the header.
Display::display_header($tool_name);

echo '<div class="actions">';
echo '<a href="resume_session.php?id_session='.$session_id.'">'.
    Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'), '', ICON_SIZE_MEDIUM).
    '</a>';
echo '</div>';

if (!empty($error_message)) {
    echo Display::return_message($error_message, 'normal', false);
}

$form = new FormValidator(
    'import_sessions',
    'post',
    api_get_self().'?id_session='.$session_id,
    null,
    ['enctype' => 'multipart/form-data']
);
$form->addElement('hidden', 'formSent', 1);
$form->addElement('file', 'import_file', get_lang('ImportCSVFileLocation'));
$form->addButtonImport(get_lang('Import'));

$form->display();

?>
<p><?php echo get_lang('CSVMustLookLike'); ?> :</p>
<blockquote>
<pre>
username;
admin;
teacher;
jmontoya;
</pre>
</blockquote>
<?php
Display::display_footer();
