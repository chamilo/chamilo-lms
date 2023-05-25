<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This class provides methods for the notebook management.
 * Include/require it in your code to use its features.
 *
 * @author Carlos Vargas <litox84@gmail.com>, move code of main/notebook up here
 * @author Jose Angel Ruiz <desarrollo@nosolored.com>, adaptation for the plugin
 * @author Julio Montoya
 *
 * @package chamilo.library
 */
class NotebookTeacher
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * a little bit of javascript to display a prettier warning when deleting a note.
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
     *
     * @version januari 2009, dokeos 1.8.6
     *
     * @return string
     */
    public static function javascriptNotebook()
    {
        return "<script>
				function confirmation (name)
				{
					if (confirm(\" ".get_lang("NoteConfirmDelete")." \"+ name + \" ?\"))
						{return true;}
					else
						{return false;}
				}
				</script>";
    }

    /**
     * This functions stores the note in the database.
     *
     * @param array $values
     * @param int   $userId    Optional. The user ID
     * @param int   $courseId  Optional. The course ID
     * @param int   $sessionId Optional. The session ID
     *
     * @return bool
     */
    public static function saveNote($values, $userId = 0, $courseId = 0, $sessionId = 0)
    {
        if (!is_array($values) || empty($values['note_title'])) {
            return false;
        }

        // Database table definition
        $table = Database::get_main_table(NotebookTeacherPlugin::TABLE_NOTEBOOKTEACHER);
        $userId = $userId ?: api_get_user_id();
        $courseId = $courseId ?: api_get_course_int_id();
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];
        $sessionId = $sessionId ?: api_get_session_id();
        $now = api_get_utc_datetime();
        $params = [
            'c_id' => $courseId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'student_id' => intval($values['student_id']),
            'course' => $courseCode,
            'title' => $values['note_title'],
            'description' => $values['note_comment'],
            'creation_date' => $now,
            'update_date' => $now,
            'status' => 0,
        ];
        $id = Database::insert($table, $params);

        if ($id > 0) {
            return $id;
        }
    }

    /**
     * @param int $notebookId
     *
     * @return array|mixed
     */
    public static function getNoteInformation($notebookId)
    {
        if (empty($notebookId)) {
            return [];
        }

        // Database table definition
        $tableNotebook = Database::get_main_table(NotebookTeacherPlugin::TABLE_NOTEBOOKTEACHER);
        $courseId = api_get_course_int_id();

        $sql = "SELECT
                id AS notebook_id,
                title AS note_title,
                description AS note_comment,
                session_id AS session_id,
                student_id AS student_id
               FROM $tableNotebook
               WHERE c_id = $courseId AND id = '".intval($notebookId)."' ";
        $result = Database::query($sql);
        if (Database::num_rows($result) != 1) {
            return [];
        }

        return Database::fetch_array($result);
    }

    /**
     * This functions updates the note in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    public static function updateNote($values)
    {
        if (!is_array($values) or empty($values['note_title'])) {
            return false;
        }

        // Database table definition
        $table = Database::get_main_table(NotebookTeacherPlugin::TABLE_NOTEBOOKTEACHER);

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $params = [
            'user_id' => api_get_user_id(),
            'student_id' => intval($values['student_id']),
            'course' => api_get_course_id(),
            'session_id' => $sessionId,
            'title' => $values['note_title'],
            'description' => $values['note_comment'],
            'update_date' => api_get_utc_datetime(),
        ];

        Database::update(
            $table,
            $params,
            [
                'c_id = ? AND id = ?' => [
                    $courseId,
                    $values['notebook_id'],
                ],
            ]
        );

        return true;
    }

    /**
     * @param int $notebookId
     *
     * @return bool
     */
    public static function deleteNote($notebookId)
    {
        if (empty($notebookId) || $notebookId != strval(intval($notebookId))) {
            return false;
        }

        // Database table definition
        $tableNotebook = Database::get_main_table(NotebookTeacherPlugin::TABLE_NOTEBOOKTEACHER);
        $courseId = api_get_course_int_id();

        $sql = "DELETE FROM $tableNotebook
                WHERE
                    c_id = $courseId AND
                    id = '".intval($notebookId)."' AND
                    user_id = '".api_get_user_id()."'";
        $result = Database::query($sql);

        if (Database::affected_rows($result) != 1) {
            return false;
        }

        return true;
    }

    /**
     * Display notes.
     */
    public static function displayNotes()
    {
        $plugin = NotebookTeacherPlugin::create();
        $userInfo = api_get_user_info();
        $sortDirection = 'DESC';
        $linkSortDirection = 'ASC';

        if (!isset($_GET['direction'])) {
            $sortDirection = 'ASC';
            $linkSortDirection = 'DESC';
        } elseif ($_GET['direction'] == 'ASC') {
            $sortDirection = 'ASC';
            $linkSortDirection = 'DESC';
        }

        $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
        $currentUrl = api_get_self().'?'.api_get_cidreq().'&student_id='.$studentId;
        $sessionId = api_get_session_id();
        $courseCode = api_get_course_id();
        $courseId = api_get_course_int_id();

        if (empty($sessionId)) {
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                0,
                null,
                null,
                STUDENT
            );
        } else {
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                null,
                null,
                0
            );
        }

        $form = new FormValidator('search_student');

        // Status
        $students = [];
        $students[] = $plugin->get_lang('AllStudent');
        foreach ($userList as $key => $userItem) {
            $students[$key] = api_get_person_name($userItem['firstname'], $userItem['lastname']);
        }

        $form->addElement(
            'select',
            'student_filter',
            $plugin->get_lang('StudentFilter'),
            $students,
            [
                'id' => 'student_filter',
                'onchange' => 'javascript: studentFilter();',
            ]
        );
        $user_data = ['student_filter' => $studentId];
        $form->setDefaults($user_data);

        $selectStudent = $form->returnForm();

        // action links
        echo '<div class="actions">';
        if (!api_is_drh()) {
            if (!api_is_anonymous()) {
                if (empty($sessionId)) {
                    echo '<a href="'.$currentUrl.'&action=addnote">'.
                        Display::return_icon(
                            'new_note.png',
                            get_lang('NoteAddNew'),
                            '',
                            '32'
                        ).'</a>';
                } elseif (api_is_allowed_to_session_edit(false, true)) {
                    echo '<a href="'.$currentUrl.'&action=addnote">'.
                        Display::return_icon('new_note.png', get_lang('NoteAddNew'), '', '32').'</a>';
                }
            } else {
                echo '<a href="javascript:void(0)">'.
                    Display::return_icon('new_note.png', get_lang('NoteAddNew'), '', '32').'</a>';
            }
        }

        echo '<a href="'.$currentUrl.
                '&action=changeview&view=creation_date&direction='.$linkSortDirection.'">'.
            Display::return_icon('notes_order_by_date_new.png', get_lang('OrderByCreationDate'), '', '32').'</a>';
        echo '<a href="'.$currentUrl.
                '&action=changeview&view=update_date&direction='.$linkSortDirection.'">'.
            Display::return_icon('notes_order_by_date_mod.png', get_lang('OrderByModificationDate'), '', '32').'</a>';
        echo '<a href="'.$currentUrl.
                '&action=changeview&view=title&direction='.$linkSortDirection.'">'.
            Display::return_icon('notes_order_by_title.png', get_lang('OrderByTitle'), '', '32').'</a>';

        echo '</div>';
        echo '<div class="row">'.$selectStudent.'</div>';
        $view = Session::read('notebook_view');
        if (!isset($view) ||
            !in_array($view, ['creation_date', 'update_date', 'title'])
        ) {
            Session::write('notebook_view', 'creation_date');
        }

        $view = Session::read('notebook_view');
        // Database table definition
        $tableNotebook = Database::get_main_table(NotebookTeacherPlugin::TABLE_NOTEBOOKTEACHER);
        if ($view == 'creation_date' || $view == 'update_date') {
            $orderBy = " ORDER BY `$view` $sortDirection ";
        } else {
            $orderBy = " ORDER BY `$view` $sortDirection ";
        }

        // condition for the session
        $conditionSession = api_get_session_condition($sessionId);
        $condExtra = $view == 'update_date' ? " AND update_date <> ''" : " ";

        if ($studentId > 0) {
            // Only one student
            $conditionStudent = " AND student_id = $studentId";

            $sql = "SELECT * FROM $tableNotebook
                    WHERE
                    c_id = $courseId
                    $conditionSession
                    $conditionStudent
                    $condExtra $orderBy
                    ";
            $first = true;
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                while ($row = Database::fetch_array($result)) {
                    if ($first) {
                        $studentText = '';
                        if ($row['student_id'] > 0) {
                            $studentInfo = api_get_user_info($row['student_id']);
                            $studentText = $studentInfo['complete_name_with_username'];
                        }
                        echo Display::page_subheader2($studentText);
                        $first = false;
                    }

                    // Validation when belongs to a session
                    $sessionImg = api_get_session_image($row['session_id'], $userInfo['status']);
                    $updateValue = '';
                    if ($row['update_date'] != $row['creation_date']) {
                        $updateValue = ', '.get_lang('UpdateDate').': '.
                                        Display::dateToStringAgoAndLongDate($row['update_date']);
                    }
                    $userInfo = api_get_user_info($row['user_id']);
                    $author = ', '.get_lang('Teacher').': '.$userInfo['complete_name'];
                    $actions = '';
                    if (intval($row['user_id']) == api_get_user_id()) {
                        $actions = '<a href="'.
                                api_get_self().'?'.
                                api_get_cidreq().'&student_id='.$studentId.'&action=editnote&notebook_id='.$row['id'].'">'.
                                Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>';
                        $actions .= '<a href="'.
                                api_get_self().
                                '?action=deletenote&student_id='.$studentId.'&notebook_id='.$row['id'].
                                '" onclick="return confirmation(\''.$row['title'].'\');">'.
                                Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                    }
                    echo Display::panel(
                        $row['description'],
                        $row['title'].$sessionImg.' <div class="pull-right">'.$actions.'</div>',
                        get_lang('CreationDate').': '.
                        Display::dateToStringAgoAndLongDate($row['creation_date']).$updateValue.$author
                    );
                }
            } else {
                echo Display::return_message($plugin->get_lang('NoNotebookUser'), 'warning');
            }
        } else {
            // All students
            foreach ($userList as $key => $userItem) {
                $studentId = $key;
                $studentText = $userItem['firstname'].' '.$userItem['lastname'];
                $conditionStudent = " AND student_id = $studentId";

                $sql = "SELECT * FROM $tableNotebook
                        WHERE
                        c_id = $courseId
                        $conditionSession
                        $conditionStudent
                        $condExtra $orderBy
                ";

                $result = Database::query($sql);
                if (Database::num_rows($result) > 0) {
                    echo Display::page_subheader($studentText);
                    while ($row = Database::fetch_array($result)) {
                        // Validation when belongs to a session
                        $sessionImg = api_get_session_image($row['session_id'], $userInfo['status']);
                        $updateValue = '';

                        if ($row['update_date'] != $row['creation_date']) {
                            $updateValue = ', '.get_lang('UpdateDate').': '.
                            Display::dateToStringAgoAndLongDate($row['update_date']);
                        }

                        $userInfo = api_get_user_info($row['user_id']);
                        $author = ', '.get_lang('Teacher').': '.$userInfo['complete_name'];

                        $actions = '';
                        if (intval($row['user_id']) == api_get_user_id()) {
                            $actions = '<a href="'.api_get_self().
                                '?action=editnote&notebook_id='.$row['id'].'&'.api_get_cidreq().'">'.
                                    Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>';
                            $actions .= '<a href="'.api_get_self().
                                    '?action=deletenote&notebook_id='.$row['id'].
                                    '" onclick="return confirmation(\''.$row['title'].'\');">'.
                                    Display::return_icon(
                                        'delete.png',
                                        get_lang('Delete'),
                                        '',
                                        ICON_SIZE_SMALL
                                    ).'</a>';
                        }

                        echo Display::panel(
                            $row['description'],
                            $row['title'].$sessionImg.' <div class="pull-right">'.$actions.'</div>',
                            get_lang('CreationDate').': '.
                            Display::dateToStringAgoAndLongDate($row['creation_date']).$updateValue.$author
                        );
                    }
                }
            }

            $conditionStudent = " AND student_id = 0";

            $sql = "SELECT * FROM $tableNotebook
                    WHERE
                    c_id = $courseId
                    $conditionSession
                    $conditionStudent
                    $condExtra $orderBy
            ";

            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                echo Display::page_subheader($plugin->get_lang('NotebookNoStudentAssigned'));
                while ($row = Database::fetch_array($result)) {
                    // Validation when belongs to a session
                    $sessionImg = api_get_session_image($row['session_id'], $userInfo['status']);
                    $updateValue = '';

                    if ($row['update_date'] != $row['creation_date']) {
                        $updateValue = ', '.get_lang('UpdateDate').': '.
                        Display::dateToStringAgoAndLongDate($row['update_date']);
                    }

                    $userInfo = api_get_user_info($row['user_id']);
                    $author = ', '.get_lang('Teacher').': '.$userInfo['complete_name'];
                    $actions = '';
                    if (intval($row['user_id']) == api_get_user_id()) {
                        $actions = '<a href="'.api_get_self().
                                '?action=editnote&notebook_id='.$row['id'].'&'.api_get_cidreq().'">'.
                                Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>';
                        $actions .= '<a href="'.api_get_self().
                                '?action=deletenote&notebook_id='.$row['id'].
                                '" onclick="return confirmation(\''.$row['title'].'\');">'.
                                Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
                    }

                    echo Display::panel(
                        $row['description'],
                        $row['title'].$sessionImg.' <div class="pull-right">'.$actions.'</div>',
                        get_lang('CreationDate').': '.
                        Display::dateToStringAgoAndLongDate($row['creation_date']).$updateValue.$author
                    );
                }
            }
        }
    }

    /**
     * @param FormValidator $form
     * @param int           $studentId
     *
     * @return FormValidator
     */
    public static function getForm($form, $studentId)
    {
        $sessionId = api_get_session_id();
        $courseCode = api_get_course_id();
        if (empty($sessionId)) {
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                0,
                null,
                null,
                STUDENT
            );
        } else {
            $userList = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId,
                null,
                null,
                0
            );
        }

        $students = ['' => ''];
        foreach ($userList as $key => $userItem) {
            $students[$key] = api_get_person_name($userItem['firstname'], $userItem['lastname']);
        }

        $form->addElement(
            'select',
            'student_id',
            get_lang('Student'),
            $students
        );

        $form->addElement('text', 'note_title', get_lang('NoteTitle'), ['id' => 'note_title']);
        $form->applyFilter('text', 'html_filter');
        $form->addElement(
            'html_editor',
            'note_comment',
            get_lang('NoteComment'),
            null,
            api_is_allowed_to_edit()
                ? ['ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300']
                : ['ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student']
        );

        $form->addButtonCreate(get_lang('Save'), 'SubmitNote');

        // Setting the rules
        $form->addRule('note_title', get_lang('ThisFieldIsRequired'), 'required');

        return $form;
    }
}
