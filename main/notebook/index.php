<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.glossary
 * @author Christian Fasanando, initial version
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium, refactoring and tighter integration
 */

// Name of the language file that needs to be included
$language_file = array('notebook');

// Including the global initialization file
require_once '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'notebook.lib.php';

// The section (tabs)
$this_section = SECTION_COURSES;

// Notice for unauthorized people.
api_protect_course_script(true);

// Including additional libraries
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

// Additional javascript
$htmlHeadXtra[] = NotebookManager::javascript_notebook();
$htmlHeadXtra[] = '<script type="text/javascript">
function setFocus(){
$("#note_title").focus();
}
$(document).ready(function () {
  setFocus();
});
</script>';

// Setting the tool constants
$tool = TOOL_NOTEBOOK;

// Tracking
event_access_tool(TOOL_NOTEBOOK);

// Tool name
if (isset($_GET['action']) && $_GET['action'] == 'addnote') {
	$tool = 'NoteAddNew';
	$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('ToolNotebook'));
}
if (isset($_GET['action']) && $_GET['action'] == 'editnote') {
	$tool = 'ModifyNote';
	$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('ToolNotebook'));
}

// Displaying the header
Display::display_header(get_lang(ucfirst($tool)));

// Tool introduction
Display::display_introduction_section(TOOL_NOTEBOOK);

// Action handling: Adding a note
if (isset($_GET['action']) && $_GET['action'] == 'addnote') {
	if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
		api_not_allowed();
	}

	if (!empty($_GET['isStudentView'])) {
		NotebookManager::display_notes();
		exit;
	}

	$_SESSION['notebook_view'] = 'creation_date';

	// Initiate the object
	$form = new FormValidator('note', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']));
	// Settting the form elements
	$form->addElement('header', '', get_lang('NoteAddNew'));
	$form->addElement('text', 'note_title', get_lang('NoteTitle'), array('size' => '95', 'id' => 'note_title'));
	//$form->applyFilter('note_title', 'html_filter');
	$form->addElement('html_editor', 'note_comment', get_lang('NoteComment'), null, api_is_allowed_to_edit()
		? array('ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300')
		: array('ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student')
	);
	$form->addElement('style_submit_button', 'SubmitNote', get_lang('AddNote'), 'class="add"');

	// Setting the rules
	$form->addRule('note_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if ($form->validate()) {
		$check = Security::check_token('post');
		if ($check) {
	   		$values = $form->exportValues();
	   		$res = NotebookManager::save_note($values);
	   		if ($res) {
	   			Display::display_confirmation_message(get_lang('NoteAdded'));
	   		}
		}
		Security::clear_token();
		NotebookManager::display_notes();
	} else {
		echo '<div class="actions">';
		echo '<a href="index.php">'.Display::return_icon('back.png',get_lang('BackToNotesList'),'','32').'</a>';
		echo '</div>';
		$token = Security::get_token();
		$form->addElement('hidden', 'sec_token');
		$form->setConstants(array('sec_token' => $token));
		$form->display();
	}
}

// Action handling: Editing a note
elseif (isset($_GET['action']) && $_GET['action'] == 'editnote' && is_numeric($_GET['notebook_id'])) {

	if (!empty($_GET['isStudentView'])) {
		NotebookManager::display_notes();
		exit;
	}

	// Initialize the object
	$form = new FormValidator('note', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&notebook_id='.Security::remove_XSS($_GET['notebook_id']));
	// Settting the form elements
	$form->addElement('header', '', get_lang('ModifyNote'));
	$form->addElement('hidden', 'notebook_id');
	$form->addElement('text', 'note_title', get_lang('NoteTitle'), array('size' => '100'));
	//$form->applyFilter('note_title', 'html_filter');
	$form->addElement('html_editor', 'note_comment', get_lang('NoteComment'), null, api_is_allowed_to_edit()
		? array('ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300')
		: array('ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student')
	);
	$form->addElement('style_submit_button', 'SubmitNote', get_lang('ModifyNote'), 'class="save"');

	// Setting the defaults
	$defaults = NotebookManager::get_note_information(Security::remove_XSS($_GET['notebook_id']));
	$form->setDefaults($defaults);

	// Setting the rules
	$form->addRule('note_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if ($form->validate()) {
		$check = Security::check_token('post');
		if ($check) {
	   		$values = $form->exportValues();
	   		$res = NotebookManager::update_note($values);
	   		if ($res) {
	   			Display::display_confirmation_message(get_lang('NoteUpdated'));
	   		}

		}
		Security::clear_token();
		NotebookManager::display_notes();
	} else {
		echo '<div class="actions">';
		echo '<a href="index.php">'.Display::return_icon('back.png',get_lang('BackToNotesList'),'','32').'</a>';
		echo '</div>';
		$token = Security::get_token();
		$form->addElement('hidden', 'sec_token');
		$form->setConstants(array('sec_token' => $token));
		$form->display();
	}
}

// Action handling: deleting a note
elseif (isset($_GET['action']) && $_GET['action'] == 'deletenote' && is_numeric($_GET['notebook_id'])) {

	$res = NotebookManager::delete_note(Security::remove_XSS($_GET['notebook_id']));
	if ($res) {
		Display::display_confirmation_message(get_lang('NoteDeleted'));
	}

	NotebookManager::display_notes();
}

// Action handling: changing the view (sorting order)
elseif ($_GET['action'] == 'changeview' AND in_array($_GET['view'], array('creation_date', 'update_date', 'title'))) {

	switch ($_GET['view']) {
		case 'creation_date':
			if (!$_GET['direction'] OR $_GET['direction'] == 'ASC') {
				Display::display_confirmation_message(get_lang('NotesSortedByCreationDateAsc'));
			} else {
				Display::display_confirmation_message(get_lang('NotesSortedByCreationDateDESC'));
			}
			break;
		case 'update_date':
			if (!$_GET['direction'] OR $_GET['direction'] == 'ASC') {
				Display::display_confirmation_message(get_lang('NotesSortedByUpdateDateAsc'));
			} else {
				Display::display_confirmation_message(get_lang('NotesSortedByUpdateDateDESC'));
			}
			break;
		case 'title':
			if (!$_GET['direction'] OR $_GET['direction'] == 'ASC') {
				Display::display_confirmation_message(get_lang('NotesSortedByTitleAsc'));
			} else {
				Display::display_confirmation_message(get_lang('NotesSortedByTitleDESC'));
			}
			break;
	}
	$_SESSION['notebook_view'] = $_GET['view'];
	NotebookManager::display_notes();
} else {
	NotebookManager::display_notes();
}

// Footer
Display::display_footer();
