<?php
/**
 * Created on 15/10/2008
 * @Author Christian Fasanando
 * Show a glossary
 * 
 */
 
 $language_file = array('glossary');
 require_once('../inc/global.inc.php');
 include('glossaryfunction.inc.php');
 $status = $_user['status'];
/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name)
{
	if (confirm(\" ". get_lang("AreYouSureToDeleteThis") ." \"+ name + \" ?\"))
		{return true;}
	else
		{return false;}
} 
function text_focus(){
	document.form_glossary.n_glossary.focus();
	document.form_glossary.n_glossary.select();
}
</script>";

$tool = TOOL_GLOSSARY;
Display::display_header($tool);
//---------------------------------------------------------

if($status=='1') {
	echo '<a href="index.php?action=addglossary"><img src="../img/filenew.gif" title ="'.get_lang('NewDescription').'">'.get_lang('NewDescription').'</a>';
	
	/*======================================
				Form Glossary 
	======================================*/
	 
	echo '<p><div>';
	if ($_GET['action'] == 'addglossary'){
		echo '<form name="frm_glossary" action="index.php">';
		echo '<div class="term_glossary">'.get_lang('Name').'<br /><input type="text" name="name_glossary"></div>';
		echo '<div class="definition_glossary">'.get_lang('Definition').'<br /><textarea cols="60" rows="5" maxlength="255" name="description_glossary"></textarea></div>';
		echo '<div class="action_glossary"><input type="submit" value="'.get_lang('Ok').'"></div>';
		echo '</form>';	
	}
	echo '</div><hr />';
}
/*======================================
			Add Glossary Details
======================================*/
 
	$name_glossary = Security::remove_XSS($_GET['name_glossary']);
	$description_glossary = Security::remove_XSS($_GET['description_glossary']);
	$add_glossary = add_glossary_details($name_glossary,$description_glossary);
	


/*======================================
			Edit Glossary Details
======================================*/		

	$g_id = Security::remove_XSS($_GET['g_id']);
	$n_glossary = Security::remove_XSS($_GET['n_glossary']);
	$d_glossary = Security::remove_XSS($_GET['d_glossary']);
	$edit_glossary = edit_glossary_details($g_id,$n_glossary,$d_glossary);
			

/*======================================
			Delete Glossary Details
======================================*/
	
		
	if ($_GET['action'] == 'delete_glossary') {
	$g_id = Security::remove_XSS($_GET['glossary_id']);	
	$delete_glossary = delete_glossary_details($g_id);
	
	Display::display_confirmation_message(get_lang('NameDeleted'));
					
	}


/*======================================
			Display Glossary Details
======================================*/

    $glossary_list=get_glossary_details();			
	Database::num_rows($glossary_list);
	echo '<p><div><dl>';
	while ($row_glossary_list=Database::fetch_array($glossary_list)) {
			
			if ($_GET['action'] == 'edit_glossary' && $_GET['glossary_id']==$row_glossary_list['glossary_id']){				
				echo '<body onload="text_focus()">';
				echo '<form name="form_glossary" action="index.php">';
				echo '<input type="hidden" name="g_id" value="'.Security::remove_XSS($_GET['glossary_id']).'"><dl>';
				echo '<dt><strong>'.get_lang('Name').'</strong><br /><input type="text" name="n_glossary" value="'.$row_glossary_list['name'].'" onfocus="this.select()"></dt>';
				echo '<dd><strong>'.get_lang('Definition').'</strong><br /><textarea cols="60" rows="5" maxlength="255" name="d_glossary" onfocus="this.select()">'.$row_glossary_list['description'].'</textarea><br>';
				echo '<input type="submit" value="'.get_lang('OK').'"></dd><br>';
				echo '</dl></form></body>';
			}else{
				echo '<dt><strong>'.$row_glossary_list['name'].'</strong></dt>';
				echo '<dd>'.$row_glossary_list['description'].'<br><br>';			
				$icon_edit ='edit.gif';
				$icon_delete ='delete.gif';
				if($status=='1'){
				echo '<a href="index.php?action=edit_glossary&glossary_id='.$row_glossary_list['glossary_id'].'"><img src="../img/'.$icon_edit.'" title ="'.get_lang('Editar').'"></a>&nbsp;';
				echo '<a href="index.php?action=delete_glossary&glossary_id='.$row_glossary_list['glossary_id'].'" onclick="return confirmation(\''.$row_glossary_list['name'].'\');"><img src="../img/'.$icon_delete.'" title ="'.get_lang('Eliminar').'"></a></dd><p>';
				}
			}			
	}					
	echo '</dl><br></p></div>';


/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
