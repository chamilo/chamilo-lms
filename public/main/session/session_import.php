<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use ChamiloSession as Session;

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
$csvCustomError = '';
$topStaticErrorHtml = '';

// Set this option to true to enforce strict purification for usenames.
$purificationOptionForUsernames = false;
$insertedInCourse = [];
$errorMessage = '';
$warn = null;
$updatesession = null;
$userInfo = api_get_user_info();

/**
 * Normalize CSV header keys (remove BOM + trim) to match importCSV() behavior.
 */
function sessionImportNormalizeKey(string $k): string
{
    return trim(ltrim($k, "\xEF\xBB\xBF"));
}

/**
 * Parse the uploaded CSV using the same Import::csvToArray() used by importCSV().
 *
 * @return array<int, array<string, mixed>>
 */
function sessionImportReadCsvRows(string $file): array
{
    $rows = Import::csvToArray($file);
    if (empty($rows) || !is_array($rows)) {
        return [];
    }

    $normalizedRows = [];
    foreach ($rows as $r) {
        $nr = [];
        foreach ($r as $k => $v) {
            $nr[sessionImportNormalizeKey((string) $k)] = $v;
        }
        $normalizedRows[] = $nr;
    }

    return $normalizedRows;
}

/**
 * Split a pipe-separated list (e.g. "u1|u2|u3") into trimmed values.
 *
 * @return string[]
 */
function sessionImportSplitPipe(string $raw): array
{
    $raw = trim((string) $raw);
    if ('' === $raw) {
        return [];
    }

    $parts = array_map('trim', explode('|', $raw));
    $parts = array_values(array_filter($parts, 'strlen'));

    // Unique while preserving order
    $seen = [];
    $out = [];
    foreach ($parts as $p) {
        if (!isset($seen[$p])) {
            $seen[$p] = true;
            $out[] = $p;
        }
    }

    return $out;
}

/**
 * Extract course codes from the Courses column.
 * Supports "CODE" and "CODE[coach][users]" formats.
 *
 * @return string[]
 */
function sessionImportExtractCourseCodes(string $rawCourses): array
{
    $items = sessionImportSplitPipe($rawCourses);
    $codes = [];

    foreach ($items as $item) {
        // Same helper used in importCSV()
        $arr = bracketsToArray($item);
        $code = trim((string) ($arr[0] ?? ''));

        if ('' !== $code) {
            $codes[] = $code;
        }
    }

    // Unique
    $codes = array_values(array_unique($codes));

    return $codes;
}

/**
 * Post-step:
 * Ensure session users (from Users column) are also subscribed to each session course (session_rel_course_rel_user).
 * Then refresh session_rel_course.nbr_users (counts students per course-session).
 *
 * This fixes the current core bug where $userList is never populated in importCSV().
 */
function sessionImportEnsureCourseSubscriptionsFromCsv(string $csvFile, array $sessionIdList): void
{
    $tblSessionCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE); // session_rel_course
    $tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER); // session_rel_course_rel_user

    $rows = sessionImportReadCsvRows($csvFile);
    if (empty($rows) || empty($sessionIdList)) {
        return;
    }

    // Map rows to session IDs in the same order importCSV() produced them.
    $idIndex = 0;

    foreach ($rows as $row) {
        $sessionName = trim((string) ($row['SessionName'] ?? ''));

        // importCSV() skips empty session names
        if ('' === $sessionName) {
            continue;
        }

        if (!isset($sessionIdList[$idIndex])) {
            // Nothing else to map
            break;
        }

        $sessionId = (int) $sessionIdList[$idIndex];
        $idIndex++;

        if ($sessionId <= 0) {
            continue;
        }

        $usernames = sessionImportSplitPipe((string) ($row['Users'] ?? ''));
        $courseCodes = sessionImportExtractCourseCodes((string) ($row['Courses'] ?? ''));

        if (empty($usernames) || empty($courseCodes)) {
            continue;
        }

        // Resolve user IDs once per row
        $userIds = [];
        foreach ($usernames as $username) {
            $uid = UserManager::get_user_id_from_username($username);
            if (false !== $uid) {
                $userIds[] = (int) $uid;
            } else {
                error_log("[SessionImport] User '$username' not found while post-subscribing to courses (session_id=$sessionId).");
            }
        }
        $userIds = array_values(array_unique($userIds));

        if (empty($userIds)) {
            continue;
        }

        foreach ($courseCodes as $courseCode) {
            if (!CourseManager::course_exists($courseCode)) {
                error_log("[SessionImport] Course '$courseCode' not found while post-subscribing users (session_id=$sessionId).");
                continue;
            }

            $courseInfo = api_get_course_info($courseCode);
            $courseId = (int) ($courseInfo['real_id'] ?? 0);
            if ($courseId <= 0) {
                continue;
            }

            // Make sure the course is linked to the session (idempotent).
            Database::query(
                "INSERT IGNORE INTO $tblSessionCourse (c_id, session_id, position, nbr_users)
                 VALUES ($courseId, $sessionId, 0, 0)"
            );

            // Insert session-course-user for each student (idempotent).
            foreach ($userIds as $userId) {
                Database::query(
                    "INSERT IGNORE INTO $tblSessionCourseUser (user_id, session_id, c_id, status, visibility, legal_agreement, progress)
                     VALUES ($userId, $sessionId, $courseId, ".STUDENT.", 1, 0, 0)"
                );
            }

            // Refresh nbr_users in session_rel_course (count students only).
            Database::query(
                "UPDATE $tblSessionCourse sc
                 SET sc.nbr_users = (
                     SELECT COUNT(*)
                     FROM $tblSessionCourseUser scu
                     WHERE scu.session_id = $sessionId
                       AND scu.c_id = $courseId
                       AND scu.status = ".STUDENT."
                 )
                 WHERE sc.session_id = $sessionId AND sc.c_id = $courseId"
            );
        }
    }
}

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

            if ($fileType === 'csv') {
                $check = Import::assertCommaSeparated($_FILES['import_file']['tmp_name'], true);
                if (true !== $check) {
                    $csvCustomError = $check;
                    $topStaticErrorHtml = Display::return_message($csvCustomError, 'error', false);
                    $result = ['session_counter' => 0, 'session_list' => [], 'error_message' => ''];
                }
            }

            try {
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

                    // Defensive flush (no-op if nothing pending).
                    $em = Database::getManager();
                    $em->flush();
                    $em->clear();
                } elseif (empty($topStaticErrorHtml)) {
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

                    // IMPORTANT:
                    // importCSV() adds students via Doctrine but does not flush at the end of the whole import.
                    // This can make the last row appear as "not subscribed".
                    $em = Database::getManager();
                    $em->flush();
                    $em->clear();

                    // IMPORTANT (core bug workaround):
                    // importCSV() never fills $userList, so users are not subscribed to session courses.
                    // We post-process the CSV and insert into session_rel_course_rel_user (idempotent).
                    $sessionListForPost = $result['session_list'] ?? [];
                    if (!empty($sessionListForPost)) {
                        sessionImportEnsureCourseSubscriptionsFromCsv(
                            $_FILES['import_file']['tmp_name'],
                            $sessionListForPost
                        );
                    }
                }
            } catch (Throwable $e) {
                error_log('[SessionImport] Import failed: '.$e->getMessage());

                $result = ['session_counter' => 0, 'session_list' => [], 'error_message' => ''];
                $errorMessage .= 'Import failed due to an unexpected error. Please check server logs for details.';
            }

            $sessionCounter = $result['session_counter'] ?? 0;
            $sessionList = $result['session_list'] ?? [];

            if (!empty($result['error_message'])) {
                $errorMessage .= get_lang('but problems occurred') . ' :<br />' . $result['error_message'];
            }

            if (!empty($insertedInCourse) && count($insertedInCourse) > 1) {
                $warn = get_lang('Several courses were subscribed to the session because of a duplicate course code') . ': ' .
                    implode(', ', array_map(fn($code, $title) => "$title ($code)", array_keys($insertedInCourse), $insertedInCourse));
            }

            if (empty($errorMessage) && empty($topStaticErrorHtml)) {
                if ($fileType === 'csv' && $sessionCounter === 1) {
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
if (!empty($topStaticErrorHtml)) {
    echo $topStaticErrorHtml;
}
if (!empty($errorMessage)) {
    echo Display::return_message($errorMessage, 'error', false);
}

$form = new FormValidator('import_sessions', 'post', api_get_self(), null, ['enctype' => 'multipart/form-data']);
$form->addElement('hidden', 'formSent', 1);
$form->addElement('file', 'import_file', get_lang('Import file'));
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
echo Display::return_message(
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
        <pre class="whitespace-pre text-sm">
<strong>SessionName</strong>,Coach,<strong>DateStart</strong>,<strong>DateEnd</strong>,Users,Courses,VisibilityAfterExpiration,DisplayStartDate,DisplayEndDate,CoachStartDate,CoachEndDate,Classes
<strong>Example 1</strong>,username,<strong>2025/04/01</strong>,<strong>2025/04/30</strong>,username1|username2,course1[coach1][username1,...],read_only,2025/04/01,2025/04/30,2025/04/01,2025/04/30,class1
<strong>Example 2</strong>,username,<strong>2025-04-01</strong>,<strong>2025-04-30</strong>,username1|username2,course1[coach1][username1,...],accessible,2025-04-01,2025-04-30,2025-04-01,2025-04-30,class2
<strong>Example 3</strong>,username,<strong>2025/04/01 08:00:00</strong>,<strong>2025/04/30 23:59:59</strong>,username1|username2,course1[coach1][username1,...],not_accessible,2025/04/01 08:00:00,2025/04/30 23:59:59,2025/04/01 08:00:00,2025/04/30 23:59:59,class3
</pre>
    </div>
    <div id="xml-example" class="mt-6 rounded-lg border border-gray-300 bg-gray-100 p-4 overflow-auto hidden">
        <h3 class="font-bold text-lg mb-2"><?php echo get_lang('Example XML file'); ?>:</h3>
        <pre class="whitespace-pre text-sm">
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
