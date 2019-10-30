<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.admin
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

$formSent = 0;
$errorMsg = '';

// Database Table Definitions
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$archivePath = api_get_path(SYS_ARCHIVE_PATH);
$archiveURL = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive_path=&archive=';

$tool_name = get_lang('Export sessions list');
$interbreadcrumb[] = ['url' => 'session_list.php', 'name' => get_lang('Session list')];
set_time_limit(0);
if (isset($_POST['formSent'])) {
    $formSent = $_POST['formSent'];
    $file_type = isset($_POST['file_type']) ? $_POST['file_type'] : 'csv';
    $session_id = $_POST['session_id'];
    if (empty($session_id)) {
        $sql = "SELECT
                    s.id,
                    name,
                    id_coach,
                    username,
                    access_start_date,
                    access_end_date,
                    visibility,
                    session_category_id
                FROM $tbl_session s
                INNER JOIN $tbl_user
                ON $tbl_user.user_id = s.id_coach
                ORDER BY id";

        if (api_is_multiple_url_enabled()) {
            $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql = "SELECT s.id, name,id_coach,username,access_start_date,access_end_date,visibility,session_category_id
                    FROM $tbl_session s
                    INNER JOIN $tbl_session_rel_access_url as session_rel_url
                    ON (s.id= session_rel_url.session_id)
                    INNER JOIN $tbl_user u ON (u.user_id = s.id_coach)
                    WHERE access_url_id = $access_url_id
                    ORDER BY id";
            }
        }

        $result = Database::query($sql);
    } else {
        $sql = "SELECT s.id,name,username,access_start_date,access_end_date,visibility,session_category_id
                FROM $tbl_session s
                INNER JOIN $tbl_user
                    ON $tbl_user.user_id = s.id_coach
                WHERE s.id='$session_id'";
        $result = Database::query($sql);
    }

    if (Database::num_rows($result)) {
        $sessionListToExport = [];
        if (in_array($file_type, ['csv', 'xls'])) {
            $archiveFile = 'export_sessions_'.$session_id.'_'.api_get_local_time();
            $cvs = true;
            $sessionListToExport[] = [
                'SessionName',
                'Coach',
                'DateStart',
                'DateEnd',
                'Visibility',
                'SessionCategory',
                'Users',
                'Courses',
            ];
        } else {
            if (!file_exists($archivePath)) {
                mkdir($archivePath, api_get_permissions_for_new_directories(), true);
            }

            if (!file_exists($archivePath.'index.html')) {
                $fp = fopen($archivePath.'index.html', 'w');
                fputs($fp, '<html><head></head><body></body></html>');
                fclose($fp);
            }

            $archiveFile = 'export_sessions_'.$session_id.'_'.api_get_local_time().'.'.$file_type;
            while (file_exists($archivePath.$archiveFile)) {
                $archiveFile = 'export_users_'.$session_id.'_'.api_get_local_time().'_'.uniqid('').'.'.$file_type;
            }

            $cvs = false;
            $fp = fopen($archivePath.$archiveFile, 'w');
            fputs($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Sessions>\n");
        }

        while ($row = Database::fetch_array($result)) {
            $row['name'] = str_replace(';', ',', $row['name']);
            $row['username'] = str_replace(';', ',', $row['username']);
            $row['access_start_date'] = str_replace(';', ',', $row['access_start_date']);
            $row['access_end_date'] = str_replace(';', ',', $row['access_end_date']);
            $row['visibility'] = str_replace(';', ',', $row['visibility']);
            $row['session_category'] = str_replace(';', ',', $row['session_category_id']);
            // users
            $sql = "SELECT DISTINCT $tbl_user.username
                    FROM $tbl_user
                    INNER JOIN $tbl_session_user
                    ON
                        $tbl_user.user_id = $tbl_session_user.user_id AND
                        $tbl_session_user.relation_type<>".SESSION_RELATION_TYPE_RRHH." AND
                        $tbl_session_user.session_id = '".$row['id']."'";

            $rsUsers = Database::query($sql);
            $users = '';
            while ($rowUsers = Database::fetch_array($rsUsers)) {
                if ($cvs) {
                    $users .= str_replace(';', ',', $rowUsers['username']).'|';
                } else {
                    $users .= "\t\t<User>$rowUsers[username]</User>\n";
                }
            }

            if (!empty($users) && $cvs) {
                $users = api_substr($users, 0, api_strlen($users) - 1);
            }

            // Courses
            $sql = "SELECT DISTINCT c.code, sc.id, c_id
                    FROM $tbl_course c
                    INNER JOIN $tbl_session_course_user sc
                    ON c.id = sc.c_id AND sc.session_id = '".$row['id']."'";

            $rsCourses = Database::query($sql);

            $courses = '';
            while ($rowCourses = Database::fetch_array($rsCourses)) {
                // get coachs from a course
                $sql = "SELECT u.username
                        FROM $tbl_session_course_user scu
                        INNER JOIN $tbl_user u
                        ON u.user_id = scu.user_id
                        WHERE
                            scu.c_id = '{$rowCourses['c_id']}' AND
                            scu.session_id = '".$row['id']."' AND
                            scu.status = 2 ";

                $rs_coachs = Database::query($sql);
                $coachs = [];
                while ($row_coachs = Database::fetch_array($rs_coachs)) {
                    $coachs[] = $row_coachs['username'];
                }

                $coachs = implode(",", $coachs);

                if ($cvs) {
                    $courses .= str_replace(';', ',', $rowCourses['code']);
                    $courses .= '['.str_replace(';', ',', $coachs).'][';
                } else {
                    $courses .= "\t\t<Course>\n";
                    $courses .= "\t\t\t<CourseCode>$rowCourses[code]</CourseCode>\n";
                    $courses .= "\t\t\t<Coach>$coachs</Coach>\n";
                }

                // rel user courses
                $sql = "SELECT DISTINCT u.username
                        FROM $tbl_session_course_user scu
                        INNER JOIN $tbl_session_user su
                        ON
                            scu.user_id = su.user_id AND
                            scu.session_id = su.session_id AND
                            su.relation_type<>".SESSION_RELATION_TYPE_RRHH."
                        INNER JOIN $tbl_user u
                        ON
                            scu.user_id = u.user_id AND
                            scu.c_id='".$rowCourses['c_id']."' AND
                            scu.session_id='".$row['id']."'";

                $rsUsersCourse = Database::query($sql);
                $userscourse = '';
                while ($rowUsersCourse = Database::fetch_array($rsUsersCourse)) {
                    if ($cvs) {
                        $userscourse .= str_replace(';', ',', $rowUsersCourse['username']).',';
                    } else {
                        $courses .= "\t\t\t<User>$rowUsersCourse[username]</User>\n";
                    }
                }

                if ($cvs) {
                    if (!empty($userscourse)) {
                        $userscourse = api_substr(
                            $userscourse,
                            0,
                            api_strlen($userscourse) - 1
                        );
                    }

                    $courses .= $userscourse.']|';
                } else {
                    $courses .= "\t\t</Course>\n";
                }
            }

            if (!empty($courses) && $cvs) {
                $courses = api_substr($courses, 0, api_strlen($courses) - 1);
            }
            $add = $courses;

            if (in_array($file_type, ['csv', 'xls'])) {
                $sessionListToExport[] = [
                    $row['name'],
                    $row['username'],
                    $row['access_start_date'],
                    $row['access_end_date'],
                    $row['visibility'],
                    $row['session_category'],
                    $users,
                    $courses,
                ];
            } else {
                $add = "\t<Session>\n"
                         ."\t\t<SessionName>$row[name]</SessionName>\n"
                         ."\t\t<Coach>$row[username]</Coach>\n"
                         ."\t\t<DateStart>$row[access_start_date]</DateStart>\n"
                         ."\t\t<DateEnd>$row[access_end_date]</DateEnd>\n"
                         ."\t\t<Visibility>$row[visibility]</Visibility>\n"
                         ."\t\t<SessionCategory>$row[session_category]</SessionCategory>\n";
            }

            if (!$cvs) {
                $add .= "\t</Session>\n";
                fputs($fp, $add);
            }
        }

        switch ($file_type) {
            case 'xml':
                fputs($fp, "</Sessions>\n");
                fclose($fp);
                $errorMsg = get_lang('The users list has been exported.').'<br/>
                <a class="btn btn-default" href="'.$archiveURL.$archiveFile.'">'.get_lang('Download the file').'</a>';
                break;
            case 'csv':
                Export::arrayToCsv($sessionListToExport, $archiveFile);
                exit;
            case 'xls':
                Export::arrayToXls($sessionListToExport, $archiveFile);
                exit;
                break;
        }
    }
}

// display the header
Display::display_header($tool_name);

//select of sessions
$sql = "SELECT id, name FROM $tbl_session ORDER BY name";

if (api_is_multiple_url_enabled()) {
    $tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $sql = "SELECT s.id, name FROM $tbl_session s
                INNER JOIN $tbl_session_rel_access_url as session_rel_url
                ON (s.id = session_rel_url.session_id)
                WHERE access_url_id = $access_url_id
                ORDER BY name";
    }
}
$result = Database::query($sql);
$Sessions = Database::store_result($result);

echo '<div class="actions">';
echo '<a href="../session/session_list.php">'.
        Display::return_icon('back.png', get_lang('Back to').' '.get_lang('Session list'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

if (!empty($errorMsg)) {
    echo Display::return_message($errorMsg, 'normal', false); //main API
}

$form = new FormValidator('session_export', 'post', api_get_self());
$form->addElement('hidden', 'formSent', 1);
$form->addElement('radio', 'file_type', get_lang('Output file type'), 'CSV', 'csv', null);
$form->addElement('radio', 'file_type', '', 'XLS', 'xls', null);
$form->addElement('radio', 'file_type', null, 'XML', 'xml', null, ['id' => 'file_type_xml']);

$options = [];
$options['0'] = get_lang('All the sessions');
foreach ($Sessions as $enreg) {
    $options[$enreg['id']] = $enreg['name'];
}

$form->addElement('select', 'session_id', get_lang('Choose the session to export'), $options);
$form->addButtonExport(get_lang('Export session(s)'));

$defaults = [];
$defaults['file_type'] = 'csv';
$form->setDefaults($defaults);
$form->display();
unset($courses);
Display::display_footer();
