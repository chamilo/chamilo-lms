<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use ChamiloSession as Session;

$course_plugin = 'notebookteacher';
require_once __DIR__.'/../config.php';

// Notice for unauthorized people.
api_protect_course_script(true);

$_setting['student_view_enabled'] = 'false';

$plugin = NotebookTeacherPlugin::create();

if (!$plugin->isEnabled(true)) {
    api_not_allowed(true, $plugin->get_lang('ToolDisabled'));
}

$allow = api_is_allowed_to_edit(false, true) || api_is_drh();
if (!$allow) {
    api_not_allowed(true);
}

$current_course_tool = $plugin->get_lang('NotebookTeacher');
$notebookId = isset($_GET['notebook_id']) ? (int) $_GET['notebook_id'] : 0;
$studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
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

            echo '<section class="w-full px-6 py-4 space-y-4">';
            echo '<div class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">';
            echo '<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">';
            echo '<div>';
            echo '<h2 class="text-2xl font-semibold text-gray-90">'.get_lang('Add new note in my personal notebook').'</h2>';
            echo '<p class="mt-1 text-sm text-gray-50">'.$plugin->get_lang('TeacherNotesHelp').'</p>';
            echo '</div>';
            $backLabel = Security::remove_XSS(get_lang('Back to the notes list'));
            echo '<a href="index.php?'.api_get_cidreq().'&student_id='.$studentId.'" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-25 bg-white text-primary shadow-sm transition hover:bg-primary/10" title="'.$backLabel.'" aria-label="'.$backLabel.'">'.
                '<span class="mdi mdi-arrow-left text-xl" aria-hidden="true"></span>'.
                '<span class="sr-only">'.$backLabel.'</span>'.
                '</a>';
            echo '</div>';
            echo '</div>';
            echo '<div class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">';
            $token = Security::get_token();
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $form->display();
            echo '</div>';
            echo '</section>';
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
            echo '<section class="w-full px-6 py-4 space-y-4">';
            echo '<div class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">';
            echo '<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">';
            echo '<div>';
            echo '<h2 class="text-2xl font-semibold text-gray-90">'.get_lang('Edit my personal note').'</h2>';
            echo '<p class="mt-1 text-sm text-gray-50">'.$plugin->get_lang('TeacherNotesHelp').'</p>';
            echo '</div>';
            $backLabel = Security::remove_XSS(get_lang('Back to the notes list'));
            echo '<a href="index.php?'.api_get_cidreq().'&student_id='.$studentId.'" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-25 bg-white text-primary shadow-sm transition hover:bg-primary/10" title="'.$backLabel.'" aria-label="'.$backLabel.'">'.
                '<span class="mdi mdi-arrow-left text-xl" aria-hidden="true"></span>'.
                '<span class="sr-only">'.$backLabel.'</span>'.
                '</a>';
            echo '</div>';
            echo '</div>';
            echo '<div class="rounded-2xl border border-gray-25 bg-white p-5 shadow-sm">';
            $token = Security::get_token();
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(['sec_token' => $token]);
            $form->display();
            echo '</div>';
            echo '</section>';
        }

        break;
    case 'deletenote':
        if (!Security::check_token('get')) {
            Display::addFlash(Display::return_message(get_lang('Invalid token'), 'error'));
            header('Location: '.$currentUrl);
            exit;
        }

        $res = NotebookTeacher::deleteNote($notebookId);
        if ($res) {
            Display::addFlash(Display::return_message(get_lang('Note deleted'), 'confirmation'));
        }

        header('Location: '.$currentUrl);
        exit;

        break;
    case 'changeview':
        $view = isset($_GET['view']) ? Security::remove_XSS($_GET['view']) : '';
        $direction = isset($_GET['direction']) ? strtoupper(Security::remove_XSS($_GET['direction'])) : 'ASC';

        if (in_array($view, ['creation_date', 'update_date', 'title'])) {
            switch ($view) {
                case 'creation_date':
                    if ('ASC' === $direction) {
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
                    if ('ASC' === $direction) {
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
                    if ('ASC' === $direction) {
                        Display::addFlash(Display::return_message(get_lang('Notes sorted by title ascendant'), 'confirmation'));
                    } else {
                        Display::addFlash(Display::return_message(get_lang('Notes sorted by title downward'), 'confirmation'));
                    }

                    break;
            }
            Session::write('notebook_view', $view);
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
