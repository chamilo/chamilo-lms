<?php
// name of the language file that needs to be included 
$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
	
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$tool_name = get_lang('TermsAndConditions');
Display :: display_header($tool_name);

require_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'security.lib.php');
require_once (api_get_path(LIBRARY_PATH).'legal.lib.php');

$parameters['sec_token'] = Security::get_token();

// action menu
echo '<div class="actions" style="height:22px;">';
echo '<div style="float:right;">
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/legal_add.php">'.Display::return_icon('edit.gif',get_lang('EditTermsAndConditions'),'').get_lang('EditTermsAndConditions').'</a>&nbsp;&nbsp;
	  </div><br />';		  
echo '</div>';

// Actions
if (isset ($_GET['action'])) {
	if ($_GET['action'] == 'show_message')
		Display :: display_normal_message(Security::remove_XSS(stripslashes($_GET['message'])));
	Security::clear_token();
}

$table = new SortableTable('conditions', 'count_mask', 'get_legal_data_mask',2); 
$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('Version'), false, 'width="15px"');
$table->set_header(1, get_lang('Language'), false, 'width="30px"');
$table->set_header(2, get_lang('Content'),false);
$table->set_header(3, get_lang('Changes'), false, 'width="60px"');
$table->set_header(4, get_lang('Type'), false, 'width="60px"');
$table->set_header(5, get_lang('Date'), false, 'width="50px"');

//$table->set_header(4, get_lang('Status'));
//$table->set_header(5, get_lang('Modify'));
//$table->set_column_filter(3, 'active_filter');
//$table->set_column_filter(4, 'status_filter');
//$table->set_column_filter(4, 'modify_filter');
//$table->set_form_actions(array ('delete' => get_lang('DeleteFromPlatform')));
$table->display(); 
/*
function status_filter($active, $url_params, $row) {	
	$url_id =UrlManager::get_url_id($row[1]);	
	if ($row[0] == $url_id ) { 	
		$action='lock';
		$image='right';
	} else {
		$image='wrong';
	}
	// you cannot lock the default	
	$result = Display::return_icon($image.'.gif', get_lang(ucfirst($action)));		

	return $result;
}
*/
/*
function modify_filter($active, $url_params, $row) {
	global $charset;	
	$url_id = $row['0'];
	$result .= '<a href="access_url_edit.php?url_id='.$url_id.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>&nbsp;';
	if ($url_id != '1') {	
		$result .= '<a href="access_urls.php?action=delete_url&amp;url_id='.$url_id.'&amp;sec_token='.$_SESSION['sec_token'].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
	}
	return $result;
}

function active_filter($active, $url_params, $row) {	
	$active = $row['3'];
	if ($active=='1') {
		$action='lock';
		$image='right';
	}
	if ($active=='0') {
		$action='unlock';
		$image='wrong';
	}
	// you cannot lock the default
	if ($row['0']=='1') { 
		$result = Display::return_icon($image.'.gif', get_lang(ucfirst($action)));
	} else {
		$result = '<a href="access_urls.php?action='.$action.'&amp;url_id='.$row['0'].'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon($image.'.gif', get_lang(ucfirst($action))).'</a>';		
	}
	return $result;
}
*/

// this 2 "mask" function are here just because the SortableTable
function get_legal_data_mask($id, $params=null, $row=null) {
	return LegalManager::get_legal_data($id, $params, $row);
}
function count_mask() {
	return LegalManager::count();				
}

/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>