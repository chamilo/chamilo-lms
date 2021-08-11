<?php
/* For licensing terms, see /license.txt */

/**
 *   @package chamilo.admin
 */
// resetting the course id
$cidReset = true;

// including some necessary files
require_once __DIR__.'/../inc/global.inc.php';

// Setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Setting breadcrumbs
$interbreadcrumb[] = [
    'url' => 'session_list.php',
    'name' => get_lang('SessionList'),
];

// Setting the name of the tool
$tool_name = get_lang('EnrollTrainersFromExistingSessions');

$form_sent = 0;
$errorMsg = '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

SessionManager::protectSession($id);

$htmlResult = '';
if (isset($_POST['form_sent']) && $_POST['form_sent']) {
    $form_sent = $_POST['form_sent'];

    if ($form_sent == 1 &&
        isset($_POST['sessions']) && isset($_POST['courses'])
    ) {
        $sessions = $_POST['sessions'];
        $courses = $_POST['courses'];
        $htmlResult = SessionManager::copyCoachesFromSessionToCourse(
            $sessions,
            $courses
        );
    }
}

$session_list = SessionManager::get_sessions_list([], ['name']);
$sessionList = [];
foreach ($session_list as $session) {
    $sessionList[$session['id']] = Security::remove_XSS($session['name']);
}

$courseList = CourseManager::get_courses_list(0, 0, 'title');
$courseOptions = [];
foreach ($courseList as $course) {
    $courseOptions[$course['id']] = $course['title'];
}
Display::display_header($tool_name);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self().'?id='.$id; ?>">
<?php echo '<legend>'.$tool_name.' </legend>';
echo $htmlResult;
echo Display::input('hidden', 'form_sent', '1');
?>
<table border="0" cellpadding="5" cellspacing="0" width="100%">
    <tr>
        <td align="center">
            <b><?php echo get_lang('Sessions'); ?> :</b>
        </td>
        <td></td>
        <td align="center">
            <b><?php echo get_lang('Courses'); ?> :</b>
        </td>
    </tr>
    <tr>
        <td align="center">
            <?php
                echo Display::select(
                    'sessions[]',
                    $sessionList,
                    '',
                    ['style' => 'width:360px', 'multiple' => 'multiple', 'id' => 'sessions', 'size' => '15px'],
                    false
                );
            ?>
        </td>
        <td align="center">
        </td>
        <td align="center">
            <?php
            echo Display::select(
                'courses[]',
                $courseOptions,
                '',
                ['style' => 'width:360px', 'id' => 'courses', 'size' => '15px'],
                false
            );
            ?>
        </td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            <br />
            <?php
            echo '<button class="btn btn-success" type="submit">'.
                get_lang('SubscribeTeachersToSession').'</button>';
            ?>
        </td>
    </tr>
</table>
</form>
<?php
Display::display_footer();
