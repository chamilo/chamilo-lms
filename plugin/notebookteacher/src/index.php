<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$course_plugin = 'notebookteacher';
require_once __DIR__.'/../config.php';

// Notice for unauthorized people.
api_protect_course_script(true);

$_setting['student_view_enabled'] = 'false';

$plugin = NotebookTeacherPlugin::create();
$enable = $plugin->get('enable_plugin_notebookteacher') == 'true';

if (!$enable) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

$allow = api_is_teacher() || api_is_drh();
if (!$allow) {
    api_not_allowed(true);
}

$current_course_tool = $plugin->get_lang('NotebookTeacher');
$notebookId = isset($_GET['notebook_id']) ? (int) $_GET['notebook_id'] : 0;
$studentId = isset($_GET['student_id']) ? $_GET['student_id'] : 0;
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
$currentUrl = api_get_self().'?'.api_get_cidreq().'&student_id='.$studentId;

// The section (tabs)
$this_section = SECTION_COURSES;
$location = api_get_self().'?'.api_get_cidreq();

// Additional javascript
$htmlHeadXtra[] = NotebookTeacher::javascriptNotebook();
$htmlHeadXtra[] = '<script>
function setFocus(){
    $("#note_title").focus();
}
$(document).ready(function () {
    setFocus();
});

function studentFilter() {
    var student_id = $("#student_filter").val();
    location.href ="'.$location.'&student_id="+student_id;
}
</script>';

// Tracking
Event::event_access_tool('notebookteacher');

$noteBookTeacher = $tool = $plugin->get_lang('NotebookTeacher');

switch ($action) {
    case 'addnote':
        $tool = 'NoteAddNew';
        $interbreadcrumb[] = [
            'url' => 'index.php?'.api_get_cidreq(),
            'name' => $noteBookTeacher,
        ];

        if ((api_get_session_id() != 0 &&
            !api_is_allowed_to_session_edit(false, true) || api_is_drh())) {
            api_not_allowed();
        }
        Session::write('notebook_view', 'creation_date');

        $form = new FormValidator(
            'note',
            'post',
            $currentUrl.'&action=addnote'
        );
        $form->addHeader(get_lang('NoteAddNew'));
        $form = NotebookTeacher::getForm($form, $studentId);

        $form->setDefaults(['student_id' => $studentId]);
        // The validation or display
        if ($form->validate()) {
            $check = Security::check_token('post');
            if ($check) {
                $values = $form->exportValues();
                $res = NotebookTeacher::saveNote($values);
                if ($res) {
                    Display::addFlash(Display::return_message(get_lang('NoteAdded'), 'confirmation'));
                }
            }

            header('Location: '.$currentUrl);
            exit;
        } else {
            // Displaying the header
            Display::display_header(get_lang(ucfirst($tool)));

            // Tool introduction
            Display::display_introduction_section($noteBookTeacher);

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
        break;
    case 'editnote':
        $tool = 'ModifyNote';
        $interbreadcrumb[] = [
            'url' => 'index.php?'.api_get_cidreq(),
            'name' => $noteBookTeacher,
        ];

        if (empty($notebookId)) {
            api_not_allowed(true);
        }

        $defaults = NotebookTeacher::getNoteInformation($notebookId);

        if (empty($defaults)) {
            api_not_allowed(true);
        }

        $form = new FormValidator(
            'note',
            'post',
            $currentUrl.'&action='.$action.'&notebook_id='.$notebookId
        );
        // Setting the form elements
        $form->addHeader(get_lang('ModifyNote'));
        $form->addHidden('notebook_id', $notebookId);
        $form = NotebookTeacher::getForm($form, $defaults['student_id']);

        // Setting the defaults
        $form->setDefaults($defaults);

        // The validation or display
        if ($form->validate()) {
            $check = Security::check_token('post');
            if ($check) {
                $values = $form->exportValues();
                $res = NotebookTeacher::updateNote($values);
                if ($res) {
                    Display::addFlash(Display::return_message(get_lang('NoteUpdated'), 'confirmation'));
                }
            }
            header('Location: '.$currentUrl);
            exit;
        } else {
            // Displaying the header
            Display::display_header(get_lang(ucfirst($tool)));

            // Tool introduction
            Display::display_introduction_section($noteBookTeacher);
            echo '<div class="actions">';
            echo '<a href="index.php">'.
                Display::return_icon('back.png', get_lang('BackToNotesList'), '', ICON_SIZE_MEDIUM).'</a>';
            echo '</div>';
            $token = Security::get_token();
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $form->display();
        }
        break;
    case 'deletenote':
        $res = NotebookTeacher::deleteNote($notebookId);
        if ($res) {
            Display::addFlash(Display::return_message(get_lang('NoteDeleted'), 'confirmation'));
        }
        header('Location: '.$currentUrl);
        exit;
        break;
    case 'changeview':
        if (in_array($_GET['view'], ['creation_date', 'update_date', 'title'])) {
            switch ($_GET['view']) {
                case 'creation_date':
                    if (!$_GET['direction'] || $_GET['direction'] == 'ASC') {
                        Display::addFlash(
                            Display::return_message(get_lang('NotesSortedByCreationDateAsc'), 'confirmation')
                        );
                    } else {
                        Display::addFlash(
                            Display::return_message(get_lang('NotesSortedByCreationDateDESC'), 'confirmation')
                        );
                    }
                    break;
                case 'update_date':
                    if (!$_GET['direction'] || $_GET['direction'] == 'ASC') {
                        Display::addFlash(
                            Display::return_message(get_lang('NotesSortedByUpdateDateAsc'), 'confirmation')
                        );
                    } else {
                        Display::addFlash(
                            Display::return_message(get_lang('NotesSortedByUpdateDateDESC'), 'confirmation')
                        );
                    }
                    break;
                case 'title':
                    if (!$_GET['direction'] || $_GET['direction'] == 'ASC') {
                        Display::addFlash(Display::return_message(get_lang('NotesSortedByTitleAsc'), 'confirmation'));
                    } else {
                        Display::addFlash(Display::return_message(get_lang('NotesSortedByTitleDESC'), 'confirmation'));
                    }
                    break;
            }
            Session::write('notebook_view', Security::remove_XSS($_GET['view']));
            header('Location: '.$currentUrl);
            exit;
        }
        break;
    default:
        // Displaying the header
        Display::display_header(get_lang(ucfirst($tool)));

        // Tool introduction
        Display::display_introduction_section($noteBookTeacher);
        NotebookTeacher::displayNotes();
        break;
}

Display::display_footer();
