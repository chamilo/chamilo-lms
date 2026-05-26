<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CoreBundle\Enums\ActionIcon;
use ChamiloSession as Session;

/**
 * @author Christian Fasanando, initial version
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium,
 * refactoring and tighter integration
 */
require_once __DIR__.'/../inc/global.inc.php';

function notebook_get_resource_language_options(): array
{
    $options = [
        '' => get_lang('No specific language'),
    ];

    $languages = Database::getManager()
        ->getRepository(Language::class)
        ->findBy(['available' => true], ['englishName' => 'ASC'])
    ;

    foreach ($languages as $language) {
        if (!$language instanceof Language) {
            continue;
        }

        $options[$language->getIsocode()] = $language->getOriginalName() ?: $language->getEnglishName();
    }

    return $options;
}

function notebook_get_resource_language_iso_code(?int $noteId): string
{
    if (empty($noteId)) {
        return '';
    }

    $note = Database::getManager()->getRepository(CNotebook::class)->find($noteId);
    if (!$note instanceof CNotebook || null === $note->getResourceNode()) {
        return '';
    }

    $language = $note->getResourceNode()->getLanguage();

    return $language instanceof Language ? $language->getIsocode() : '';
}

function notebook_find_last_note_id(array $values): ?int
{
    $title = trim((string) ($values['note_title'] ?? ''));
    if ('' === $title) {
        return null;
    }

    $queryBuilder = Database::getManager()
        ->getRepository(CNotebook::class)
        ->createQueryBuilder('note')
    ;

    $note = $queryBuilder
        ->andWhere('note.user = :user')
        ->andWhere('note.title = :title')
        ->setParameter('user', api_get_user_entity())
        ->setParameter('title', $title)
        ->orderBy('note.creationDate', 'DESC')
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult()
    ;

    return $note instanceof CNotebook ? $note->getIid() : null;
}

function notebook_apply_resource_language(?int $noteId, mixed $rawLanguage): void
{
    if (empty($noteId)) {
        return;
    }

    $entityManager = Database::getManager();
    $note = $entityManager->getRepository(CNotebook::class)->find($noteId);
    if (!$note instanceof CNotebook || null === $note->getResourceNode()) {
        return;
    }

    $languageCode = trim((string) $rawLanguage);
    $language = null;

    if ('' !== $languageCode) {
        $language = $entityManager
            ->getRepository(Language::class)
            ->findOneBy([
                'isocode' => $languageCode,
                'available' => true,
            ])
        ;

        if (!$language instanceof Language) {
            return;
        }
    }

    $resourceNode = $note->getResourceNode();
    $resourceNode->setLanguage($language);
    $entityManager->persist($resourceNode);
    $entityManager->flush();
}

$current_course_tool = TOOL_NOTEBOOK;

// The section (tabs)
$this_section = SECTION_COURSES;

// Notice for unauthorized people.
api_protect_course_script(true);

// Additional javascript
$htmlHeadXtra[] = NotebookManager::javascript_notebook();
$htmlHeadXtra[] = '<script>
function setFocus(){
    $("#note_title").focus();
}
$(function() {
    setFocus();
});
</script>';

// Setting the tool constants
$tool = TOOL_NOTEBOOK;

// Tracking
Event::event_access_tool(TOOL_NOTEBOOK);

$currentUserId = api_get_user_id();
$action = $_GET['action'] ?? '';

$logInfo = [
    'tool' => TOOL_NOTEBOOK,
    'tool_id' => 0,
    'tool_id_detail' => 0,
    'action' => $action,
    'action_details' => '',
];
Event::registerLog($logInfo);

// Tool name
if ('addnote' === $action) {
    $tool = 'Add new note in my personal notebook';
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq(),
        'name' => get_lang('Notebook'),
    ];
}
if ('editnote' === $action) {
    $tool = 'Edit my personal note';
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq(),
        'name' => get_lang('Notebook'),
    ];
}

// Displaying the header
Display::display_header(get_lang(ucfirst($tool)));

// Tool introduction
Display::display_introduction_section(TOOL_NOTEBOOK);

// Action handling: Adding a note
if ('addnote' === $action) {
    if (0 != api_get_session_id() && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }

    if (!empty($_GET['isStudentView'])) {
        NotebookManager::display_notes();
        Display::display_footer();

        exit;
    }

    Session::write('notebook_view', 'creation_date');

    $form = new FormValidator(
        'note',
        'post',
        api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&'.api_get_cidreq()
    );
    // Setting the form elements
    $form->addElement('header', '', get_lang('Add new note in my personal notebook'));
    $form->addElement('text', 'note_title', get_lang('Note title'), ['id' => 'note_title']);
    $form->applyFilter('note_title', 'html_filter');
    $form->addElement(
        'html_editor',
        'note_comment',
        get_lang('Note details'),
        null,
        api_is_allowed_to_edit()
            ? ['ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300']
            : ['ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student']
    );

    $languageOptions = notebook_get_resource_language_options();
    if (\count($languageOptions) > 2) {
        $form->addButtonAdvancedSettings('advanced_params', get_lang('Advanced settings'));
        $form->addElement('html', '<div id="advanced_params_options" style="display:none">');
        $form->addSelect(
            'language',
            get_lang('Language'),
            $languageOptions,
            [
                'id' => 'resource_language',
            ]
        );
        $form->addElement('html', '</div>');
    }

    $form->addButtonCreate(get_lang('Create note'), 'SubmitNote');

    // Setting the rules
    $form->addRule('note_title', get_lang('Required field'), 'required');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $res = NotebookManager::saveNote($values);
            if ($res) {
                $noteId = is_numeric($res) ? (int) $res : notebook_find_last_note_id($values);
                notebook_apply_resource_language($noteId, $values['language'] ?? '');
                echo Display::return_message(get_lang('Note added'), 'confirmation');
            }
        }
        Security::clear_token();
        NotebookManager::display_notes();
    } else {
        echo Display::toolbarAction(
            'add_glossary',
            [
                Display::url(
                    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
                    api_get_self().'?'.api_get_cidreq()
                ),
            ]
        );
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $form->display();
    }
} elseif ('editnote' === $action && is_numeric($_GET['notebook_id'])) {
    // Action handling: Editing a note

    if (!empty($_GET['isStudentView'])) {
        NotebookManager::display_notes();
        Display::display_footer();

        exit;
    }

    // Setting the defaults
    $defaults = NotebookManager::get_note_information((int) $_GET['notebook_id']);

    if ($currentUserId !== (int) $defaults['user_id']) {
        echo Display::return_message(get_lang('NotAllowed'), 'error');
        Display::display_footer();
        exit();
    }

    // Initialize the object
    $form = new FormValidator(
        'note',
        'post',
        api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&notebook_id='.intval($_GET['notebook_id']).'&'.api_get_cidreq()
    );
    // Setting the form elements
    $form->addElement('header', '', get_lang('Edit my personal note'));
    $form->addElement('hidden', 'notebook_id');
    $form->addElement('text', 'note_title', get_lang('Note title'), ['size' => '100']);
    $form->applyFilter('note_title', 'html_filter');
    $form->addElement(
        'html_editor',
        'note_comment',
        get_lang('Note details'),
        null,
        api_is_allowed_to_edit()
            ? ['ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300']
            : ['ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student']
    );

    $languageOptions = notebook_get_resource_language_options();
    if (\count($languageOptions) > 2) {
        $form->addButtonAdvancedSettings('advanced_params', get_lang('Advanced settings'));
        $form->addElement('html', '<div id="advanced_params_options" style="display:none">');
        $form->addSelect(
            'language',
            get_lang('Language'),
            $languageOptions,
            [
                'id' => 'resource_language',
            ]
        );
        $form->addElement('html', '</div>');
    }

    $form->addButtonUpdate(get_lang('Edit my personal note'), 'SubmitNote');

    $defaults['language'] = notebook_get_resource_language_iso_code((int) $_GET['notebook_id']);
    $form->setDefaults($defaults);

    // Setting the rules
    $form->addRule('note_title', get_lang('Required field'), 'required');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $res = NotebookManager::updateNote($values);
            if ($res) {
                notebook_apply_resource_language((int) ($values['notebook_id'] ?? 0), $values['language'] ?? '');
                echo Display::return_message(get_lang('Note updated'), 'confirmation');
            }
        }
        Security::clear_token();
        NotebookManager::display_notes();
    } else {
        echo Display::toolbarAction(
            'add_glossary',
            [
                Display::url(
                    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back')),
                    api_get_self().'?'.api_get_cidreq()
                ),
            ]
        );
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $form->display();
    }
} elseif ('deletenote' === $action && is_numeric($_GET['notebook_id'])) {
    // Action handling: deleting a note
    $res = NotebookManager::delete_note($_GET['notebook_id']);
    if ($res) {
        echo Display::return_message(get_lang('Note deleted'), 'confirmation');
    }

    NotebookManager::display_notes();
} elseif ('changeview' === $action &&
    in_array($_GET['view'], ['creation_date', 'update_date', 'title'])
) {
    // Action handling: changing the view (sorting order)
    switch ($_GET['view']) {
        case 'creation_date':
            if (!$_GET['direction'] || 'ASC' == $_GET['direction']) {
                echo Display::return_message(
                    get_lang('Notes sorted by creation date ascendant'),
                    'confirmation'
                );
            } else {
                echo Display::return_message(
                    get_lang('Notes sorted by creation date downward'),
                    'confirmation'
                );
            }
            break;
        case 'update_date':
            if (!$_GET['direction'] || 'ASC' == $_GET['direction']) {
                echo Display::return_message(
                    get_lang('Notes sorted by update date ascendant'),
                    'confirmation'
                );
            } else {
                echo Display::return_message(
                    get_lang('Notes sorted by update date downward'),
                    'confirmation'
                );
            }
            break;
        case 'title':
            if (!$_GET['direction'] || 'ASC' == $_GET['direction']) {
                echo Display::return_message(
                    get_lang('Notes sorted by title ascendant'),
                    'confirmation'
                );
            } else {
                echo Display::return_message(
                    get_lang('Notes sorted by title downward'),
                    'confirmation'
                );
            }
            break;
    }
    Session::write('notebook_view', $_GET['view']);
    NotebookManager::display_notes();
} else {
    NotebookManager::display_notes();
}

Display::display_footer();
