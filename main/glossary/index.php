<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
 * @package dokeos.glossary
 * @author Christian Fasanando
 * Glossary tool's user interface
 */
$language_file = array('glossary');
require_once('../inc/global.inc.php');
require_once('glossaryfunction.inc.php');
$status = $_user['status'];
/*
 *	Header
 */

$htmlHeadXtra[] =
"<script type=\"text/javascript\">
function confirmation (name)
{
	if (confirm(\" ". get_lang("TermConfirmDelete") ." \"+ name + \" ?\"))
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

if ($status == 1) {
	echo '<a href="index.php?action=addglossary"><img src="../img/filenew.gif" title ="'.get_lang('AddNewTerm').'">'.get_lang('AddNewTerm').'</a>';
	
	/*======================================
				Form Glossary 
	======================================*/
	 
	echo '<p><div>';
	if ($_GET['action'] == 'addglossary') {
		echo '<form name="frm_glossary" action="index.php">';
		echo '<div class="term_glossary">'.get_lang('TermName').'<br /><input type="text" name="name_glossary"></div>';
		echo '<div class="definition_glossary">'.get_lang('TermDefinition').'<br /><textarea cols="60" rows="5" maxlength="255" name="description_glossary"></textarea></div>';
		echo '<div class="action_glossary"><input type="submit" value="'.get_lang('TermAddButton').'"></div>';
		echo '</form>';	
	}
	echo '</div></p><hr />';
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
	
	Display::display_confirmation_message(get_lang('TermDeleted'));
}

/*======================================
			Display Glossary Details
======================================*/

$glossary_list=get_glossary_details(); //returns a results resource		
Database::num_rows($glossary_list);
echo '<p><div><dl>';
while ($row_glossary_list=Database::fetch_array($glossary_list)) {
	if ( ($_GET['action'] == 'edit_glossary') && ($_GET['glossary_id'] == $row_glossary_list['glossary_id']) ) {				
		echo '<body onload="text_focus()">';
		echo '<form name="form_glossary" action="index.php">';
		echo '<input type="hidden" name="g_id" value="'.Security::remove_XSS($_GET['glossary_id']).'">';
        echo '<dl>';
		echo '  <dt><strong>'.get_lang('TermName').'</strong><br />';
        echo '    <input type="text" name="n_glossary" value="'.$row_glossary_list['name'].'" onfocus="this.select()">';
        echo '  </dt>';
		echo '  <dd><strong>'.get_lang('TermDefinition').'</strong><br /><textarea cols="60" rows="5" maxlength="255" name="d_glossary" onfocus="this.select()">'.$row_glossary_list['description'].'</textarea><br />';
		echo '    <input type="submit" value="'.get_lang('TermUpdateButton').'">';
        echo '  </dd><br />';
		echo '</dl></form></body>';
	} else {
		echo '<dt><strong>'.$row_glossary_list['name'].'</strong></dt>';
		echo '<dd>'.$row_glossary_list['description'].'<br /><br />';			
		$icon_edit ='edit.gif';
		$icon_delete ='delete.gif';
		if ($status == 1) {
		    echo '<a href="index.php?action=edit_glossary&glossary_id='.$row_glossary_list['glossary_id'].'"><img src="../img/'.$icon_edit.'" title ="'.get_lang('Editar').'"></a>&nbsp;';
		    echo '<a href="index.php?action=delete_glossary&glossary_id='.$row_glossary_list['glossary_id'].'" onclick="return confirmation(\''.$row_glossary_list['name'].'\');"><img src="../img/'.$icon_delete.'" title ="'.get_lang('Eliminar').'"></a></dd><p>';
		}
	}			
}					
echo '</dl></p></div>';
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();