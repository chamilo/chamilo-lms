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

$date = date('Y/m/d H:i:s');
$icon_add = 'kwrite.gif';
$icon_edit ='edit.gif';
$icon_delete ='delete.gif';

//---------------------------------------------------------

echo '<a href="index.php?action=addnotebook">'.Display::return_icon($icon_add,get_lang(' $tool = TOOL_NOTEBOOK;
 Display::display_header(get_lang(ucfirst($tool)));')).get_lang('NewNotebook').'</a>';

if (isset($_REQUEST['action']) && $_REQUEST['action']=='addnotebook') {
	echo '<div class="notebook-add-form" id="notebook-add">';			
	echo '<form name="frm_add_notebook" method="post">';	
	echo '<input type="hidden" name="sec_token" value="'.$stok.'" />';
	echo '<input type="hidden" name="action" value="addnotebook">';
	echo '<div class="add-desc-notebook"><textarea class="style-add-textarea" rows="4" cols="95" name="description" maxlength="255" onfocus="this.value=\'\';document.getElementById(\'msg_add_error\').style.display=\'none\';"><<'.get_lang("WriteHereYourNote").'>></textarea></div>';
	echo '<div class="action_notebook"><input type="button" value="Grabar" onclick="return add_notebook()"><input type="button" value="Cancelar" onclick="document.getElementById(\'notebook-add\').style.display = \'none\';document.getElementById(\'msg_add_error\').style.display=\'none\';"></div>';
	echo '<span class="msg_error" id="msg_add_error"></span>';
	echo '</form>';				
	echo '</div>';
}
 
/*======================================
			Add Notebook Details
======================================*/

if ($ctok==$_POST['sec_token']) {
	if ((isset($_REQUEST['action']) && $_REQUEST['action']=='addnotebook') && isset($_REQUEST['description'])) {	
			$description = Security::remove_XSS($_REQUEST['description']);
			$add_notebook= add_notebook_details($user_id,$course_id,$session_id,$description,$date);	
			if($add_notebook) {
				Display::display_confirmation_message(get_lang('NotebookAdded'));
			}							
	}		
}

/*======================================
			Edit Notebook Details
======================================*/	
if ($ctok==$_POST['sec_token']) {
	if (isset($_REQUEST['upd_notebook_id']) && isset($_REQUEST['upd_description'])) {
		$notebook_id = Security::remove_XSS($_REQUEST['upd_notebook_id']);	
		$description = Security::remove_XSS($_REQUEST['upd_description']);
		$edit_notebook= edit_notebook_details($notebook_id,$user_id,$course_id,$session_id,$description,$date);	
		if($edit_notebook) {
			Display::display_confirmation_message(get_lang('NotebookUpdated'));
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
		Display::display_confirmation_message(get_lang('NotebookDeleted'));
	}					
}

/*======================================
			Display Notebook Details
======================================*/
 
$notebook_list=get_notebook_details($user_id);			
echo '<div>';
$counter = 1;
while ($row_notebook_list=Database::fetch_array($notebook_list)){
	
	$title= get_lang("Note").'&nbsp;'.$counter;
	$notebook_id = $_REQUEST['notebook_id'];
	echo '<div class="notebook-list">';
	echo '<div class="note-number">';
	echo '<span>'.$title.'</span>&nbsp;|&nbsp;';
	echo '<span class="date_information" >'.$row_notebook_list['start_date'].'</span>';
	echo '</div>';
		
	if ((isset($_REQUEST['action']) && $_REQUEST['action']=='edit_notebook') && ($row_notebook_list['notebook_id'] == $notebook_id)){
		echo '<div class="notebook-edit-form"><a name="note-'.$row_notebook_list['notebook_id'].'"></a>';
		echo '<form name="frm_edit_notebook" action="index.php" method="post"><input type="hidden" name="upd_notebook_id" value="'.$notebook_id.'"';
		echo '<input type="hidden" name="sec_token" value="'.$stok.'" />';				
		echo '<div class="upd-desc-notebook"><textarea class="style-edit-textarea" rows="4" cols="120"  name="upd_description" maxlength="255" onfocus="this.select()">'.$row_notebook_list['description'].'</textarea></div>';
		echo '<div class="action_notebook"><input type="button" value="Grabar" onclick="edit_notebook()"><input type="button" value="Cancelar" onclick="edit_cancel_notebook()"></div>';
		echo '<span class="msg_error" id="msg_edit_error"></span>';
		echo '</form></div>';	
	} else {				
		echo '<div class="desc-notebook">'.$row_notebook_list['description'].'</div>';
		echo '<div class="notebook-term-action-links">';
		echo '<span><a href="index.php?action=edit_notebook&notebook_id='.$row_notebook_list['notebook_id'].'#note-'.$row_notebook_list['notebook_id'].'" >'.Display::return_icon($icon_edit,get_lang('Edit')).'</a>&nbsp;';
		echo '<a href="index.php?action=delete_notebook&notebook_id='.$row_notebook_list['notebook_id'].'" onclick="return confirmation(\''.$title.'\');">'.Display::return_icon($icon_delete,get_lang('Edit')).'</a></span>';
		if ( $row_notebook_list['status']==1 ) {
			
			echo '&nbsp;&nbsp;<span class="date_information">'.get_lang('EndDate').'&nbsp;:&nbsp;'.$row_notebook_list['end_date'].'</span>';	
		}			
		echo '</div>';		
	}
		
echo '</div>';
		
$counter++;

}	
	
echo '</div>';
 	
 
 
 
 /*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();