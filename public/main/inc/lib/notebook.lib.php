<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CNotebook;
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
					if (confirm(\" ".get_lang("Are you sure you want to delete this note")." \"+ name + \" ?\"))
						{return true;}
					else
						{return false;}
				}
				</script>";
    }

    public static function saveNote(array $values, $userId = 0, $courseId = 0, $sessionId = 0)
    {
        if (!is_array($values) || empty($values['note_title'])) {
            return false;
        }

        $userId = $userId ?: api_get_user_id();
        $courseId = $courseId ?: api_get_course_int_id();
        $course = api_get_course_entity($courseId);
        $sessionId = $sessionId ?: api_get_session_id();
        $session = api_get_session_entity($sessionId);

        $notebook = new CNotebook();
        $notebook
            ->setTitle($values['note_title'])
            ->setDescription($values['note_comment'])
            ->setUser(api_get_user_entity($userId))
            ->addCourseLink($course, $session)
        ;

        $repo = Container::getNotebookRepository();
        $repo->create($notebook);

        return $notebook->getIid();
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
                iid 		AS notebook_id,
                title				AS note_title,
                description 		AS note_comment,
                session_id			AS session_id
                FROM $table
                WHERE iid = '".$notebook_id."' ";
        $result = Database::query($sql);
        if (1 != Database::num_rows($result)) {
            return [];
        }

        return Database::fetch_array($result);
    }

    /**
     * @param array $values
     */
    public static function updateNote($values)
    {
        if (!is_array($values) || empty($values['note_title'])) {
            return false;
        }

        $repo = Container::getNotebookRepository();
        $notebook = $repo->find($values['notebook_id']);

        if (!$notebook) {
            return false;
        }

        $notebook
            ->setTitle($values['note_title'])
            ->setDescription($values['note_comment'])
        ;

        $repo->update($notebook);

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
                    iid='".$notebook_id."' AND
                    user_id = '".api_get_user_id()."'";
        $result = Database::query($sql);
        $affected_rows = Database::affected_rows($result);

        if (1 != $affected_rows) {
            return false;
        }

        // Update item_property (delete)
        /*api_item_property_update(
            api_get_course_info(),
            TOOL_NOTEBOOK,
            $notebook_id,
            'delete',
            api_get_user_id()
        );*/

        return true;
    }

    /**
     * Display notes.
     */
    public static function display_notes()
    {
        $sessionId = api_get_session_id();
        $_user = api_get_user_info();
        if (!isset($_GET['direction'])) {
            $sort_direction = 'ASC';
            $link_sort_direction = 'DESC';
        } elseif ('ASC' == $_GET['direction']) {
            $sort_direction = 'ASC';
            $link_sort_direction = 'DESC';
        } else {
            $sort_direction = 'DESC';
            $link_sort_direction = 'ASC';
        }

        // action links
        echo '<div class="actions">';
        if (!api_is_anonymous()) {
            if (0 == $sessionId || api_is_allowed_to_session_edit(false, true)) {
                echo '<a href="index.php?'.api_get_cidreq().'&action=addnote">'.
                    Display::return_icon('new_note.png', get_lang('Add new note in my personal notebook'), '', '32').
                    '</a>';
            }
        }

        echo '<a
            href="index.php?'.api_get_cidreq().'&action=changeview&view=creation_date&direction='.$link_sort_direction.'">'.
            Display::return_icon('notes_order_by_date_new.png', get_lang('Sort by date created'), '', '32').
            '</a>';
        echo '<a
            href="index.php?'.api_get_cidreq().'&action=changeview&view=update_date&direction='.$link_sort_direction.'">'.
            Display::return_icon('notes_order_by_date_mod.png', get_lang('Sort by date last modified'), '', '32').
            '</a>';
        echo '<a href="index.php?'.api_get_cidreq().'&action=changeview&view=title&direction='.$link_sort_direction.'">'.
            Display::return_icon('notes_order_by_title.png', get_lang('Sort by title'), '', '32').'</a>';
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

        $cond_extra = 'update_date' === $notebookView ? " AND update_date <> ''" : ' ';
        $course_id = api_get_course_int_id();

        $sql = "SELECT * FROM $table
                WHERE
                    c_id = $course_id AND
                    user_id = '".api_get_user_id()."'
                    $condition_session
                    $cond_extra $order_by
                ";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            // Validation when belongs to a session
            $session_img = api_get_session_image($row['session_id'], $_user['status']);
            $updateValue = '';
            if ($row['update_date'] != $row['creation_date']) {
                $updateValue = ', '.get_lang('Updated').': '.Display::dateToStringAgoAndLongDate($row['update_date']);
            }

            $actions = '<a href="'.api_get_self().'?action=editnote&notebook_id='.$row['notebook_id'].'">'.
                Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a>';
            $actions .= '<a
                href="'.api_get_self().'?action=deletenote&notebook_id='.$row['notebook_id'].'"
                onclick="return confirmation(\''.$row['title'].'\');">'.
                Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';

            echo Display::panel(
                $row['description'],
                $row['title'].$session_img.' <div class="pull-right">'.$actions.'</div>',
                get_lang('Creation date').': '.Display::dateToStringAgoAndLongDate($row['creation_date']).$updateValue
            );
        }
    }
}
