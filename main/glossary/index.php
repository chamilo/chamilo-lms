<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
 * @package dokeos.glossary
 * @author Christian Fasanando
 * Glossary tool's user interface
 */
$language_file = array('glossary');
require_once('../inc/global.inc.php');
api_protect_course_script(true);
require_once('glossaryfunction.inc.php');
$is_allowed_to_edit = api_is_allowed_to_edit();
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
Display::display_header(get_lang(ucfirst($tool)));

//---------------------------------------------------------

if ($is_allowed_to_edit) {
	echo '<a href="index.php?'.api_get_cidreq().'&action=addglossary"><img src="../img/filenew.gif" title ="'.get_lang('TermAddNew').'"> '.get_lang('TermAddNew').'</a>';

	/*======================================
				Form Glossary
	======================================*/

	echo '<div class="glossary-add-form">';
	if ($_GET['action'] == 'addglossary') {		
		echo '<form name="frm_glossary" action="index.php?'.api_get_cidreq().'" method="post">';
		echo '<div class="term_glossary">'.get_lang('TermName').'<br /><input type="text" name="name_glossary"></div>';
		echo '<div class="definition_glossary">'.get_lang('TermDefinition').'<br /><textarea cols="60" rows="5" maxlength="255" name="description_glossary"></textarea></div>';
		echo '<div class="action_glossary"><input type="submit" value="'.get_lang('TermAddButton').'"></div>';
		echo '</form>';
	}
	echo '</div>';
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

// order by up/down  
$action = (!empty($_REQUEST['action'])?$_REQUEST['action']:'');
switch($action) {
case 'move_lp_up':
		move_up($_REQUEST['id']);
		break;
case 'move_lp_down':
		move_down($_REQUEST['id']);
		break;
}

// order by type (one = By Start Date, two = By End Date, three = By Term Name)
$type = (int)$_GET['type'];
$glossary_list=get_glossary_details($type); //returns a results resource
$max = Database::num_rows($glossary_list);
$current = 0;
if ($max > 1) {
	if ($type == 1) {
			echo '<div class="glossary-orderby-link">'.get_lang('OrderBy').'&nbsp;:&nbsp;'.get_lang('CreationDate').'&nbsp;|
					<a href="index.php?'.api_get_cidreq().'&type=2">'.get_lang('UpdateDate').'</a>&nbsp;|&nbsp<a href="index.php?'.api_get_cidreq().'&type=3">'.get_lang('TermName').'</a>
					&nbsp;|&nbsp;<a href="index.php?'.api_get_cidreq().'&type=4">'.get_lang('PreSelectedOrder').'</a>		
				  </div>';
	} elseif ($type == 2) {
			echo '<div class="glossary-orderby-link">'.get_lang('OrderBy').'&nbsp;:&nbsp;<a href="index.php?'.api_get_cidreq().'&type=1">'.get_lang('CreationDate').'</a>&nbsp;|&nbsp;
					'.get_lang('UpdateDate').'&nbsp;|&nbsp;<a href="index.php?'.api_get_cidreq().'&type=3">'.get_lang('TermName').'</a>
					&nbsp;|&nbsp;<a href="index.php?'.api_get_cidreq().'&type=4">'.get_lang('PreSelectedOrder').'</a>
				  </div>';
	} elseif ($type == 3) {
			echo '<div class="glossary-orderby-link">'.get_lang('OrderBy').'&nbsp;:&nbsp;<a href="index.php?'.api_get_cidreq().'&type=1">'.get_lang('CreationDate').'</a>&nbsp;|&nbsp;
					<a href="index.php?'.api_get_cidreq().'&type=2">'.get_lang('UpdateDate').'</a>&nbsp;|&nbsp;'.get_lang('TermName').'
					&nbsp;|&nbsp;<a href="index.php?'.api_get_cidreq().'&type=4">'.get_lang('PreSelectedOrder').'</a>		
				  </div>';
	} elseif ($type == 4){ 
			echo '<div class="glossary-orderby-link">'.get_lang('OrderBy').'&nbsp;:&nbsp;<a href="index.php?'.api_get_cidreq().'&type=1">'.get_lang('CreationDate').'</a>&nbsp;|&nbsp;
					<a href="index.php?'.api_get_cidreq().'&type=2">'.get_lang('UpdateDate').'</a>&nbsp;|&nbsp;<a href="index.php?'.api_get_cidreq().'&type=3">'.get_lang('TermName').'</a>					
					&nbsp;|&nbsp;'.get_lang('PreSelectedOrder').'</a>		
					</div>';
	} else {
			echo '<div class="glossary-orderby-link">'.get_lang('OrderBy').'&nbsp;:&nbsp;<a href="index.php?'.api_get_cidreq().'&type=1">'.get_lang('CreationDate').'</a>&nbsp;|&nbsp;
					<a href="index.php?'.api_get_cidreq().'&type=2">'.get_lang('UpdateDate').'</a>&nbsp;|&nbsp;<a href="index.php?'.api_get_cidreq().'&type=3">'.get_lang('TermName').'</a>
					&nbsp;|&nbsp;<a href="index.php?'.api_get_cidreq().'&type=4">'.get_lang('PreSelectedOrder').'</a>		
				  </div>';
	}
}
echo '<br />';
echo '<div class="glossary-terms-list">';

// glossary list
while ($row_glossary_list=Database::fetch_array($glossary_list)) {
	
	$dsp_order = '';
	if ( ($_GET['action'] == 'edit_glossary') && ($_GET['glossary_id'] == $row_glossary_list['glossary_id']) ) {
		if ($is_allowed_to_edit) {
	        echo '<div class="glossary-term-edit-form"><a name="term-'.$row_glossary_list['glossary_id'].'"></a>';
			echo '<form name="form_glossary" action="index.php?'.api_get_cidreq().'">';
			echo '<input type="hidden" name="g_id" value="'.Security::remove_XSS($_GET['glossary_id']).'">';
			echo '<span class="glossary-term-edit-title">'.get_lang('TermName').'</span><br />';
	        echo '<input type="text" name="n_glossary" value="'.$row_glossary_list['name'].'" onfocus="this.select()"><br />';
			echo '<span class="glossary-term-edit-desc">'.get_lang('TermDefinition').'</span><br /><textarea cols="60" rows="5" maxlength="255" name="d_glossary" onfocus="this.select()">'.$row_glossary_list['description'].'</textarea><br />';
			echo '<input type="submit" value="'.get_lang('TermUpdateButton').'">';
			echo '</form></div>';
		}
	} else {
		echo '<div class="glossary-term"><a name="term-'.$row_glossary_list['glossary_id'].'"></a><span class="glossary-term-title">'.$row_glossary_list['name'].'</span><br />';
		echo '<span class="glossary-term-desc">'.$row_glossary_list['description'].'</span><br />';
		if ($is_allowed_to_edit) {			
			$id = $row_glossary_list['glossary_id'];
            $icon_edit ='edit.gif';
            $icon_delete ='delete.gif';
            $icon_move_down = 'down.gif';
            $icon_move_up ='up.gif';
            $icon_gray_down = 'down_na.gif';
            $icon_gray_up = 'up_na.gif';		
            
            // links order by up/down	
            if (isset($type) && $type == 4) {			
	            if ($row_glossary_list['display_order'] == 1 && $max != 1) {
		    		$dsp_order .= '<a href="index.php?'.api_get_cidreq().'&action=move_lp_down&id='.$id.'&type=4">' .
		    				'<img src="../img/'.$icon_move_down.'" border="0" alt="'.get_lang("MoveDown").'" title="'.get_lang("MoveDown").'"/>' .
		    				'</a><img src="../img/'.$icon_gray_up.'" border="0" alt="" title="" />';
		    	} elseif($current == $max-1 && $max != 1) {
		    		//last element
		    		$dsp_order .= '<img src="../img/'.$icon_gray_down.'" border="0" alt="" title="" /><a href="index.php?'.api_get_cidreq().'&action=move_lp_up&id='.$id.'&type=4">' .
			    				'<img src="../img/'.$icon_move_up.'" border="0" alt="'.get_lang("MoveUp").'" title="'.get_lang("MoveUp").'"/>' .
			    				'</a>';
		    	} elseif($max == 1) {
		    		$dsp_order = '&nbsp;';
		    	} else {
		    		$dsp_order .= '<a href="index.php?'.api_get_cidreq().'&action=move_lp_down&id='.$id.'&type=4">' .
		    				'<img src="../img/'.$icon_move_down.'" border="0" alt="'.get_lang("MoveDown").'" title="'.get_lang("MoveDown").'"/>' .
		    				'</a>&nbsp;';
		    		$dsp_order .= '<a href="index.php?'.api_get_cidreq().'&action=move_lp_up&id='.$id.'&type=4">' .
		    				'<img src="../img/'.$icon_move_up.'" border="0" alt="'.get_lang("MoveDown").'" title="'.get_lang("MoveDown").'"/>' .
		    				'</a>';
		    	}
            }
	    	// action links
            echo '<span class="glossary-term-action-links">';
		    echo '<a href="index.php?'.api_get_cidreq().'&action=edit_glossary&glossary_id='.$id.'#term-'.$id.'"><img src="../img/'.$icon_edit.'" title ="'.get_lang('TermEditAction').'"></a>&nbsp;';
		    echo '<a href="index.php?'.api_get_cidreq().'&action=delete_glossary&glossary_id='.$id.'" onclick="return confirmation(\''.$row_glossary_list['name'].'\');"><img src="../img/'.$icon_delete.'" title ="'.get_lang('TermDeleteAction').'"></a></dd>';
		    echo $dsp_order;
            echo '</span>';                       
		}
        echo '</div>';
	}
	$current++;	
}
echo '</div>';

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();