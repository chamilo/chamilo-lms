<?php  //$id: $

/* For licensing terms, see /dokeos_license.txt */

/**
 * @package dokeos.glossary
 * @author Christian Fasanando, initial version
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium, refactoring and tighter integration in Dokeos
 */

// name of the language file that needs to be included
$language_file = array('notebook');

// including the global dokeos file
require_once '../inc/global.inc.php';

// the section (tabs)
$this_section=SECTION_COURSES;


// notice for unauthorized people.
api_protect_course_script(true);

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

// additional javascript
$htmlHeadXtra[] = javascript_notebook();

// setting the tool constants
$tool = TOOL_NOTEBOOK;

// tracking
event_access_tool(TOOL_NOTEBOOK);

// tool name
if ( isset($_GET['action']) && $_GET['action'] == 'addnote')
{
	$tool = get_lang('NoteAddNew');
	$interbreadcrumb[] = array ("url"=>"index.php", "name"=> get_lang('Notebook'));
}
if ( isset($_GET['action']) && $_GET['action'] == 'editnote')
{
	$tool = get_lang('ModifyNote');
	$interbreadcrumb[] = array ("url"=>"index.php", "name"=> get_lang('Notebook'));
}

// displaying the header
Display::display_header(get_lang(ucfirst($tool)));

// Tool introduction
Display::display_introduction_section(TOOL_NOTEBOOK);


// Action handling: Adding a note
if (isset($_GET['action']) && $_GET['action'] == 'addnote')
{

	if (!empty($_GET['isStudentView'])) {
		display_notes();
		exit;
	}

	$_SESSION['notebook_view'] = 'creation_date';

	// initiate the object
	$form = new FormValidator('note','post', api_get_self().'?action='.Security::remove_XSS($_GET['action']));
	// settting the form elements
	$form->addElement('header', '', get_lang('NoteAddNew'));
	$form->addElement('text', 'note_title', get_lang('NoteTitle'),array('size'=>'95'));
	//$form->applyFilter('note_title', 'html_filter');
	$form->addElement('html_editor', 'note_comment', get_lang('NoteComment'), null, api_is_allowed_to_edit()
		? array('ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300')
		: array('ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student')
	);
	$form->addElement('style_submit_button', 'SubmitNote', get_lang('AddNote'), 'class="add"');

	// setting the rules
	$form->addRule('note_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if ( $form->validate() )
	{
		$check = Security::check_token('post');
		if ($check)
		{
	   		$values = $form->exportValues();
	   		save_note($values);

		}
		Security::clear_token();
		display_notes();
	}
	else
	{
		echo '<div class="actions">';
		echo '<a href="index.php">'.Display::return_icon('back.png').' '.get_lang('BackToNotesList').'</a>';
		echo '</div>';
		$token = Security::get_token();
		$form->addElement('hidden','sec_token');
		$form->setConstants(array('sec_token' => $token));
		$form->display();
	}
}

// Action handling: Editing a note
else if (isset($_GET['action']) && $_GET['action'] == 'editnote' && is_numeric($_GET['notebook_id']))
{

	if (!empty($_GET['isStudentView'])) {
		display_notes();
		exit;
	}

	// initiate the object
	$form = new FormValidator('note','post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&notebook_id='.Security::remove_XSS($_GET['notebook_id']));
	// settting the form elements
	$form->addElement('header', '', get_lang('ModifyNote'));
	$form->addElement('hidden', 'notebook_id');
	$form->addElement('text', 'note_title', get_lang('NoteTitle'),array('size'=>'100'));
	//$form->applyFilter('note_title', 'html_filter');
	$form->addElement('html_editor', 'note_comment', get_lang('NoteComment'), null, api_is_allowed_to_edit()
		? array('ToolbarSet' => 'Notebook', 'Width' => '100%', 'Height' => '300')
		: array('ToolbarSet' => 'NotebookStudent', 'Width' => '100%', 'Height' => '300', 'UserStatus' => 'student')
	);
	$form->addElement('style_submit_button', 'SubmitNote', get_lang('ModifyNote'), 'class="save"');

	// setting the defaults
	$defaults = get_note_information(Security::remove_XSS($_GET['notebook_id']));
	$form->setDefaults($defaults);

	// setting the rules
	$form->addRule('note_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

	// The validation or display
	if ( $form->validate() )
	{
		$check = Security::check_token('post');
		if ($check)
		{
	   		$values = $form->exportValues();
	   		update_note($values);
		}
		Security::clear_token();
		display_notes();
	}
	else
	{
		echo '<div class="actions">';
		echo '<a href="index.php">'.Display::return_icon('back.png').' '.get_lang('BackToNotesList').'</a>';
		echo '</div>';
		$token = Security::get_token();
		$form->addElement('hidden','sec_token');
		$form->setConstants(array('sec_token' => $token));
		$form->display();
	}
}

// Action handling: deleting a note
else if (isset($_GET['action']) && $_GET['action'] == 'deletenote' && is_numeric($_GET['notebook_id']))
{
	delete_note(Security::remove_XSS($_GET['notebook_id']));
	display_notes();
}

// Action handling: changing the view (sorting order)
else if ($_GET['action'] == 'changeview' AND in_array($_GET['view'],array('creation_date','update_date', 'title')))
{
	switch ($_GET['view'])
	{
		case 'creation_date':
			if (!$_GET['direction'] OR $_GET['direction'] == 'ASC')
			{
				Display::display_confirmation_message(get_lang('NotesSortedByCreationDateAsc'));
			}
			else
			{
				Display::display_confirmation_message(get_lang('NotesSortedByCreationDateDESC'));
			}
			break;
		case 'update_date':
			if (!$_GET['direction'] OR $_GET['direction'] == 'ASC')
			{
				Display::display_confirmation_message(get_lang('NotesSortedByUpdateDateAsc'));
			}
			else
			{
				Display::display_confirmation_message(get_lang('NotesSortedByUpdateDateDESC'));
			}
			break;
		case 'title':
			if (!$_GET['direction'] OR $_GET['direction'] == 'ASC')
			{
				Display::display_confirmation_message(get_lang('NotesSortedByTitleAsc'));
			}
			else
			{
				Display::display_confirmation_message(get_lang('NotesSortedByTitleDESC'));
			}
			break;
	}
	$_SESSION['notebook_view'] = $_GET['view'];
	display_notes();
} else {
	display_notes();
}


// footer
Display::display_footer();

/**
 * a little bit of javascript to display a prettier warning when deleting a note
 *
 * @return unknown
 *
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
 * @version januari 2009, dokeos 1.8.6
 */
function javascript_notebook()
{
	return "<script type=\"text/javascript\">
			function confirmation (name)
			{
				if (confirm(\" ". get_lang("NoteConfirmDelete") ." \"+ name + \" ?\"))
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
 *
 * @author Christian Fasanando <christian.fasanando@dokeos.com>
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
 * @version januari 2009, dokeos 1.8.6
 */
function save_note($values) {
	// Database table definition
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

	$sql = "INSERT INTO $t_notebook (user_id, course, session_id, title, description, creation_date,update_date,status)
			VALUES(
				'".Database::escape_string(api_get_user_id())."',
				'".Database::escape_string(api_get_course_id())."',
				'".Database::escape_string($_SESSION['id_session'])."',
				'".Database::escape_string(Security::remove_XSS($values['note_title']))."',
				'".Database::escape_string(Security::remove_XSS(stripslashes(api_html_entity_decode($values['note_comment'])),COURSEMANAGERLOWSECURITY))."',
				'".Database::escape_string(date('Y-m-d H:i:s'))."',
				'".Database::escape_string(date('Y-m-d H:i:s'))."',
				'0')";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	// display the feedback message
	Display::display_confirmation_message(get_lang('NoteAdded'));
}

function get_note_information($notebook_id) {
	// Database table definition
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

	$sql = "SELECT 	notebook_id 		AS notebook_id,
					title				AS note_title,
					description 		AS note_comment
			   FROM $t_notebook
			   WHERE notebook_id = '".Database::escape_string($notebook_id)."' ";
	$result = api_sql_query($sql, __FILE__, __LINE__);
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
function update_note($values) {
	// Database table definition
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

	$sql = "UPDATE $t_notebook SET
				user_id = '".Database::escape_string(api_get_user_id())."',
				course = '".Database::escape_string(api_get_course_id())."',
				session_id = '".Database::escape_string($_SESSION['id_session'])."',
				title = '".Database::escape_string(Security::remove_XSS($values['note_title']))."',
				description = '".Database::escape_string(Security::remove_XSS(stripslashes(api_html_entity_decode($values['note_comment'])),COURSEMANAGERLOWSECURITY))."',
				update_date = '".Database::escape_string(date('Y-m-d H:i:s'))."'
			WHERE notebook_id = '".Database::escape_string($values['notebook_id'])."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	// display the feedback message
	Display::display_confirmation_message(get_lang('NoteUpdated'));
}

function delete_note($notebook_id) {
	// Database table definition
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

	$sql = "DELETE FROM $t_notebook WHERE notebook_id='".Database::escape_string($notebook_id)."' AND user_id = '".Database::escape_string(api_get_user_id())."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	Display::display_confirmation_message(get_lang('NoteDeleted'));
}

function display_notes() {

	if (!$_GET['direction'])
	{
		$sort_direction = 'ASC';
		$link_sort_direction = 'DESC';
	}
	elseif ($_GET['direction'] == 'ASC')
	{
		$sort_direction = 'ASC';
		$link_sort_direction = 'DESC';
	}
	else
	{
		$sort_direction = 'DESC';
		$link_sort_direction = 'ASC';
	}


	// action links
	echo '<div class="actions" style="margin-bottom:20px">';
	//if (api_is_allowed_to_edit())
	//{
		if (!api_is_anonymous()) {
			echo '<a href="index.php?'.api_get_cidreq().'&amp;action=addnote">'.Display::return_icon('filenew.gif',get_lang('NoteAddNew')).get_lang('NoteAddNew').'</a>';
		} else {
			echo '<a href="javascript:void(0)">'.Display::return_icon('filenew.gif',get_lang('NoteAddNew')).get_lang('NoteAddNew').'</a>';
		}
	//}
	echo '<a href="index.php?'.api_get_cidreq().'&amp;action=changeview&amp;view=creation_date&amp;direction='.$link_sort_direction.'">'.Display::return_icon('calendar_select.gif',get_lang('OrderByCreationDate')).get_lang('OrderByCreationDate').'</a>';
	echo '<a href="index.php?'.api_get_cidreq().'&amp;action=changeview&amp;view=update_date&amp;direction='.$link_sort_direction.'">'.Display::return_icon('calendar_select.gif',get_lang('OrderByModificationDate')).get_lang('OrderByModificationDate').'</a>';
	echo '<a href="index.php?'.api_get_cidreq().'&amp;action=changeview&amp;view=title&amp;direction='.$link_sort_direction.'">'.Display::return_icon('comment.gif',get_lang('OrderByTitle')).get_lang('OrderByTitle').'</a>';
	echo '</div>';

	if (!in_array($_SESSION['notebook_view'],array('creation_date','update_date', 'title'))) {
		$_SESSION['notebook_view'] = 'creation_date';
	}

	// Database table definition
	$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
	$order_by = "";
	if ($_SESSION['notebook_view'] == 'creation_date' || $_SESSION['notebook_view'] == 'update_date') {
		$order_by = " ORDER BY ".$_SESSION['notebook_view']." $sort_direction ";
	} else {
		$order_by = " ORDER BY ".$_SESSION['notebook_view']." $sort_direction ";
	}

	$cond_extra = ($_SESSION['notebook_view']== 'update_date')?" AND update_date <> '0000-00-00 00:00:00'":" ";

	$sql = "SELECT * FROM $t_notebook WHERE user_id = '".Database::escape_string(api_get_user_id())."' $cond_extra $order_by";
	$result = Database::query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result)) {
		echo '<div class="sectiontitle">';
		echo '<span style="float: right;"> ('.get_lang('CreationDate').': '.date_to_str_ago($row['creation_date']).'&nbsp;&nbsp;<span class="dropbox_date">'.$row['creation_date'].'</span>';
		if ($row['update_date'] <> $row['creation_date']) {
			echo ', '.get_lang('UpdateDate').': '.date_to_str_ago($row['update_date']).'&nbsp;&nbsp;<span class="dropbox_date">'.$row['update_date'].'</span>';
		}
		echo ')</span>';
		echo $row['title'];
		echo '</div>';
		echo '<div class="sectioncomment">'.$row['description'].'</div>';
		echo '<div>';
		echo '<a href="'.api_get_self().'?action=editnote&amp;notebook_id='.$row['notebook_id'].'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
		echo '<a href="'.api_get_self().'?action=deletenote&amp;notebook_id='.$row['notebook_id'].'" onclick="return confirmation(\''.$row['title'].'\');">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
		echo '</div>';
	}
	return $return;
}
?>
