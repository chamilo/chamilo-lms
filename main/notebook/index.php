<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
 * @package dokeos.notebook
 * @author Christian Fasanando
 * Notebook tool's user interface
 */
 

$language_file = array ('notebook');
require_once('../inc/global.inc.php');
api_protect_course_script(true);
require_once('notebookfunction.inc.php');

/*
 *	Header
 */
 
$htmlHeadXtra[] = to_javascript_notebook();

$tool = TOOL_NOTEBOOK;
Display::display_header(get_lang(ucfirst($tool))); 
$user_id = api_get_user_id();
$course_id = api_get_course_id();
$session_id = $_SESSION['id_session'];
$ctok = $_SESSION['sec_token'];
$stok = Security::get_token();	

$icon_add = 'kwrite.gif';
$icon_edit ='edit.gif';
$icon_delete ='delete.gif';

//---------------------------------------------------------

echo '<div class="actions">';
echo '<form name="frm_search" method="POST">';
echo '<a href="index.php?action=addnotebook">'.Display::return_icon($icon_add,get_lang('NewNote')).get_lang('NewNote').'</a>';
echo '<input type="hidden" name="action" value="search"/>';
echo '<input type="text" name="search_title" /><input type="submit" value="'.get_lang('SearchByTitle').'"></form>';
echo '</div>';

if (isset($_REQUEST['action']) && $_REQUEST['action']=='addnotebook') {
	echo '<table class="notebook-add-form" id="notebook-add">';	
	echo '<tr><td>';		
	echo '<form name="frm_add_notebook" method="post">';	
	echo '<input type="hidden" name="sec_token" value="'.$stok.'" />';
	echo '<input type="hidden" name="action" value="addnotebook">';
	echo '<div class="notebook-add-title">'.get_lang('Title').'<br /><input type="text" class="notebook-add-title-text" name="title" maxlength="255" size="50" onfocus="this.value=\'\';document.getElementById(\'msg_add_error\').style.display=\'none\';" value="<<'.get_lang('WriteTheTitleHere').'>>"/></div>';
	echo '<div class="notebook-add-desc">'.get_lang('Description').'<br /><textarea class="notebook-add-desc-textarea" rows="5" cols="80" name="description" maxlength="255" onfocus="this.value=\'\';document.getElementById(\'msg_add_error\').style.display=\'none\';"><<'.get_lang('WriteYourNoteHere').'>></textarea></div>';
	echo '<div class="action_notebook"><input type="button" value="'.get_lang('SaveNote').'" onclick="return add_notebook()"><input type="button" value="'.get_lang('Cancel').'" onclick="document.getElementById(\'notebook-add\').style.display = \'none\';document.getElementById(\'msg_add_error\').style.display=\'none\';"></div>';
	echo '<span class="notebook-msg-error" id="msg_add_error"></span>';
	echo '</form>';	
	echo '</td></tr>';			
	echo '</table>';
}
 
/*======================================
			Add Notebook Details
======================================*/

if ($ctok==$_REQUEST['sec_token']) {
	if ((isset($_REQUEST['action']) && $_REQUEST['action']=='addnotebook') && isset($_REQUEST['description']) && isset($_REQUEST['title'])) {	
			$description = Security::remove_XSS($_REQUEST['description']);
			$title = Security::remove_XSS($_REQUEST['title']);
			$add_notebook= add_notebook_details($user_id,$course_id,$session_id,$title,$description);	
			if($add_notebook) {
				Display::display_confirmation_message(get_lang('NoteCreated'));
			}							
	}		
}

/*======================================
			Edit Notebook Details
======================================*/	
if ($ctok==$_REQUEST['sec_token']) {
	if (isset($_REQUEST['upd_notebook_id']) && isset($_REQUEST['upd_title']) && isset($_REQUEST['upd_description'])) {
		$notebook_id = Security::remove_XSS($_REQUEST['upd_notebook_id']);
		$title = Security::remove_XSS($_REQUEST['upd_title']);	
		$description = Security::remove_XSS($_REQUEST['upd_description']);
		$edit_notebook= edit_notebook_details($notebook_id,$user_id,$course_id,$session_id,$title,$description);	
		if($edit_notebook) {
			Display::display_confirmation_message(get_lang('NoteUpdated'));
		}
	}	
	
}

/*======================================
			Delete Notebook Details
======================================*/

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete_notebook'){
	$notebook_id = Security::remove_XSS($_REQUEST['notebook_id']);	
	$delete_notebook = delete_notebook_details($notebook_id);	
	if($delete_notebook) {
		Display::display_confirmation_message(get_lang('NoteDeleted'));
	}					
}

/*======================================
			Display Notebook Details
======================================*/
 
	
// order by type (1 = By Creation Date, 2 = By Update Date, 3 = By Title)
isset($_REQUEST['type'])?$type=$_REQUEST['type']:$type='';
$notebook_list=get_notebook_details($user_id,$course_id,$type);	
$max = Database::num_rows($notebook_list);	


if ($max > 1) {
	echo '<div class="notebook-orderby-link">';	
	if ($type == 3) {
			echo get_lang('OrderBy').'&nbsp;:&nbsp;<a href="index.php?'.api_get_cidreq().'&type=1">'.get_lang('CreationDate').'</a>&nbsp;|&nbsp;
					<a href="index.php?'.api_get_cidreq().'&type=2">'.get_lang('UpdateDate').'</a>&nbsp;|&nbsp;'.get_lang('Title');											  
	} elseif ($type == 2) {
			echo get_lang('OrderBy').'&nbsp;:&nbsp;<a href="index.php?'.api_get_cidreq().'&type=1">'.get_lang('CreationDate').'</a>&nbsp;|&nbsp;
					'.get_lang('UpdateDate').'&nbsp;|&nbsp;<a href="index.php?'.api_get_cidreq().'&type=3">'.get_lang('Title').'</a>';									  
	} else {
			echo get_lang('OrderBy').'&nbsp;:&nbsp;'.get_lang('CreationDate').'&nbsp;|&nbsp;
					<a href="index.php?'.api_get_cidreq().'&type=2">'.get_lang('UpdateDate').'</a>&nbsp;|&nbsp<a href="index.php?'.api_get_cidreq().'&type=3">'.get_lang('Title').'</a>';									  
	}
	echo '</div>';			
}

if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'search') {
	$search_title=$_POST['search_title'];
	$notebook_list=get_notebook_details_by_title($user_id,$course_id,$search_title);
}

//notebook list	
echo '<div>';
while ($row_notebook_list=Database::fetch_array($notebook_list)){
		
	$notebook_id = $_REQUEST['notebook_id'];
	echo '<div class="notebook-list">';
			
	if ((isset($_REQUEST['action']) && $_REQUEST['action']=='edit_notebook') && ($row_notebook_list['notebook_id'] == $notebook_id)){
		echo '<div class="notebook-edit-form"><a name="note-'.$row_notebook_list['notebook_id'].'"></a>';
		echo '<form name="frm_edit_notebook" action="index.php" method="post"><input type="hidden" name="upd_notebook_id" value="'.$notebook_id.'" />';
		echo '<input type="hidden" name="sec_token" value="'.$stok.'" />';
		echo '<input type="hidden" name="type" value="'.Security::remove_XSS($_REQUEST['type']).'" />';
		echo '<div class="upd-title-notebook"><input type="text" class="notebook-edit-title-text" name="upd_title" maxlength="255" size="30" onfocus="this.select();document.getElementById(\'msg_edit_error\').style.display=\'none\';" value="'.$row_notebook_list['title'].'"/>';
		echo '<span class="notebook-date-information" >&nbsp;|&nbsp;'.$row_notebook_list['creation_date'].'</span></div><br />';				
		echo '<div class="upd-desc-notebook"><textarea class="notebook-edit-desc-textarea" rows="4" cols="120"  name="upd_description" maxlength="255" onfocus="this.select();document.getElementById(\'msg_edit_error\').style.display=\'none\';">'.$row_notebook_list['description'].'</textarea></div>';
		echo '<div class="action_notebook"><input type="button" value="'.get_lang('SaveNote').'" onclick="edit_notebook()"><input type="button" value="'.get_lang('Cancel').'" onclick="edit_cancel_notebook()"></div>';
		echo '<span class="notebook-msg-error" id="msg_edit_error"></span>';
		echo '</form></div>';	
	} else {
		echo '<div class="notebook-title-list">';
		echo '<span>'.$row_notebook_list['title'].'</span>&nbsp;|&nbsp;';
		echo '<span class="notebook-date-information" >'.$row_notebook_list['creation_date'].'</span>';
		echo '</div>';				
		echo '<div class="notebook-desc-list">'.$row_notebook_list['description'].'</div>';
		echo '<div class="notebook-term-action-links">';
		echo '<span><a href="index.php?action=edit_notebook&notebook_id='.$row_notebook_list['notebook_id'].'&type='.Security::remove_XSS($_REQUEST['type']).'#note-'.$row_notebook_list['notebook_id'].'" >'.Display::return_icon($icon_edit,get_lang('Edit')).'</a>&nbsp;';
		echo '<a href="index.php?action=delete_notebook&notebook_id='.$row_notebook_list['notebook_id'].'&type='.Security::remove_XSS($_REQUEST['type']).'" onclick="return confirmation(\''.$title.'\');">'.Display::return_icon($icon_delete,get_lang('Edit')).'</a></span>';
		if ( $row_notebook_list['status']==1 ) {			
			echo '&nbsp;&nbsp;<span class="notebook-date-information">'.get_lang('LastUpdateDate').'&nbsp;:&nbsp;'.$row_notebook_list['update_date'].'</span>';	
		}			
		echo '</div>';				
	}
		
echo '</div>';

}	
	
echo '</div>';	
 
 /*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();