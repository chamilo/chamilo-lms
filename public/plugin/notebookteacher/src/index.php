<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$course_plugin = 'notebookteacher';
require_once __DIR__.'/../config.php';

// Notice for unauthorized people.
api_protect_course_script(true);

$_setting['student_view_enabled'] = 'false';

$plugin = NotebookTeacherPlugin::create();
$enable = 'true' == $plugin->get('enable_plugin_notebookteacher');

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
        $tool = 'Add new note in my personal notebook';
        $interbreadcrumb[] = [
            'url' => 'index.php?'.api_get_cidreq(),
            'name' => $noteBookTeacher,
        ];

        if ((0 != api_get_session_id() &&
            !api_is_allowed_to_session_edit(false, true) || api_is_drh())) {
            api_not_allowed();
        }
        Session::write('notebook_view', 'creation_date');

        $form = new FormValidator(
            'note',
            'post',
            $currentUrl.'&action=addnote'
        );
        $form->addHeader(get_lang('Add new note in my personal notebook'));
        $form = NotebookTeacher::getForm($form, $studentId);

        $form->setDefaults(['student_id' => $studentId]);
        // The validation or display
        if ($form->validate()) {
            $check = Security::check_token('post');
            if ($check) {
                $values = $form->exportValues();
                $res = NotebookTeacher::saveNote($values);
                if ($res) {
                    Display::addFlash(Display::return_message(get_lang('Note added'), 'confirmation'));
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
                Display::return_icon('back.png', get_lang('Back to the notes list'), '', ICON_SIZE_MEDIUM).
                '</a>';
            echo '</div>';
            $token = Security::get_token();
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $form->display();
        }

        break;
    case 'editnote':
        $tool = 'Edit my personal note';
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
        $form->addHeader(get_lang('Edit my personal note'));
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
                    Display::addFlash(Display::return_message(get_lang('Note updated'), 'confirmation'));
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
                Display::return_icon('back.png', get_lang('Back to the notes list'), '', ICON_SIZE_MEDIUM).'</a>';
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
            Display::addFlash(Display::return_message(get_lang('Note deleted'), 'confirmation'));
        }
        header('Location: '.$currentUrl);
        exit;

        break;
    case 'changeview':
        if (in_array($_GET['view'], ['creation_date', 'update_date', 'title'])) {
            switch ($_GET['view']) {
                case 'creation_date':
                    if (!$_GET['direction'] || 'ASC' == $_GET['direction']) {
                        Display::addFlash(
                            Display::return_message(get_lang('Notes sorted by creation date ascendant'), 'confirmation')
                        );
                    } else {
                        Display::addFlash(
                            Display::return_message(get_lang('Notes sorted by creation date downward'), 'confirmation')
                        );
                    }

                    break;
                case 'update_date':
                    if (!$_GET['direction'] || 'ASC' == $_GET['direction']) {
                        Display::addFlash(
                            Display::return_message(get_lang('Notes sorted by update date ascendant'), 'confirmation')
                        );
                    } else {
                        Display::addFlash(
                            Display::return_message(get_lang('Notes sorted by update date downward'), 'confirmation')
                        );
                    }

                    break;
                case 'title':
                    if (!$_GET['direction'] || 'ASC' == $_GET['direction']) {
                        Display::addFlash(Display::return_message(get_lang('Notes sorted by title ascendant'), 'confirmation'));
                    } else {
                        Display::addFlash(Display::return_message(get_lang('Notes sorted by title downward'), 'confirmation'));
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
