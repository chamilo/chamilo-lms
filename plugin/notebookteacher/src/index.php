<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$course_plugin = 'notebookteacher';
require_once __DIR__.'/../config.php';

$_setting['student_view_enabled'] = 'false';

$plugin = NotebookTeacherPlugin::create();
$current_course_tool = $plugin->get_lang('NotebookTeacher');
$enable = $plugin->get('enable_plugin_notebookteacher') == 'true';

if ($enable) {
    if (api_is_teacher() || api_is_drh()) {
        // The section (tabs)
        $this_section = SECTION_COURSES;

        // Notice for unauthorized people.
        api_protect_course_script(true);
        $location = 'index.php?'.api_get_cidreq();

        // Additional javascript
        $htmlHeadXtra[] = NotebookTeacher::javascriptNotebook();
        $htmlHeadXtra[] = '<script>
        function setFocus(){
            $("#note_title").focus();
        }
        $(document).ready(function () {
            setFocus();
        });

        function filter_student() {
            var student_id = $("#student_filter").val();
            location.href ="'.$location.'&student_id="+student_id;
        }
        </script>';

        // Setting the tool constants
        $tool = $plugin->get_lang('NotebookTeacher');

        // Tracking
        Event::event_access_tool('notebookteacher');

        $action = isset($_GET['action']) ? $_GET['action'] : '';

        // Tool name
        if ($action === 'addnote') {
            $tool = 'NoteAddNew';
            $interbreadcrumb[] = [
                'url' => 'index.php?'.api_get_cidreq(),
                'name' => $plugin->get_lang('NotebookTeacher'),
            ];
        }
        if ($action === 'editnote') {
            $tool = 'ModifyNote';
            $interbreadcrumb[] = [
                'url' => 'index.php?'.api_get_cidreq(),
                'name' => $plugin->get_lang('NotebookTeacher'),
            ];
        }

        // Displaying the header
        Display::display_header(get_lang(ucfirst($tool)));

        // Tool introduction
        Display::display_introduction_section($plugin->get_lang('NotebookTeacher'));

        // Action handling: Adding a note
        if ($action === 'addnote') {
            if ((api_get_session_id() != 0 &&
                !api_is_allowed_to_session_edit(false, true) || api_is_drh())) {
                api_not_allowed();
            }
            Session::write('notebook_view', 'creation_date');

            $form = new FormValidator(
                'note',
                'post',
                api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&'.api_get_cidreq()
            );
            // Setting the form elements
            $form->addElement('header', '', get_lang('NoteAddNew'));
            $form->addElement('text', 'note_title', get_lang('NoteTitle'), ['id' => 'note_title']);

            $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
            $sessionId = api_get_session_id();
            $courseCode = api_get_course_id();
            $active = isset($_GET['active']) ? $_GET['active'] : null;
            $status = STUDENT;
            $course_info = api_get_course_info();
            $courseId = $course_info['real_id'];
            $current_access_url_id = api_get_current_access_url_id();
            $sort_by_first_name = api_sort_by_first_name();

            if (!empty($sessionId)) {
                $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
                $table_users = Database::get_main_table(TABLE_MAIN_USER);
                $sql = "SELECT DISTINCT
                            user.user_id, ".($is_western_name_order
                                    ? "user.firstname, user.lastname"
                                    : "user.lastname, user.firstname")."
                        FROM $table_session_course_user as session_course_user,
                $table_users as user ";
                if (api_is_multiple_url_enabled()) {
                    $sql .= ' , '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).' au ';
                }
                $sql .= " WHERE c_id = '$courseId' AND session_course_user.user_id = user.user_id ";
                $sql .= ' AND session_id = '.$sessionId;

                if (api_is_multiple_url_enabled()) {
                    $sql .= " AND user.user_id = au.user_id AND access_url_id =  $current_access_url_id  ";
                }

                // only users no coaches/teachers
                if ($type == COURSEMANAGER) {
                    $sql .= " AND session_course_user.status = 2 ";
                } else {
                    $sql .= " AND session_course_user.status = 0 ";
                }
                $sql .= $sort_by_first_name
                        ? ' ORDER BY user.firstname, user.lastname'
                        : ' ORDER BY user.lastname, user.firstname';

                $rs = Database::query($sql);

                $a_course_users = [];
                while ($row = Database::fetch_assoc($rs)) {
                    $a_course_users[$row['user_id']] = $row;
                }
            } else {
                $a_course_users = CourseManager::get_user_list_from_course_code(
                    $courseCode,
                    0,
                    null,
                    null,
                    $status,
                    null,
                    false,
                    false,
                    null,
                    null,
                    null,
                    $active
                );
            }

            $studentList = [];
            $studentList[0] = '';
            foreach ($a_course_users as $key => $user_item) {
                $studentList[$key] = $user_item['firstname'].' '.$user_item['lastname'];
            }

            $form->addElement(
                'select',
                'student_id',
                get_lang('Student'),
                $studentList,
                [
                    'id' => 'student_id',
                ]
            );

            $form->addElement(
                'html_editor',
                'note_comment',
                get_lang('NoteComment'),
                null,
                api_is_allowed_to_edit()
                ? ['ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300']
                : ['ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student']
            );
            $form->addButtonCreate(get_lang('AddNote'), 'SubmitNote');

            // Setting the rules
            $form->addRule('note_title', get_lang('ThisFieldIsRequired'), 'required');

            // The validation or display
            if ($form->validate()) {
                $check = Security::check_token('post');
                if ($check) {
                    $values = $form->exportValues();
                    $res = NotebookTeacher::saveNote($values);
                    if ($res) {
                        echo Display::return_message(get_lang('NoteAdded'), 'confirmation');
                    }
                }
                Security::clear_token();
                NotebookTeacher::displayNotes();
            } else {
                echo '<div class="actions">';
                echo '<a href="index.php">'.
                        Display::return_icon('back.png', get_lang('BackToNotesList'), '', ICON_SIZE_MEDIUM).
                    '</a>';
                echo '</div>';
                $token = Security::get_token();
                $form->addElement('hidden', 'sec_token');
                $form->setConstants(['sec_token' => $token]);
                $form->display();
            }
        } elseif ($action === 'editnote' && is_numeric($_GET['notebook_id'])) {
            // Action handling: Editing a note
            if (!empty($_GET['isStudentView']) || api_is_drh()) {
                NotebookTeacher::displayNotes();
                exit;
            }

            // Initialize the object
            $form = new FormValidator(
                'note',
                'post',
                api_get_self().'?action='.Security::remove_XSS($_GET['action']).
                    '&notebook_id='.intval($_GET['notebook_id']).'&'.api_get_cidreq()
            );
            // Setting the form elements
            $form->addElement('header', '', get_lang('ModifyNote'));
            $form->addElement('hidden', 'notebook_id');
            $form->addElement('text', 'note_title', get_lang('NoteTitle'), ['size' => '100']);

            $sessionId = api_get_session_id();
            $courseCode = api_get_course_id();
            $active = isset($_GET['active']) ? $_GET['active'] : null;
            $status = STUDENT;
            $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;
            $course_info = api_get_course_info();
            $courseId = $course_info['real_id'];
            $current_access_url_id = api_get_current_access_url_id();
            $sort_by_first_name = api_sort_by_first_name();

            if (!empty($sessionId)) {
                $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
                $table_users = Database::get_main_table(TABLE_MAIN_USER);
                $sql = "SELECT DISTINCT
                        user.user_id, ".($is_western_name_order
                                ? "user.firstname, user.lastname"
                                : "user.lastname, user.firstname")."
                        FROM $table_session_course_user as session_course_user,
                $table_users as user ";
                if (api_is_multiple_url_enabled()) {
                    $sql .= ' , '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).' au ';
                }
                $sql .= " WHERE c_id = '$courseId' AND session_course_user.user_id = user.user_id ";
                $sql .= ' AND session_id = '.$sessionId;

                if (api_is_multiple_url_enabled()) {
                    $sql .= " AND user.user_id = au.user_id AND access_url_id =  $current_access_url_id  ";
                }

                // only users no coaches/teachers
                if ($type == COURSEMANAGER) {
                    $sql .= " AND session_course_user.status = 2 ";
                } else {
                    $sql .= " AND session_course_user.status = 0 ";
                }
                $sql .= $sort_by_first_name
                        ? ' ORDER BY user.firstname, user.lastname'
                        : ' ORDER BY user.lastname, user.firstname';

                $rs = Database::query($sql);

                $a_course_users = [];
                while ($row = Database::fetch_assoc($rs)) {
                    $a_course_users[$row['user_id']] = $row;
                }
            } else {
                $a_course_users = CourseManager::get_user_list_from_course_code(
                    $courseCode,
                    0,
                    null,
                    null,
                    $status,
                    null,
                    false,
                    false,
                    null,
                    null,
                    null,
                    $active
                );
            }

            $studentList = [];
            $studentList[0] = '';
            foreach ($a_course_users as $key => $user_item) {
                $studentList[$key] = $user_item['firstname'].' '.$user_item['lastname'];
            }

            $form->addElement(
                'select',
                'student_id',
                get_lang('Student'),
                $studentList,
                [
                    'id' => 'student_id',
                ]
            );

            $form->addElement(
                'html_editor',
                'note_comment',
                get_lang('NoteComment'),
                null,
                api_is_allowed_to_edit()
                ? ['ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300']
                : ['ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student']
            );
            $form->addButtonUpdate(get_lang('ModifyNote'), 'SubmitNote');

            // Setting the defaults
            $defaults = NotebookTeacher::getNoteInformation(Security::remove_XSS($_GET['notebook_id']));
            $form->setDefaults($defaults);

            // Setting the rules
            $form->addRule('note_title', get_lang('ThisFieldIsRequired'), 'required');

            // The validation or display
            if ($form->validate()) {
                $check = Security::check_token('post');
                if ($check) {
                    $values = $form->exportValues();
                    $res = NotebookTeacher::updateNote($values);
                    if ($res) {
                        echo Display::return_message(get_lang('NoteUpdated'), 'confirmation');
                    }
                }
                Security::clear_token();
                NotebookTeacher::displayNotes();
            } else {
                echo '<div class="actions">';
                echo '<a href="index.php">'.
                    Display::return_icon('back.png', get_lang('BackToNotesList'), '', ICON_SIZE_MEDIUM).'</a>';
                echo '</div>';
                $token = Security::get_token();
                $form->addElement('hidden', 'sec_token');
                $form->setConstants(['sec_token' => $token]);
                $form->display();
            }
        } elseif ($action === 'deletenote' && is_numeric($_GET['notebook_id'])) {
            // Action handling: deleting a note
            $res = NotebookTeacher::deleteNote($_GET['notebook_id']);
            if ($res) {
                echo Display::return_message(get_lang('NoteDeleted'), 'confirmation');
            }

            NotebookTeacher::displayNotes();
        } elseif ($action === 'changeview' && in_array($_GET['view'], ['creation_date', 'update_date', 'title'])) {
            // Action handling: changing the view (sorting order)
            switch ($_GET['view']) {
                case 'creation_date':
                    if (!$_GET['direction'] || $_GET['direction'] == 'ASC') {
                        echo Display::return_message(get_lang('NotesSortedByCreationDateAsc'), 'confirmation');
                    } else {
                        echo Display::return_message(get_lang('NotesSortedByCreationDateDESC'), 'confirmation');
                    }
                    break;
                case 'update_date':
                    if (!$_GET['direction'] || $_GET['direction'] == 'ASC') {
                        echo Display::return_message(get_lang('NotesSortedByUpdateDateAsc'), 'confirmation');
                    } else {
                        echo Display::return_message(get_lang('NotesSortedByUpdateDateDESC'), 'confirmation');
                    }
                    break;
                case 'title':
                    if (!$_GET['direction'] || $_GET['direction'] == 'ASC') {
                        echo Display::return_message(get_lang('NotesSortedByTitleAsc'), 'confirmation');
                    } else {
                        echo Display::return_message(get_lang('NotesSortedByTitleDESC'), 'confirmation');
                    }
                    break;
            }
            Session::write('notebook_view', Security::remove_XSS($_GET['view']));
            NotebookTeacher::displayNotes();
        } else {
            NotebookTeacher::displayNotes();
        }

        Display::display_footer();
    } else {
        $session = api_get_session_entity(api_get_session_id());
        $_course = api_get_course_info();
        $web_course_path = api_get_path(WEB_COURSE_PATH);
        $url = $web_course_path.$_course['path'].'/index.php'.($session ? '?id_session='.$session->getId() : '');

        Display::addFlash(
            Display::return_message($plugin->get_lang('ToolForTeacher'))
        );

        header('Location: '.$url);
        exit;
    }
} else {
    echo $plugin->get_lang('ToolDisabled');
}
