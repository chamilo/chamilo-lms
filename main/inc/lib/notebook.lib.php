<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This class provides methods for the notebook management.
 * Include/require it in your code to use its features.
 *
 * @author Carlos Vargas <litox84@gmail.com>, move code of main/notebook up here
 */
class NotebookManager
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
     * @return string
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
     *
     * @version januari 2009, dokeos 1.8.6
     */
    public static function javascript_notebook()
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
     *
     * @author Christian Fasanando <christian.fasanando@dokeos.com>
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
     *
     * @version januari 2009, dokeos 1.8.6
     */
    public static function save_note($values, $userId = 0, $courseId = 0, $sessionId = 0)
    {
        if (!is_array($values) || empty($values['note_title'])) {
            return false;
        }

        // Database table definition
        $table = Database::get_course_table(TABLE_NOTEBOOK);
        $userId = $userId ?: api_get_user_id();
        $courseId = $courseId ?: api_get_course_int_id();
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];
        $sessionId = $sessionId ?: api_get_session_id();
        $now = api_get_utc_datetime();
        $params = [
            'notebook_id' => 0,
            'c_id' => $courseId,
            'user_id' => $userId,
            'course' => $courseCode,
            'session_id' => $sessionId,
            'title' => $values['note_title'],
            'description' => $values['note_comment'],
            'creation_date' => $now,
            'update_date' => $now,
            'status' => 0,
        ];
        $id = Database::insert($table, $params);

        if ($id > 0) {
            $sql = "UPDATE $table SET notebook_id = $id WHERE iid = $id";
            Database::query($sql);

            //insert into item_property
            api_item_property_update(
                $courseInfo,
                TOOL_NOTEBOOK,
                $id,
                'NotebookAdded',
                $userId
            );

            return $id;
        }
    }

    /**
     * @param int $notebook_id
     *
     * @return array
     */
    public static function get_note_information($notebook_id)
    {
        if (empty($notebook_id)) {
            return [];
        }

        // Database table definition
        $table = Database::get_course_table(TABLE_NOTEBOOK);
        $course_id = api_get_course_int_id();
        $notebook_id = (int) $notebook_id;

        $sql = "SELECT
                user_id,
                notebook_id 		AS notebook_id,
                title				AS note_title,
                description 		AS note_comment,
                session_id			AS session_id
                FROM $table
                WHERE c_id = $course_id AND notebook_id = '".$notebook_id."' ";
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
     * @author Christian Fasanando <christian.fasanando@dokeos.com>
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
     *
     * @return bool
     *
     * @version januari 2009, dokeos 1.8.6
     */
    public static function update_note($values)
    {
        if (!is_array($values) || empty($values['note_title'])) {
            return false;
        }

        // Database table definition
        $table = Database::get_course_table(TABLE_NOTEBOOK);

        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $params = [
            'user_id' => api_get_user_id(),
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
                'c_id = ? AND notebook_id = ?' => [
                    $course_id,
                    $values['notebook_id'],
                ],
            ]
        );

        // update item_property (update)
        api_item_property_update(
            api_get_course_info(),
            TOOL_NOTEBOOK,
            $values['notebook_id'],
            'NotebookUpdated',
            api_get_user_id()
        );

        return true;
    }

    /**
     * @param int $notebook_id
     *
     * @return bool
     */
    public static function delete_note($notebook_id)
    {
        $notebook_id = (int) $notebook_id;

        if (empty($notebook_id)) {
            return false;
        }

        // Database table definition
        $table = Database::get_course_table(TABLE_NOTEBOOK);
        $course_id = api_get_course_int_id();

        $sql = "DELETE FROM $table
                WHERE
                    c_id = $course_id AND
                    notebook_id='".$notebook_id."' AND
                    user_id = '".api_get_user_id()."'";
        $result = Database::query($sql);
        $affected_rows = Database::affected_rows($result);

        if ($affected_rows != 1) {
            return false;
        }

        // Update item_property (delete)
        api_item_property_update(
            api_get_course_info(),
            TOOL_NOTEBOOK,
            $notebook_id,
            'delete',
            api_get_user_id()
        );

        return true;
    }

    /**
     * Display notes.
     */
    public static function display_notes()
    {
        $cidReq = api_get_cidreq();
        $sessionId = api_get_session_id();
        $_user = api_get_user_info();
        if (!isset($_GET['direction'])) {
            $sort_direction = 'ASC';
            $link_sort_direction = 'DESC';
        } elseif ($_GET['direction'] == 'ASC') {
            $sort_direction = 'ASC';
            $link_sort_direction = 'DESC';
        } else {
            $sort_direction = 'DESC';
            $link_sort_direction = 'ASC';
        }

        // action links
        echo '<div class="actions">';
        if (!api_is_anonymous()) {
            if ($sessionId == 0 || api_is_allowed_to_session_edit(false, true)) {
                echo '<a href="index.php?'.$cidReq.'&action=addnote">'.
                    Display::return_icon('new_note.png', get_lang('NoteAddNew'), '', '32').'</a>';
            }
        }

        echo '<a href="index.php?'.$cidReq.'&action=changeview&view=creation_date&direction='.$link_sort_direction.'">'.
            Display::return_icon('notes_order_by_date_new.png', get_lang('OrderByCreationDate'), '', '32').'</a>';
        echo '<a href="index.php?'.$cidReq.'&action=changeview&view=update_date&direction='.$link_sort_direction.'">'.
            Display::return_icon('notes_order_by_date_mod.png', get_lang('OrderByModificationDate'), '', '32').'</a>';
        echo '<a href="index.php?'.$cidReq.'&action=changeview&view=title&direction='.$link_sort_direction.'">'.
            Display::return_icon('notes_order_by_title.png', get_lang('OrderByTitle'), '', '32').'</a>';
        echo '</div>';

        $notebookView = Session::read('notebook_view');
        if (empty($notebookView)) {
            $notebookView = 'creation_date';
        }

        if (!in_array($notebookView, ['creation_date', 'update_date', 'title'])) {
            Session::write('notebook_view', 'creation_date');
        }

        // Database table definition
        $table = Database::get_course_table(TABLE_NOTEBOOK);
        $order_by = " ORDER BY `$notebookView` $sort_direction ";

        // Condition for the session
        $condition_session = api_get_session_condition($sessionId);

        $cond_extra = $notebookView === 'update_date' ? " AND update_date <> ''" : ' ';
        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $table
                WHERE
                    c_id = $course_id AND
                    user_id = '".api_get_user_id()."'
                    $condition_session
                    $cond_extra $order_by
                ";
        $result = Database::query($sql);
        $iconEdit = Display::return_icon('edit.png', get_lang('Edit'));
        $iconDelete = Display::return_icon('delete.png', get_lang('Delete'));
        while ($row = Database::fetch_array($result)) {
            // Validation when belongs to a session
            $session_img = api_get_session_image($row['session_id'], $_user['status']);
            $updateValue = '';
            if ($row['update_date'] != $row['creation_date']) {
                $updateValue = ', '.get_lang('UpdateDate').': '.Display::dateToStringAgoAndLongDate($row['update_date']);
            }

            $actions = Display::url(
                $iconEdit,
                api_get_self().'?action=editnote&notebook_id='.$row['notebook_id'].'&'.$cidReq
            );
            $actions .= Display::url(
                $iconDelete,
                api_get_self().'?action=deletenote&notebook_id='.$row['notebook_id'].'&'.$cidReq,
                ['onclick' => 'return confirmation(\''.$row['title'].'\');']
            );

            echo Display::panel(
                Security::remove_XSS($row['description']),
                Security::remove_XSS($row['title']).$session_img.
                ' <div class="pull-right">'.$actions.'</div>',
                get_lang('CreationDate').': '.Display::dateToStringAgoAndLongDate($row['creation_date']).
                $updateValue
            );
        }
    }
}
