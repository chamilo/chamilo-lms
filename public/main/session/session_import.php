<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);
api_protect_limit_for_session_admin();

$formSent = 0;
$tblUser = Database::get_main_table(TABLE_MAIN_USER);
$tblSession = Database::get_main_table(TABLE_MAIN_SESSION);
$tblSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tblSessionCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$toolName = get_lang('Import sessions list');

$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('Session list')];

set_time_limit(0);

// Set this option to true to enforce strict purification for usenames.
$purificationOptionForUsernames = false;
$insertedInCourse = [];
$errorMessage = '';
$warn = null;
$updatesession = null;
$userInfo = api_get_user_info();

if (isset($_POST['formSent']) && $_POST['formSent']) {
    if (!empty($_FILES['import_file']['tmp_name'])) {
        $fileType = $_POST['file_type'] ?? null;

        $allowedExtensions = [
            'csv' => ['csv'],
            'xml' => ['xml'],
        ];

        $uploadedFileName = $_FILES['import_file']['name'];
        $uploadedExtension = strtolower(pathinfo($uploadedFileName, PATHINFO_EXTENSION));

        if (!in_array($uploadedExtension, $allowedExtensions[$fileType] ?? [])) {
            $errorMessage = get_lang('The uploaded file does not match the selected file type.');
        } else {
            $sendMail = !empty($_POST['sendMail']);
            $isOverwrite = !empty($_POST['overwrite']);
            $deleteUsersNotInList = !empty($_POST['delete_users_not_in_list']);

            $insertedInCourse = [];
            $errorMessage = '';

            if ($fileType === 'xml') {
                $result = SessionManager::importXML(
                    $_FILES['import_file']['tmp_name'],
                    $isOverwrite,
                    api_get_user_id(),
                    $sendMail,
                    SESSION_VISIBLE,
                    $insertedInCourse,
                    $errorMessage
                );
            } else {
                $result = SessionManager::importCSV(
                    $_FILES['import_file']['tmp_name'],
                    $isOverwrite,
                    api_get_user_id(),
                    null,
                    [],
                    null,
                    null,
                    null,
                    SESSION_VISIBLE,
                    [],
                    $deleteUsersNotInList,
                    !empty($_POST['update_course_coaches']),
                    false,
                    !empty($_POST['add_me_as_coach']),
                    false
                );
            }

            $sessionCounter = $result['session_counter'] ?? 0;
            $sessionList = $result['session_list'] ?? [];

            if (!empty($result['error_message'])) {
                $errorMessage .= get_lang('but problems occured') . ' :<br />' . $result['error_message'];
            }

            if (!empty($insertedInCourse) && count($insertedInCourse) > 1) {
                $warn = get_lang('Several courses were subscribed to the session because of a duplicate course code') . ': ' .
                    implode(', ', array_map(fn($code, $title) => "$title ($code)", array_keys($insertedInCourse), $insertedInCourse));
            }

            if (empty($errorMessage)) {
                if ($sessionCounter === 1 && $fileType === 'csv') {
                    $sessionId = current($sessionList);
                    header('Location: resume_session.php?id_session=' . $sessionId);
                    exit;
                } else {
                    header('Location: session_list.php');
                    exit;
                }
            }
        }
    } else {
        $errorMessage = get_lang('No file was sent');
    }
}

Display::display_header($toolName);
$actions = '<a href="../session/session_list.php">'.
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to').' '.get_lang('Administration')).
    '</a>';
echo Display::toolbarAction('session_import', [$actions]);

if (!empty($errorMessage)) {
    echo Display::return_message($errorMessage, 'error');
}

$form = new FormValidator('import_sessions', 'post', api_get_self(), null, ['enctype' => 'multipart/form-data']);
$form->addElement('hidden', 'formSent', 1);
$form->addElement('file', 'import_file', get_lang('Import marks in an assessment'));
$form->addElement(
    'radio',
    'file_type',
    [
        get_lang('File type'),
        Display::url(
            get_lang('Example CSV file'),
            api_get_path(WEB_CODE_PATH).'admin/example_session.csv',
            ['target' => '_blank', 'download' => null]
        ),
    ],
    'CSV',
    'csv'
);
$form->addElement(
    'radio',
    'file_type',
    [
        null,
        Display::url(
            get_lang('Example XML file'),
            api_get_path(WEB_CODE_PATH).'admin/example_session.xml',
            ['target' => '_blank', 'download' => null]
        ),
    ],
    'XML',
    'xml'
);

$form->addElement('checkbox', 'overwrite', null, get_lang('If a session exists, update it'));
$form->addElement(
    'checkbox',
    'delete_users_not_in_list',
    null,
    get_lang('Unsubscribe students which are not in the imported list')
);
$form->addElement('checkbox', 'update_course_coaches', null, get_lang('Clean and update course coaches'));
$form->addElement('checkbox', 'add_me_as_coach', null, get_lang('Add me as coach'));
$form->addElement('checkbox', 'sendMail', null, get_lang('Send a mail to users'));
$form->addButtonImport(get_lang('Import session(s)'));

$defaults = ['sendMail' => 'true', 'file_type' => 'csv'];

$options = api_get_setting('session.session_import_settings', true);
if (!empty($options) && isset($options['options'])) {
    if (isset($options['options']['session_exists_default_option'])) {
        $defaults['overwrite'] = $options['options']['session_exists_default_option'];
    }
    if (isset($options['options']['send_mail_default_option'])) {
        $defaults['sendMail'] = $options['options']['send_mail_default_option'];
    }
}

$form->setDefaults($defaults);
Display::return_message(
    get_lang(
        'The XML import lets you add more info and create resources (courses, users). The CSV import will only create sessions and let you assign existing resources to them.'
    )
);
$form->display();
echo '<script>
document.addEventListener("DOMContentLoaded", function () {
    const csvExample = document.getElementById("csv-example");
    const xmlExample = document.getElementById("xml-example");

    function toggleExamples(fileType) {
        if (fileType === "csv") {
            csvExample.classList.remove("hidden");
            xmlExample.classList.add("hidden");
        } else if (fileType === "xml") {
            csvExample.classList.add("hidden");
            xmlExample.classList.remove("hidden");
        }
    }

    const radios = document.querySelectorAll("input[name=\'file_type\']");
    radios.forEach(radio => {
        radio.addEventListener("change", function () {
            toggleExamples(this.value.toLowerCase());
        });

        if (radio.checked) {
            toggleExamples(radio.value.toLowerCase());
        }
    });
});
</script>';
?>
    <div id="csv-example" class="mt-6 rounded-lg border border-gray-300 bg-gray-100 p-4 overflow-auto">
        <h3 class="font-bold text-lg mb-2"><?php echo get_lang('Example CSV file'); ?>:</h3>
        <pre class="whitespace-nowrap text-sm">
<strong>SessionName</strong>;Coach;<strong>DateStart</strong>;<strong>DateEnd</strong>;Users;Courses;VisibilityAfterExpiration;DisplayStartDate;DisplayEndDate;CoachStartDate;CoachEndDate;Classes
<strong>Example 1</strong>;username;<strong>2025/04/01</strong>;<strong>2025/04/30</strong>;username1|username2;course1[coach1][username1,...];read_only;2025/04/01;2025/04/30;2025/04/01;2025/04/30;class1
<strong>Example 2</strong>;username;<strong>2025-04-01</strong>;<strong>2025-04-30</strong>;username1|username2;course1[coach1][username1,...];accessible;2025-04-01;2025-04-30;2025-04-01;2025-04-30;class2
<strong>Example 3</strong>;username;<strong>2025/04/01 08:00:00</strong>;<strong>2025/04/30 23:59:59</strong>;username1|username2;course1[coach1][username1,...];not_accessible;2025/04/01 08:00:00;2025/04/30 23:59:59;2025/04/01 08:00:00;2025/04/30 23:59:59;class3
    </pre>
    </div>
    <div id="xml-example" class="mt-6 rounded-lg border border-gray-300 bg-gray-100 p-4 overflow-auto hidden">
        <h3 class="font-bold text-lg mb-2"><?php echo get_lang('Example XML file'); ?>:</h3>
        <pre class="whitespace-nowrap text-sm">
&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;Sessions&gt;
    &lt;Session&gt;
        &lt;SessionName&gt;<strong>Example 1</strong>&lt;/SessionName&gt;
        &lt;Coach&gt;username&lt;/Coach&gt;
        &lt;DateStart&gt;<strong>2025/04/01</strong>&lt;/DateStart&gt;
        &lt;DateEnd&gt;<strong>2025/04/30</strong>&lt;/DateEnd&gt;
        &lt;User&gt;username1&lt;/User&gt;
        &lt;Course&gt;
            &lt;CourseCode&gt;coursecode1&lt;/CourseCode&gt;
            &lt;Coach&gt;coach1&lt;/Coach&gt;
            &lt;User&gt;username2&lt;/User&gt;
        &lt;/Course&gt;
    &lt;/Session&gt;

    &lt;Session&gt;
        &lt;SessionName&gt;<strong>Example 2</strong>&lt;/SessionName&gt;
        &lt;Coach&gt;username&lt;/Coach&gt;
        &lt;DateStart&gt;<strong>2025-04-01</strong>&lt;/DateStart&gt;
        &lt;DateEnd&gt;<strong>2025-04-30</strong>&lt;/DateEnd&gt;
        &lt;User&gt;username1&lt;/User&gt;
        &lt;Course&gt;
            &lt;CourseCode&gt;coursecode2&lt;/CourseCode&gt;
            &lt;Coach&gt;coach2&lt;/Coach&gt;
            &lt;User&gt;username2&lt;/User&gt;
        &lt;/Course&gt;
    &lt;/Session&gt;

    &lt;Session&gt;
        &lt;SessionName&gt;<strong>Example 3</strong>&lt;/SessionName&gt;
        &lt;Coach&gt;username&lt;/Coach&gt;
        &lt;DateStart&gt;<strong>2025/04/01 08:00:00</strong>&lt;/DateStart&gt;
        &lt;DateEnd&gt;<strong>2025/04/30 23:59:59</strong>&lt;/DateEnd&gt;
        &lt;User&gt;username1&lt;/User&gt;
        &lt;Course&gt;
            &lt;CourseCode&gt;coursecode3&lt;/CourseCode&gt;
            &lt;Coach&gt;coach3&lt;/Coach&gt;
            &lt;User&gt;username2&lt;/User&gt;
        &lt;/Course&gt;
    &lt;/Session&gt;
&lt;/Sessions&gt;
    </pre>
    </div>
<?php

Display::display_footer();
