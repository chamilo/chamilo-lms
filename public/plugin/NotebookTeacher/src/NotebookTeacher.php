<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use ChamiloSession as Session;

/**
 * This class provides methods for the notebook management.
 * Include/require it in your code to use its features.
 *
 * @author Carlos Vargas <litox84@gmail.com>, move code of main/notebook up here
 * @author Jose Angel Ruiz <desarrollo@nosolored.com>, adaptation for the plugin
 * @author Julio Montoya
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
        return '<script>
				function confirmation (name)
				{
					if (confirm(" '.get_lang('Are you sure you want to delete this note').' "+ name + " ?"))
						{return true;}
					else
						{return false;}
				}
				</script>';
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
            'student_id' => (int) ($values['student_id']),
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
               WHERE
                    c_id = $courseId AND
                    id = '".(int) $notebookId."' AND
                    user_id = '".api_get_user_id()."' ";
        $result = Database::query($sql);
        if (1 != Database::num_rows($result)) {
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
            'student_id' => (int) ($values['student_id']),
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
                'c_id = ? AND id = ? AND user_id = ?' => [
                    $courseId,
                    $values['notebook_id'],
                    api_get_user_id(),
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
        if (empty($notebookId) || $notebookId != (string) ((int) $notebookId)) {
            return false;
        }

        // Database table definition
        $tableNotebook = Database::get_main_table(NotebookTeacherPlugin::TABLE_NOTEBOOKTEACHER);
        $courseId = api_get_course_int_id();

        $sql = "DELETE FROM $tableNotebook
                WHERE
                    c_id = $courseId AND
                    id = '".(int) $notebookId."' AND
                    user_id = '".api_get_user_id()."'";
        $result = Database::query($sql);

        if (1 != Database::affected_rows($result)) {
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
        $user = api_get_user_entity();
        $sortDirection = 'DESC';
        $linkSortDirection = 'ASC';

        if (!isset($_GET['direction'])) {
            $sortDirection = 'ASC';
            $linkSortDirection = 'DESC';
        } elseif ('ASC' === strtoupper((string) $_GET['direction'])) {
            $sortDirection = 'ASC';
            $linkSortDirection = 'DESC';
        }

        $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
        $currentUrl = api_get_self().'?'.api_get_cidreq().'&student_id='.$studentId;
        $deleteToken = Security::get_token();
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

        $students = [0 => $plugin->get_lang('AllStudent')];
        foreach ($userList as $key => $userItem) {
            $students[(int) $key] = api_get_person_name($userItem['firstname'], $userItem['lastname']);
        }

        $view = Session::read('notebook_view');
        if (!isset($view) || !in_array($view, ['creation_date', 'update_date', 'title'], true)) {
            Session::write('notebook_view', 'creation_date');
        }

        $view = Session::read('notebook_view');
        $tableNotebook = Database::get_main_table(NotebookTeacherPlugin::TABLE_NOTEBOOKTEACHER);
        $orderBy = " ORDER BY $view $sortDirection ";
        $conditionSession = api_get_session_condition($sessionId);
        $condExtra = 'update_date' === $view ? " AND update_date <> ''" : ' ';

        $totalNotes = self::countNotes($tableNotebook, $courseId, $conditionSession, $condExtra, $studentId);
        $selectedStudentLabel = $studentId > 0 && isset($students[$studentId])
            ? $students[$studentId]
            : $plugin->get_lang('AllStudent');

        echo '<section class="w-full px-6 py-4 space-y-6">';
        echo '  <div class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">';
        echo '      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">';
        echo '          <div class="flex items-start gap-4">';
        echo '              <span class="mdi mdi-notebook-edit-outline ch-tool-icon-gradient text-4xl" aria-hidden="true"></span>';
        echo '              <div>';
        echo '                  <h2 class="text-2xl font-semibold text-gray-90">'.$plugin->get_lang('NotebookTeacher').'</h2>';
        echo '                  <p class="mt-1 text-sm text-gray-50">'.$plugin->get_lang('TeacherNotesHelp').'</p>';
        echo '              </div>';
        echo '          </div>';
        echo '          <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">';
        echo self::renderStatCard(get_lang('Learners'), (string) count($userList), 'mdi-account-school-outline');
        echo self::renderStatCard(get_lang('Notes'), (string) $totalNotes, 'mdi-note-text-outline');
        echo self::renderStatCard($plugin->get_lang('CurrentFilter'), Security::remove_XSS($selectedStudentLabel), 'mdi-filter-outline');
        echo '          </div>';
        echo '      </div>';
        echo '  </div>';

        echo '  <div class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm">';
        echo '      <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">';
        echo '          <div class="w-full xl:max-w-sm">';
        echo '              <label for="student_filter" class="mb-2 block text-sm font-semibold text-gray-90">'.$plugin->get_lang('StudentFilter').'</label>';
        echo '              <select id="student_filter" name="student_filter" class="w-full rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm text-gray-90 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" onchange="studentFilter();">';
        foreach ($students as $id => $name) {
            $selected = (int) $id === $studentId ? ' selected="selected"' : '';
            echo '                  <option value="'.(int) $id.'"'.$selected.'>'.Security::remove_XSS($name).'</option>';
        }
        echo '              </select>';
        echo '          </div>';

        echo '          <div class="flex flex-wrap items-center gap-2">';
        if (!api_is_drh() && !api_is_anonymous()) {
            if (empty($sessionId) || api_is_allowed_to_session_edit(false, true)) {
                echo self::renderIconAction(
                    $currentUrl.'&action=addnote',
                    get_lang('Add new note in my personal notebook'),
                    'mdi-plus-box',
                    'bg-primary text-white hover:bg-primary/90'
                );
            }
        }

        echo self::renderIconAction(
            $currentUrl.'&action=changeview&view=creation_date&direction='.$linkSortDirection,
            get_lang('Sort by date created'),
            'mdi-sort-calendar-ascending',
            'bg-white text-primary hover:bg-primary/10'
        );
        echo self::renderIconAction(
            $currentUrl.'&action=changeview&view=update_date&direction='.$linkSortDirection,
            get_lang('Sort by date last modified'),
            'mdi-sort-clock-ascending-outline',
            'bg-white text-primary hover:bg-primary/10'
        );
        echo self::renderIconAction(
            $currentUrl.'&action=changeview&view=title&direction='.$linkSortDirection,
            get_lang('Sort by title'),
            'mdi-sort-alphabetical-ascending',
            'bg-white text-primary hover:bg-primary/10'
        );
        echo '          </div>';
        echo '      </div>';
        echo '  </div>';

        echo '  <div class="space-y-5">';

        $renderedNotes = 0;
        if ($studentId > 0) {
            $studentName = $students[$studentId] ?? get_lang('Learner');
            $notes = self::fetchNotes($tableNotebook, $courseId, $conditionSession, " AND student_id = $studentId", $condExtra, $orderBy);
            $renderedNotes += self::renderNotesGroup(
                Security::remove_XSS($studentName),
                $notes,
                $studentId,
                $deleteToken,
                $user
            );

            if (0 === $renderedNotes) {
                echo self::renderEmptyState($plugin->get_lang('NoNotebookUser'), $plugin->get_lang('EmptyStateHelp'));
            }
        } else {
            foreach ($userList as $key => $userItem) {
                $currentStudentId = (int) $key;
                $studentName = api_get_person_name($userItem['firstname'], $userItem['lastname']);
                $notes = self::fetchNotes(
                    $tableNotebook,
                    $courseId,
                    $conditionSession,
                    " AND student_id = $currentStudentId",
                    $condExtra,
                    $orderBy
                );

                if (!empty($notes)) {
                    $renderedNotes += self::renderNotesGroup(
                        Security::remove_XSS($studentName),
                        $notes,
                        $currentStudentId,
                        $deleteToken,
                        $user
                    );
                }
            }

            $notesWithoutStudent = self::fetchNotes(
                $tableNotebook,
                $courseId,
                $conditionSession,
                ' AND student_id = 0',
                $condExtra,
                $orderBy
            );

            if (!empty($notesWithoutStudent)) {
                $renderedNotes += self::renderNotesGroup(
                    $plugin->get_lang('NotebookNoStudentAssigned'),
                    $notesWithoutStudent,
                    0,
                    $deleteToken,
                    $user
                );
            }

            if (0 === $renderedNotes) {
                echo self::renderEmptyState($plugin->get_lang('NoNotebook'), $plugin->get_lang('EmptyStateHelp'));
            }
        }

        echo '  </div>';
        echo '</section>';
    }

    private static function countNotes($tableNotebook, $courseId, $conditionSession, $condExtra, $studentId)
    {
        $conditionStudent = $studentId > 0 ? " AND student_id = $studentId" : '';
        $sql = "SELECT COUNT(*) AS total
                FROM $tableNotebook
                WHERE c_id = $courseId
                $conditionSession
                $conditionStudent
                $condExtra";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return isset($row['total']) ? (int) $row['total'] : 0;
    }

    private static function fetchNotes($tableNotebook, $courseId, $conditionSession, $conditionStudent, $condExtra, $orderBy)
    {
        $sql = "SELECT *
                FROM $tableNotebook
                WHERE c_id = $courseId
                $conditionSession
                $conditionStudent
                $condExtra
                $orderBy";
        $result = Database::query($sql);
        $notes = [];

        while ($row = Database::fetch_array($result)) {
            $notes[] = $row;
        }

        return $notes;
    }

    private static function renderStatCard($label, $value, $icon)
    {
        return '<div class="rounded-xl border border-gray-25 bg-gray-15 px-4 py-3">'.
            '<div class="flex items-center gap-3">'.
            '<span class="mdi '.$icon.' ch-tool-icon text-xl" aria-hidden="true"></span>'.
            '<div>'.
            '<p class="text-xs font-semibold uppercase tracking-wide text-gray-50">'.Security::remove_XSS($label).'</p>'.
            '<p class="text-lg font-semibold text-gray-90">'.Security::remove_XSS($value).'</p>'.
            '</div>'.
            '</div>'.
            '</div>';
    }

    private static function renderButton($url, $label, $icon, $classes)
    {
        $isPrimary = false !== strpos($classes, 'btn--primary');
        $iconClass = $isPrimary ? 'text-white' : 'ch-tool-icon';
        $style = $isPrimary ? ' style="color: #fff;"' : '';

        return '<a href="'.$url.'" class="'.$classes.' inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold">'.
            '<span class="mdi '.$icon.' '.$iconClass.'"'.$style.' aria-hidden="true"></span>'.
            '<span'.($isPrimary ? $style : '').'>'.Security::remove_XSS($label).'</span>'.
            '</a>';
    }

    private static function renderIconAction($url, $label, $icon, $classes = 'bg-white text-primary hover:bg-primary/10')
    {
        $safeLabel = Security::remove_XSS($label);

        return '<a href="'.$url.'" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-25 shadow-sm transition '.$classes.'" title="'.$safeLabel.'" aria-label="'.$safeLabel.'">'.
            '<span class="mdi '.$icon.' text-xl" aria-hidden="true"></span>'.
            '<span class="sr-only">'.$safeLabel.'</span>'.
            '</a>';
    }

    private static function renderNotesGroup($title, array $notes, $studentId, $deleteToken, $user)
    {
        if (empty($notes)) {
            return 0;
        }

        echo '<div class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">';
        echo '  <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">';
        echo '      <div>';
        echo '          <h3 class="text-lg font-semibold text-gray-90">'.$title.'</h3>';
        echo '          <p class="text-sm text-gray-50">'.count($notes).' '.get_lang('Notes').'</p>';
        echo '      </div>';
        echo '  </div>';
        echo '  <div class="grid gap-4">';

        foreach ($notes as $row) {
            echo self::renderNoteCard($row, $studentId, $deleteToken, $user);
        }

        echo '  </div>';
        echo '</div>';

        return count($notes);
    }

    private static function renderNoteCard(array $row, $studentId, $deleteToken, $user)
    {
        $sessionImg = api_get_session_image($row['session_id'], $user);
        $updateValue = '';

        if ($row['update_date'] != $row['creation_date']) {
            $updateValue = '<span class="inline-flex items-center gap-1">'.
                '<span class="mdi mdi-update text-sm" aria-hidden="true"></span>'.
                get_lang('Updated').': '.Display::dateToStringAgoAndLongDate($row['update_date']).
                '</span>';
        }

        $userInfo = api_get_user_info($row['user_id']);
        $author = $userInfo['complete_name'] ?? '';
        $actions = '';

        if ((int) ($row['user_id']) === api_get_user_id()) {
            $editUrl = api_get_self().'?'.api_get_cidreq().'&student_id='.$studentId.'&action=editnote&notebook_id='.$row['id'];
            $deleteUrl = api_get_self().'?'.api_get_cidreq().'&action=deletenote&student_id='.$studentId.'&notebook_id='.$row['id'].'&sec_token='.$deleteToken;
            $editLabel = Security::remove_XSS(get_lang('Edit'));
            $deleteLabel = Security::remove_XSS(get_lang('Delete'));
            $actions = '<div class="flex items-center gap-2">'.
                '<a href="'.$editUrl.'" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-25 bg-white text-primary shadow-sm transition hover:bg-primary/10" title="'.$editLabel.'" aria-label="'.$editLabel.'">'.
                '<span class="mdi mdi-pencil text-lg" aria-hidden="true"></span>'.
                '<span class="sr-only">'.$editLabel.'</span>'.
                '</a>'.
                '<a href="'.$deleteUrl.'" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-danger/20 bg-white text-danger shadow-sm transition hover:bg-danger/10" title="'.$deleteLabel.'" aria-label="'.$deleteLabel.'" onclick="return confirmation('.htmlspecialchars(json_encode($row['title']), ENT_QUOTES).');">'.
                '<span class="mdi mdi-delete text-lg" aria-hidden="true"></span>'.
                '<span class="sr-only">'.$deleteLabel.'</span>'.
                '</a>'.
                '</div>';
        }

        return '<article class="rounded-xl border border-gray-25 bg-gray-15 p-4">'.
            '<div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">'.
            '<div class="min-w-0">'.
            '<div class="flex flex-wrap items-center gap-2">'.
            '<h4 class="text-base font-semibold text-gray-90">'.Security::remove_XSS($row['title']).'</h4>'.
            $sessionImg.
            '</div>'.
            '<div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-gray-50">'.
            '<span class="inline-flex items-center gap-1">'.
            '<span class="mdi mdi-calendar-outline text-sm" aria-hidden="true"></span>'.
            get_lang('Creation date').': '.Display::dateToStringAgoAndLongDate($row['creation_date']).
            '</span>'.
            $updateValue.
            '<span class="inline-flex items-center gap-1">'.
            '<span class="mdi mdi-account-tie-outline text-sm" aria-hidden="true"></span>'.
            get_lang('Trainer').': '.Security::remove_XSS($author).
            '</span>'.
            '</div>'.
            '</div>'.
            $actions.
            '</div>'.
            '<div class="mt-4 rounded-lg border border-gray-25 bg-white p-4 text-sm text-gray-90">'.
            $row['description'].
            '</div>'.
            '</article>';
    }

    private static function renderEmptyState($message, $helpMessage)
    {
        return '<div class="rounded-2xl border border-gray-25 bg-gray-15 p-8 text-center shadow-sm">'.
            '<span class="mdi mdi-notebook-outline ch-tool-icon-gradient text-5xl" aria-hidden="true"></span>'.
            '<h3 class="mt-4 text-lg font-semibold text-gray-90">'.Security::remove_XSS($message).'</h3>'.
            '<p class="mt-2 text-sm text-gray-50">'.Security::remove_XSS($helpMessage).'</p>'.
            '</div>';
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

        $form->addSelect(
            'student_id',
            get_lang('Learner'),
            $students,
            [
                'id' => 'student_id',
                'class' => 'w-full rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm text-gray-90 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
            ]
        );

        $form->addElement(
            'text',
            'note_title',
            get_lang('Note title'),
            [
                'id' => 'note_title',
                'class' => 'w-full rounded-xl border border-gray-25 bg-white px-4 py-2 text-sm text-gray-90 shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
            ]
        );
        $form->applyFilter('note_title', 'html_filter');
        $form->applyFilter('note_title', 'attr_on_filter');
        $form->addHtmlEditor(
            'note_comment',
            get_lang('Note details'),
            false,
            false,
            api_is_allowed_to_edit()
                ? ['ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300']
                : ['ToolbarSet' => 'NotebookLearner', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student']
        );

        $form->addButtonCreate(get_lang('Save'), 'SubmitNote');

        // Setting the rules
        $form->addRule('note_title', get_lang('Required field'), 'required');

        return $form;
    }
}
