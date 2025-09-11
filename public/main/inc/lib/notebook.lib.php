<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
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
        $notebook->setParent($course);
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
    public static function get_note_information(int $notebook_id): array
    {
        if (empty($notebook_id)) {
            return [];
        }

        $repo = Container::getNotebookRepository();
        $note = $repo->find($notebook_id);
        if (empty($note)) {
            return [];
        }
        /** @var CNotebook $note */
        return [
            'iid' => $note->getIid(),
            'title' => $note->getTitle(),
            'description' => $note->getDescription(),
            'session_id' => api_get_session_id(),
        ];
    }

    /**
     * Updates a note
     * @param array $values
     */
    public static function updateNote($values): mixed
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
    public static function delete_note($notebook_id): bool
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

        return true;
    }

    /**
     * Display notes.
     */
    public static function display_notes()
    {
        $sessionId = api_get_session_id();
        $user = api_get_user_entity();

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
                    Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add new note in my personal notebook')).
                    '</a>';
            }
        }

        echo '<a
            href="index.php?'.api_get_cidreq().'&action=changeview&view=creation_date&direction='.$link_sort_direction.'">'.
            Display::getMdiIcon(ActionIcon::SORT_DATE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Sort by date created')).
            '</a>';
        echo '<a
            href="index.php?'.api_get_cidreq().'&action=changeview&view=update_date&direction='.$link_sort_direction.'">'.
            Display::getMdiIcon(ActionIcon::SORT_DATE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Sort by date last modified')).
            '</a>';
        echo '<a href="index.php?'.api_get_cidreq().'&action=changeview&view=title&direction='.$link_sort_direction.'">'.
            Display::getMdiIcon(ActionIcon::SORT_ALPHA, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Sort by title')).
            '</a>';
        echo '</div>';

        $notebookView = Session::read('notebook_view');
        if (empty($notebookView)) {
            $notebookView = 'creation_date';
        }

        if (!in_array($notebookView, ['creation_date', 'update_date', 'title'])) {
            Session::write('notebook_view', 'creation_date');
        }

        //$order_by = " ORDER BY $notebookView $sort_direction ";

        $course_id = api_get_course_int_id();

        $repo = Container::getNotebookRepository();
        $course = api_get_course_entity($course_id);
        $session = api_get_session_entity($sessionId);

        $notebooks = $repo->getResourcesByCourse($course, $session);

        /** @var CNotebook $item */
        foreach ($notebooks as $item) {
            $notebookData = [
                'id' => $item->getIid(),
                'title' => $item->getTitle(),
                'description' => $item->getDescription(),
                'creation_date' => $item->getCreationDate(),
                'update_date' => $item->getUpdateDate(),
            ];
            // Validation when belongs to a session
            $session_img = api_get_session_image($course_id, $user);
            $updateValue = '';
            if ($notebookData['update_date'] != $notebookData['creation_date']) {
                $updateValue = ', '.get_lang('Updated').': '.Display::dateToStringAgoAndLongDate($notebookData['update_date']);
            }

            $actions = '<a href="'.api_get_self().'?action=editnote&notebook_id='.$notebookData['id'].'">'.
                Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>';
            $actions .= '<a
                href="'.api_get_self().'?action=deletenote&notebook_id='.$notebookData['id'].'"
                onclick="return confirmation(\''.$notebookData['title'].'\');">'.
                Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a>';

            echo Display::panel(
                $notebookData['description'],
                $notebookData['title'].$session_img.' <div class="pull-right">'.$actions.'</div>',
                get_lang('Creation date').': '.Display::dateToStringAgoAndLongDate($notebookData['creation_date']).$updateValue
            );
        }
    }
}
