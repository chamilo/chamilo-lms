<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author Christian Fasanando, initial version
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium,
 * refactoring and tighter integration
 */
require_once __DIR__.'/../inc/global.inc.php';

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
if ($action === 'addnote') {
    $tool = 'NoteAddNew';
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq(),
        'name' => get_lang('ToolNotebook'),
    ];
}
if ($action === 'editnote') {
    $tool = 'ModifyNote';
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq(),
        'name' => get_lang('ToolNotebook'),
    ];
}

// Displaying the header
Display::display_header(get_lang(ucfirst($tool)));

// Tool introduction
Display::display_introduction_section(TOOL_NOTEBOOK);

// Action handling: Adding a note
if ($action === 'addnote') {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }

    if (!empty($_GET['isStudentView'])) {
        NotebookManager::display_notes();
        exit;
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
    $form->applyFilter('text', 'html_filter');
    $form->addElement(
        'html_editor',
        'note_comment',
        get_lang('NoteComment'),
        null,
        api_is_allowed_to_edit() ? ['ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300'] : ['ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student']
    );
    $form->addButtonCreate(get_lang('AddNote'), 'SubmitNote');

    // Setting the rules
    $form->addRule('note_title', get_lang('ThisFieldIsRequired'), 'required');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $res = NotebookManager::save_note($values);
            if ($res) {
                echo Display::return_message(get_lang('NoteAdded'), 'confirmation');
            }
        }
        Security::clear_token();
        NotebookManager::display_notes();
    } else {
        echo Display::toolbarAction(
            'add_glossary',
            [
                Display::url(
                    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                    api_get_self().'?'.api_get_cidreq()
                ),
            ]
        );
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $form->display();
    }
} elseif ($action === 'editnote' && is_numeric($_GET['notebook_id'])) {
    // Action handling: Editing a note

    if (!empty($_GET['isStudentView'])) {
        NotebookManager::display_notes();
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
    $form->addElement('header', '', get_lang('ModifyNote'));
    $form->addElement('hidden', 'notebook_id');
    $form->addElement('text', 'note_title', get_lang('NoteTitle'), ['size' => '100']);
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
    $form->addButtonUpdate(get_lang('ModifyNote'), 'SubmitNote');

    $form->setDefaults($defaults);

    // Setting the rules
    $form->addRule('note_title', get_lang('ThisFieldIsRequired'), 'required');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();
            $res = NotebookManager::update_note($values);
            if ($res) {
                echo Display::return_message(get_lang('NoteUpdated'), 'confirmation');
            }
        }
        Security::clear_token();
        NotebookManager::display_notes();
    } else {
        echo Display::toolbarAction(
            'add_glossary',
            [
                Display::url(
                    Display::return_icon('back.png', get_lang('Back'), [], ICON_SIZE_MEDIUM),
                    api_get_self().'?'.api_get_cidreq()
                ),
            ]
        );
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $form->display();
    }
} elseif ($action === 'deletenote' && is_numeric($_GET['notebook_id'])) {
    // Action handling: deleting a note
    $res = NotebookManager::delete_note($_GET['notebook_id']);
    if ($res) {
        echo Display::return_message(get_lang('NoteDeleted'), 'confirmation');
    }

    NotebookManager::display_notes();
} elseif ($action === 'changeview' &&
    in_array($_GET['view'], ['creation_date', 'update_date', 'title'])
) {
    // Action handling: changing the view (sorting order)
    switch ($_GET['view']) {
        case 'creation_date':
            if (!$_GET['direction'] || $_GET['direction'] == 'ASC') {
                echo Display::return_message(
                    get_lang('NotesSortedByCreationDateAsc'),
                    'confirmation'
                );
            } else {
                echo Display::return_message(
                    get_lang('NotesSortedByCreationDateDESC'),
                    'confirmation'
                );
            }
            break;
        case 'update_date':
            if (!$_GET['direction'] || $_GET['direction'] == 'ASC') {
                echo Display::return_message(
                    get_lang('NotesSortedByUpdateDateAsc'),
                    'confirmation'
                );
            } else {
                echo Display::return_message(
                    get_lang('NotesSortedByUpdateDateDESC'),
                    'confirmation'
                );
            }
            break;
        case 'title':
            if (!$_GET['direction'] || $_GET['direction'] == 'ASC') {
                echo Display::return_message(
                    get_lang('NotesSortedByTitleAsc'),
                    'confirmation'
                );
            } else {
                echo Display::return_message(
                    get_lang('NotesSortedByTitleDESC'),
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
