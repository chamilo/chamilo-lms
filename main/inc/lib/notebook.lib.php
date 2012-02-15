<?php
/* For licensing terms, see /license.txt */

/**
*	This class provides methods for the notebook management.
*	Include/require it in your code to use its features.
*	@author Carlos Vargas <litox84@gmail.com>, move code of main/notebook up here
*	@package chamilo.library
*/

class NotebookManager
{
	private function __construct() {

	}
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
	 * @return bool
	 * @author Christian Fasanando <christian.fasanando@dokeos.com>
	 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium
	 * @version januari 2009, dokeos 1.8.6
	 *
	 */
	function save_note($values) {
		if (!is_array($values) or empty($values['note_title'])) { 
			return false; 
		}
		// Database table definition
		$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);
		$course_id = api_get_course_int_id();

		$sql = "INSERT INTO $t_notebook (c_id, user_id, course, session_id, title, description, creation_date,update_date,status)
				VALUES(
					 $course_id,
					'".api_get_user_id()."',
					'".Database::escape_string(api_get_course_id())."',
					'".Database::escape_string($_SESSION['id_session'])."',
					'".Database::escape_string($values['note_title'])."',
					'".Database::escape_string($values['note_comment'])."',
					'".Database::escape_string(date('Y-m-d H:i:s'))."',
					'".Database::escape_string(date('Y-m-d H:i:s'))."',
					'0')";
		$result = Database::query($sql);
		$id = Database::insert_id();
		if ($id > 0) {
			//insert into item_property
			api_item_property_update(api_get_course_info(), TOOL_NOTEBOOK, $id, 'NotebookAdded', api_get_user_id());
		}
		$affected_rows = Database::affected_rows();
		if (!empty($affected_rows)){
			return $id;
		}
	}

	function get_note_information($notebook_id) {
		if (empty($notebook_id)) { return array(); }
		// Database table definition
		$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

		$sql = "SELECT 	notebook_id 		AS notebook_id,
						title				AS note_title,
						description 		AS note_comment,
				   		session_id			AS session_id
				   FROM $t_notebook
				   WHERE notebook_id = '".Database::escape_string($notebook_id)."' ";
		$result = Database::query($sql);
		if (Database::num_rows($result)!=1) { return array(); }
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
		if (!is_array($values) or empty($values['note_title'])) {
			return false;
		}
		// Database table definition
		$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

		$sql = "UPDATE $t_notebook SET
					user_id = '".api_get_user_id()."',
					course = '".Database::escape_string(api_get_course_id())."',
					session_id = '".Database::escape_string($_SESSION['id_session'])."',
					title = '".Database::escape_string($values['note_title'])."',
					description = '".Database::escape_string($values['note_comment'])."',
					update_date = '".Database::escape_string(date('Y-m-d H:i:s'))."'
				WHERE notebook_id = '".Database::escape_string($values['notebook_id'])."'";
		$result = Database::query($sql);

		//update item_property (update)
		api_item_property_update(api_get_course_info(), TOOL_NOTEBOOK, $values['notebook_id'], 'NotebookUpdated', api_get_user_id());
		$affected_rows = Database::affected_rows();
		if (!empty($affected_rows)){
			return true;
		}
	}

	function delete_note($notebook_id) {
		if (empty($notebook_id) or $notebook_id != strval(intval($notebook_id))) { return false; }
		// Database table definition
		$t_notebook = Database :: get_course_table(TABLE_NOTEBOOK);

		$sql = "DELETE FROM $t_notebook WHERE notebook_id='".intval($notebook_id)."' AND user_id = '".api_get_user_id()."'";
		$result = Database::query($sql);
        $affected_rows = Database::affected_rows();
        if ($affected_rows != 1){
        	return false;
        }
		//update item_property (delete)
		api_item_property_update(api_get_course_info(), TOOL_NOTEBOOK, intval($notebook_id), 'delete', api_get_user_id());
		return true;
	}

	function display_notes() {

		global $_user;

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
				if (api_get_session_id()==0)
					echo '<a href="index.php?'.api_get_cidreq().'&amp;action=addnote">'.Display::return_icon('new_note.png',get_lang('NoteAddNew'),'','32').'</a>';
				elseif(api_is_allowed_to_session_edit(false,true)){
					echo '<a href="index.php?'.api_get_cidreq().'&amp;action=addnote">'.Display::return_icon('new_note.png',get_lang('NoteAddNew'),'','32').'</a>';
				}

			} else {
				echo '<a href="javascript:void(0)">'.Display::return_icon('new_note.png',get_lang('NoteAddNew'),'','32').'</a>';
			}
		//}
		echo '<a href="index.php?'.api_get_cidreq().'&amp;action=changeview&amp;view=creation_date&amp;direction='.$link_sort_direction.'">'.Display::return_icon('notes_order_by_date_new.png',get_lang('OrderByCreationDate'),'','32').'</a>';
		echo '<a href="index.php?'.api_get_cidreq().'&amp;action=changeview&amp;view=update_date&amp;direction='.$link_sort_direction.'">'.Display::return_icon('notes_order_by_date_mod.png',get_lang('OrderByModificationDate'),'','32').'</a>';
		echo '<a href="index.php?'.api_get_cidreq().'&amp;action=changeview&amp;view=title&amp;direction='.$link_sort_direction.'">'.Display::return_icon('notes_order_by_title.png',get_lang('OrderByTitle'),'','32').'</a>';
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

		//condition for the session
		$session_id = api_get_session_id();
		$condition_session = api_get_session_condition($session_id);

		$cond_extra = ($_SESSION['notebook_view']== 'update_date')?" AND update_date <> '0000-00-00 00:00:00'":" ";
		$course_id = api_get_course_int_id();
		
		$sql = "SELECT * FROM $t_notebook WHERE c_id = $course_id AND user_id = '".api_get_user_id()."' $condition_session $cond_extra $order_by";
		$result = Database::query($sql);
		while ($row = Database::fetch_array($result)) {
			//validacion when belongs to a session
			$session_img = api_get_session_image($row['session_id'], $_user['status']);
			$creation_date = api_get_local_time($row['creation_date'], null, date_default_timezone_get());
			$update_date = api_get_local_time($row['update_date'], null, date_default_timezone_get());
			echo '<div class="sectiontitle">';
			echo '<span style="float: right;"> ('.get_lang('CreationDate').': '.date_to_str_ago($creation_date).'&nbsp;&nbsp;<span class="dropbox_date">'.$creation_date.'</span>';
			if ($row['update_date'] <> $row['creation_date']) {
				echo ', '.get_lang('UpdateDate').': '.date_to_str_ago($update_date).'&nbsp;&nbsp;<span class="dropbox_date">'.$update_date.'</span>';
			}
			echo ')</span>';
			echo $row['title'] . $session_img;
			echo '</div>';
			echo '<div class="sectioncomment">'.$row['description'].'</div>';
			echo '<div>';
			echo '<a href="'.api_get_self().'?action=editnote&amp;notebook_id='.$row['notebook_id'].'">'.Display::return_icon('edit.png', get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>';
			echo '<a href="'.api_get_self().'?action=deletenote&amp;notebook_id='.$row['notebook_id'].'" onclick="return confirmation(\''.$row['title'].'\');">'.Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
			echo '</div>';
		}
		//return $return;
	}
}
?>
