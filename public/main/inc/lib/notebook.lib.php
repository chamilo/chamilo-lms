<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CNotebook;
use ChamiloSession as Session;

/**
 * Notebook tool manager.
 */
class NotebookManager
{
    public function __construct() {}

    /**
     * Delete confirmation JS.
     */
    public static function javascript_notebook(): string
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

    /**
     * Create a note.
     */
    public static function saveNote(array $values, $userId = 0, $courseId = 0, $sessionId = 0)
    {
        if (!is_array($values) || empty($values['note_title'])) {
            return false;
        }

        $userId    = $userId ?: api_get_user_id();
        $courseId  = $courseId ?: api_get_course_int_id();
        $course    = api_get_course_entity($courseId);
        $sessionId = $sessionId ?: api_get_session_id();
        $session   = api_get_session_entity($sessionId);

        $notebook = new CNotebook();
        $notebook->setParent($course);
        $notebook
            ->setTitle($values['note_title'])
            ->setDescription($values['note_comment'])
            ->setUser(api_get_user_entity($userId))
            ->addCourseLink($course, $session);

        $repo = Container::getNotebookRepository();
        $repo->create($notebook);

        return $notebook->getIid();
    }

    /**
     * Read note info for edit form defaults.
     */
    public static function get_note_information(int $notebook_id): array
    {
        if (empty($notebook_id)) {
            return [];
        }

        $repo = Container::getNotebookRepository();
        /** @var CNotebook|null $note */
        $note = $repo->find($notebook_id);
        if (!$note) {
            return [];
        }

        return [
            'notebook_id' => $note->getIid(),
            'note_title'  => $note->getTitle(),
            'note_comment'=> $note->getDescription(),
            'session_id'  => $note->getFirstResourceLink()?->getSession()?->getId() ?? api_get_session_id(),
        ];
    }

    /**
     * Update a note (owner only).
     */
    public static function updateNote($values): bool
    {
        if (!is_array($values) || empty($values['note_title'])) {
            return false;
        }

        $repo = Container::getNotebookRepository();
        /** @var CNotebook|null $notebook */
        $notebook = $repo->find((int) ($values['notebook_id'] ?? 0));

        if (!$notebook) {
            return false;
        }

        if ($notebook->getUser()?->getId() !== api_get_user_id()) {
            return false;
        }

        $notebook
            ->setTitle($values['note_title'])
            ->setDescription($values['note_comment']);

        $repo->update($notebook);

        return true;
    }

    /**
     * Delete a note (owner only).
     */
    public static function delete_note($notebook_id): bool
    {
        $repo = Container::getNotebookRepository();

        /** @var CNotebook|null $note */
        $note = $repo->find((int) $notebook_id);
        if (!$note) {
            return false;
        }

        if ($note->getUser()?->getId() !== api_get_user_id()) {
            return false;
        }

        $repo->delete($note);

        return true;
    }

    /**
     * List notes.
     */
    public static function display_notes(): void
    {
        $sessionId = api_get_session_id();
        $user      = api_get_user_entity();

        // Sorting direction
        if (!isset($_GET['direction'])) {
            $sort_direction      = 'ASC';
            $link_sort_direction = 'DESC';
        } elseif ('ASC' == $_GET['direction']) {
            $sort_direction      = 'ASC';
            $link_sort_direction = 'DESC';
        } else {
            $sort_direction      = 'DESC';
            $link_sort_direction = 'ASC';
        }

        // Toolbar (single group string)
        $left = '';
        if (!api_is_anonymous() && (0 == $sessionId || api_is_allowed_to_session_edit(false, true))) {
            $left .= Display::url(
                Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Add new note in my personal notebook')),
                api_get_self().'?'.api_get_cidreq().'&action=addnote'
            );
        }
        $left .= Display::url(
            Display::getMdiIcon(ActionIcon::SORT_DATE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Sort by date created')),
            api_get_self().'?'.api_get_cidreq().'&action=changeview&view=creation_date&direction='.$link_sort_direction
        );
        $left .= Display::url(
            Display::getMdiIcon(ActionIcon::SORT_DATE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Sort by date last modified')),
            api_get_self().'?'.api_get_cidreq().'&action=changeview&view=update_date&direction='.$link_sort_direction
        );
        $left .= Display::url(
            Display::getMdiIcon(ActionIcon::SORT_ALPHA, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Sort by title')),
            api_get_self().'?'.api_get_cidreq().'&action=changeview&view=title&direction='.$link_sort_direction
        );

        echo '<div class="mb-4">'.Display::toolbarAction('nb_actions', [$left]).'</div>';

        // View
        $notebookView = Session::read('notebook_view') ?: 'creation_date';
        if (!in_array($notebookView, ['creation_date', 'update_date', 'title'], true)) {
            $notebookView = 'creation_date';
            Session::write('notebook_view', 'creation_date');
        }

        // Load notebooks via repository helper (filters by course/session/owner + order)
        $fieldMap = ['creation_date' => 'creation_date', 'update_date' => 'update_date', 'title' => 'title'];
        $orderField = $fieldMap[$notebookView] ?? 'creation_date';

        $notebooks = Container::getNotebookRepository()->findByUser(
            $user,
            api_get_course_entity(),
            api_get_session_entity($sessionId),
            $orderField,
            $sort_direction
        );

        // Empty state
        if (empty($notebooks)) {
            echo '<div class="rounded-2xl border border-dashed border-gray-200 p-10 text-center text-gray-600 bg-white">
                    <div class="text-lg font-medium mb-1">'.get_lang('No notes yet').'</div>
                    <div class="text-sm">'.get_lang('Create your first note to get started.').'</div>
                  </div>';

            return;
        }

        echo '<div class="space-y-4">';

        /** @var CNotebook $item */
        foreach ($notebooks as $item) {
            $sessionIdForIcon = $item->getFirstResourceLink()?->getSession()?->getId();
            $session_img = api_get_session_image($sessionIdForIcon, $user);

            $updateValue = '';
            if ($item->getUpdateDate() && $item->getUpdateDate() != $item->getCreationDate()) {
                $updateValue = ', '.get_lang('Updated').': '.Display::dateToStringAgoAndLongDate($item->getUpdateDate());
            }

            $editUrl = api_get_self().'?'.api_get_cidreq().'&action=editnote&notebook_id='.$item->getIid();
            $delUrl  = api_get_self().'?'.api_get_cidreq().'&action=deletenote&notebook_id='.$item->getIid();

            $actions =
                '<div class="flex items-center gap-3">'.
                Display::url(
                    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
                    $editUrl,
                    ['class' => 'text-sky-700 hover:text-sky-800', 'aria-label' => get_lang('Edit')]
                ).
                Display::url(
                    Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')),
                    $delUrl,
                    [
                        'onclick' => "return confirmation('".addslashes($item->getTitle())."');",
                        'class'   => 'text-rose-600 hover:text-rose-700',
                        'aria-label' => get_lang('Delete'),
                    ]
                ).
                '</div>';

            // Sanitize description for safe display (keeps basic formatting)
            $desc = Security::remove_XSS($item->getDescription(), COURSEMANAGERLOWSECURITY);

            echo '<div class="bg-white shadow-sm rounded-2xl p-5 border border-gray-100">';
            echo '<div class="flex items-start justify-between gap-4">';
            echo '<div class="min-w-0">';
            echo '<div class="flex items-center gap-2">';
            echo '<h3 class="font-semibold text-gray-900 text-lg leading-snug break-words">'.$item->getTitle().'</h3>';
            echo '<span class="inline-flex">'.$session_img.'</span>';
            echo '</div>';
            echo '<div class="prose prose-sm max-w-none text-gray-700 mt-2">'.$desc.'</div>';
            echo '</div>';
            echo $actions;
            echo '</div>';

            echo '<div class="mt-3 text-xs text-gray-500">';
            echo get_lang('Creation date').': '.Display::dateToStringAgoAndLongDate($item->getCreationDate()).$updateValue;
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
    }
}
