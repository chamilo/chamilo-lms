<?php
/* For licensing terms, see /license.txt */

/**
 * 	This class provides methods for the notebook management.
 * 	Include/require it in your code to use its features.
 * 	@author Carlos Vargas <litox84@gmail.com>, move code of main/notebook up here
 * 	@package chamilo.library
 */
class NotebookManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * a little bit of javascript to display a prettier warning when deleting a note
     *
     * @return string
     *
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
     * @version januari 2009, dokeos 1.8.6
     */
    static function javascript_notebook()
    {
        return "<script>
				function confirmation (name)
				{
					if (confirm(\" " . get_lang("NoteConfirmDelete") . " \"+ name + \" ?\"))
						{return true;}
					else
						{return false;}
				}
				</script>";
    }

    /**
     * This functions stores the note in the database
     *
     * @param array $values
     * @return bool
     * @author Christian Fasanando <christian.fasanando@dokeos.com>
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
     * @version januari 2009, dokeos 1.8.6
     *
     */
    static function save_note($values)
    {
        if (!is_array($values) or empty($values['note_title'])) {
            return false;
        }
        // Database table definition
        $table = Database :: get_course_table(TABLE_NOTEBOOK);
        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $now = api_get_utc_datetime();
        $params = [
            'c_id' => $course_id,
            'user_id' => api_get_user_id(),
            'course' => api_get_course_id(),
            'session_id' => $sessionId,
            'title' => $values['note_title'],
            'description' => $values['note_comment'],
            'creation_date' => $now,
            'update_date' => $now,
            'status' => 0
        ];
        $id = Database::insert($table, $params);

        if ($id > 0) {
            $sql = "UPDATE $table SET notebook_id = $id WHERE iid = $id";
            Database::query($sql);

            //insert into item_property
            api_item_property_update(
                api_get_course_info(),
                TOOL_NOTEBOOK,
                $id,
                'NotebookAdded',
                api_get_user_id()
            );
            return $id;
        }
    }

    /**
     * @param int $notebook_id
     * @return array|mixed
     */
    static function get_note_information($notebook_id)
    {
        if (empty($notebook_id)) {
            return array();
        }
        // Database table definition
        $t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
        $course_id = api_get_course_int_id();

        $sql = "SELECT
                notebook_id 		AS notebook_id,
                title				AS note_title,
                description 		AS note_comment,
                session_id			AS session_id
               FROM $t_notebook
               WHERE c_id = $course_id AND notebook_id = '" . intval($notebook_id) . "' ";
        $result = Database::query($sql);
        if (Database::num_rows($result) != 1) {
            return array();
        }

        return Database::fetch_array($result);
    }

    /**
     * This functions updates the note in the database
     *
     * @param array $values
     *
     * @author Christian Fasanando <christian.fasanando@dokeos.com>
     * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
     * @version januari 2009, dokeos 1.8.6
     */
    static function update_note($values)
    {
        if (!is_array($values) or empty($values['note_title'])) {
            return false;
        }
        // Database table definition
        $table = Database :: get_course_table(TABLE_NOTEBOOK);

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

    static function delete_note($notebook_id)
    {
        if (empty($notebook_id) or $notebook_id != strval(intval($notebook_id))) {
            return false;
        }
        // Database table definition
        $t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

        $course_id = api_get_course_int_id();

        $sql = "DELETE FROM $t_notebook
                WHERE
                    c_id = $course_id AND
                    notebook_id='" . intval($notebook_id) . "' AND
                    user_id = '" . api_get_user_id() . "'";
        $result = Database::query($sql);
        $affected_rows = Database::affected_rows($result);
        if ($affected_rows != 1) {
            return false;
        }
        //update item_property (delete)
        api_item_property_update(
            api_get_course_info(),
            TOOL_NOTEBOOK,
            intval($notebook_id),
            'delete',
            api_get_user_id()
        );
        return true;
    }

    /**
     * Display notes
     */
    public static function display_notes()
    {
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
            if (api_get_session_id() == 0)
                echo '<a href="index.php?' . api_get_cidreq() . '&action=addnote">' .
                    Display::return_icon('new_note.png', get_lang('NoteAddNew'), '', '32') . '</a>';
            elseif (api_is_allowed_to_session_edit(false, true)) {
                echo '<a href="index.php?' . api_get_cidreq() . '&action=addnote">' .
                    Display::return_icon('new_note.png', get_lang('NoteAddNew'), '', '32') . '</a>';
            }
        } else {
            echo '<a href="javascript:void(0)">' . Display::return_icon('new_note.png', get_lang('NoteAddNew'), '', '32') . '</a>';
        }

        echo '<a href="index.php?' . api_get_cidreq() . '&action=changeview&view=creation_date&direction=' . $link_sort_direction . '">' .
            Display::return_icon('notes_order_by_date_new.png', get_lang('OrderByCreationDate'), '', '32') . '</a>';
        echo '<a href="index.php?' . api_get_cidreq() . '&action=changeview&view=update_date&direction=' . $link_sort_direction . '">' .
            Display::return_icon('notes_order_by_date_mod.png', get_lang('OrderByModificationDate'), '', '32') . '</a>';
        echo '<a href="index.php?' . api_get_cidreq() . '&action=changeview&view=title&direction=' . $link_sort_direction . '">' .
            Display::return_icon('notes_order_by_title.png', get_lang('OrderByTitle'), '', '32') . '</a>';
        echo '</div>';

        if (!in_array($_SESSION['notebook_view'], array('creation_date', 'update_date', 'title'))) {
            $_SESSION['notebook_view'] = 'creation_date';
        }

        // Database table definition
        $t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
        $order_by = "";
        if ($_SESSION['notebook_view'] == 'creation_date' || $_SESSION['notebook_view'] == 'update_date') {
            $order_by = " ORDER BY " . $_SESSION['notebook_view'] . " $sort_direction ";
        } else {
            $order_by = " ORDER BY " . $_SESSION['notebook_view'] . " $sort_direction ";
        }

        //condition for the session
        $session_id = api_get_session_id();
        $condition_session = api_get_session_condition($session_id);

        $cond_extra = ($_SESSION['notebook_view'] == 'update_date') ? " AND update_date <> '0000-00-00 00:00:00'" : " ";
        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $t_notebook
                WHERE
                    c_id = $course_id AND
                    user_id = '" . api_get_user_id() . "'
                    $condition_session
                    $cond_extra $order_by
                ";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            // Validation when belongs to a session
            $session_img = api_get_session_image($row['session_id'], $_user['status']);
            $creation_date = api_get_local_time($row['creation_date'], null, date_default_timezone_get());
            $update_date = api_get_local_time($row['update_date'], null, date_default_timezone_get());

            $updateValue = '';
            if ($row['update_date'] <> $row['creation_date']) {
                $updateValue = ', ' . get_lang('UpdateDate') . ': ' . date_to_str_ago($update_date) . '&nbsp;&nbsp;<span class="dropbox_date">' . $update_date . '</span>';
            }

            $actions = '<a href="' . api_get_self() . '?action=editnote&notebook_id=' . $row['notebook_id'] . '">' .
                Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL) . '</a>';
            $actions .= '<a href="' . api_get_self() . '?action=deletenote&notebook_id=' . $row['notebook_id'] . '" onclick="return confirmation(\'' . $row['title'] . '\');">' .
                Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL) . '</a>';

            echo Display::panel(
                $row['description'],
                $row['title'] . $session_img.' <div class="pull-right">'.$actions.'</div>',
                get_lang('CreationDate') . ': ' . date_to_str_ago($creation_date) . '&nbsp;&nbsp;<span class="dropbox_date">' . $creation_date . $updateValue."</span>"
            );
        }
    }
}
