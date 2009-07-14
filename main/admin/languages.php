<?php // $Id: languages.php 22087 2009-07-14 20:48:49Z iflorespaz $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
* This page allows the platform admin to decide which languages should
* be available in the language selection menu in the login page. This can be
* useful for countries with more than one official language (like Belgium: 
* Dutch, French and German) or international organisations that are active in	
* a limited number of countries. 
* 
* @author Patrick Cool, main author
* @author Roan EMbrechts, code cleaning
* @since Dokeos 1.6
* @package dokeos.admin
==============================================================================
*/
/*
============================================================================== 
	   INIT SECTION
============================================================================== 
*/
// name of the language file that needs to be included
$language_file = 'admin';

// we are in the admin area so we do not need a course id
$cidReset = true;

// include global script
require_once '../inc/global.inc.php';
require_once 'admin.class.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Ajax request
if (isset($_POST['sent_http_request'])) {
	if (isset($_POST['visibility']) && $_POST['visibility']==strval(intval($_POST['visibility'])) && $_POST['visibility']==0) {
		if (isset($_POST['id'])&& $_POST['id']==strval(intval($_POST['id']))) {
			AdminManager::make_unavailable_language($_POST['id']);
			echo 'set_hidden';	
		}
	} 
	if (isset($_POST['visibility']) && $_POST['visibility']==strval(intval($_POST['visibility'])) && $_POST['visibility']==1) {
		if (isset($_POST['id'])&& $_POST['id']==strval(intval($_POST['id']))) {
			AdminManager::make_available_language($_POST['id']);
			echo 'set_visible';			
		}
	} 	
	exit;
}
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] ='<script type="text/javascript">
 $(document).ready(function() {

 	$(window).load(function () { 
      $(".make_visible_and_invisible").attr("href","javascript:void(0)");
	});
 		
 	$("td .make_visible_and_invisible").click(function () { 
		make_visible="visible.gif";
		make_invisible="invisible.gif";
		id_link_tool=$(this).attr("id");
		id_img_link_tool="img"+id_link_tool;
		path_name_of_imglinktool=$("#"+id_img_link_tool).attr("src");
		link_info_id=id_link_tool.split("linktool_");
		link_id=link_info_id[1];
				
		link_tool_info=path_name_of_imglinktool.split("/");
		my_image_tool=link_tool_info[link_tool_info.length-1];
    

		if (my_image_tool=="visible.gif") {
			path_name_of_imglinktool=path_name_of_imglinktool.replace(make_visible,make_invisible);
			my_visibility=0;
		} else {
			path_name_of_imglinktool=path_name_of_imglinktool.replace(make_invisible,make_visible);
			my_visibility=1;
		}
		
		$.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
				$("#id_content_message").html("<div class=\"normal-message\"><img src=\'/main/inc/lib/javascript/indicator.gif\' /></div>");
			
			},
			type: "POST",
			url: "../admin/languages.php",
			data: "id="+link_id+"&visibility="+my_visibility+"&sent_http_request=1",
			success: function(datos) {
				
				$("#"+id_img_link_tool).attr("src",path_name_of_imglinktool);
				
				if (my_image_tool=="visible.gif") {
					$("#"+id_img_link_tool).attr("alt","'.get_lang('MakeAvailable').'");
					$("#"+id_img_link_tool).attr("title","'.get_lang('MakeAvailable').'");
				} else {
					$("#"+id_img_link_tool).attr("alt","'.get_lang('MakeUnavailable').'");
					$("#"+id_img_link_tool).attr("title","'.get_lang('MakeUnavailable').'");							
				}

				if (datos=="set_visible") {
					$("#id_content_message").html("<div class=\"confirmation-message\">'.get_lang('LanguageIsNowVisible').'</div>");
				} else {
					$("#id_content_message").html("<div class=\"confirmation-message\">'.get_lang('LanguageIsNowHidden').'</div>");					
				}
				
		} }); 		
				
	}); 	

 });
</script>';	
// setting the table that is needed for the styles management (there is a check if it exists later in this code)
$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
$tbl_settings_current 	= Database :: get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

/*
============================================================================== 
		STORING THE CHANGES
============================================================================== 
*/

// we change the availability
if ($_GET['action'] == 'makeunavailable') {

	if (isset($_GET['id']) && $_GET['id']==strval(intval($_GET['id']))) {
		AdminManager::make_unavailable_language($_GET['id']);
	}
}
if ($_GET['action'] == 'makeavailable') {

	if (isset($_GET['id']) && $_GET['id']==strval(intval($_GET['id']))) {
		AdminManager::make_available_language($_GET['id']);
	}
}
if ($_GET['action'] == 'setplatformlanguage') {

	if (isset($_GET['id']) && $_GET['id']==strval(intval($_GET['id']))) {
		AdminManager::set_platform_language($_GET['id']);		
	}

}


if ($_POST['Submit'])
{
	// changing the name
	$sql_update = "UPDATE $tbl_admin_languages SET original_name='{$_POST['txt_name']}' WHERE id='{$_POST['edit_id']}'";
	$result = api_sql_query($sql_update);
	// changing the Platform language
	if ($_POST['platformlanguage'] && $_POST['platformlanguage'] <> '')
	{
		//$sql_update_2 = "UPDATE $tbl_settings_current SET selected_value='{$_POST['platformlanguage']}' WHERE variable='platformLanguage'";
		//$result_2 = api_sql_query($sql_update_2);
		api_set_setting('platformLanguage',$_POST['platformlanguage'],null,null,$_configuration['access_url']);
	}
}
elseif (isset($_POST['action']))
{
	switch ($_POST['action'])
	{
		case 'makeavailable' :
			if (count($_POST['id']) > 0)
			{
				$ids = array ();
				foreach ($_POST['id'] as $index => $id)
				{
					$ids[] = Database::escape_string($id);
				}
				$sql = "UPDATE $tbl_admin_languages SET available='1' WHERE id IN ('".implode("','", $ids)."')";
				api_sql_query($sql,__FILE__,__LINE__);
			}
			break;
		case 'makeunavailable' :
			if (count($_POST['id']) > 0)
			{
				$ids = array ();
				foreach ($_POST['id'] as $index => $id)
				{
					$ids[] = Database::escape_string($id);
				}
				$sql = "UPDATE $tbl_admin_languages SET available='0' WHERE id IN ('".implode("','", $ids)."')";
				api_sql_query($sql,__FILE__,__LINE__);
			}
			break;
	}
}


/*
============================================================================== 
		MAIN CODE
============================================================================== 
*/
// setting the name of the tool
$tool_name = get_lang('PlatformLanguages');

// setting breadcrumbs
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));

// including the header file (which includes the banner itself)
Display :: display_header($tool_name);

// displaying the naam of the tool 
//api_display_tool_title($tool_name);

// displaying the explanation for this tool
echo '<p>'.get_lang('PlatformLanguagesExplanation').'</p>';

// selecting all the languages	
$sql_select = "SELECT * FROM $tbl_admin_languages";
$result_select = api_sql_query($sql_select);

$sql_select_lang = "SELECT * FROM $tbl_settings_current WHERE  category='Languages'";
$result_select_lang = api_sql_query($sql_select_lang,__FILE__,__LINE__);
$row_lang=Database::fetch_array($result_select_lang);
 
/*
--------------------------------------
		DISPLAY THE TABLE
--------------------------------------
*/

// the table data
$language_data = array ();
while ($row = Database::fetch_array($result_select)) {
	
	$row_td = array ();	
	$row_td[] = $row['id'];
	// the first column is the original name of the language OR a form containing the original name
	if ($_GET['action'] == 'edit' and $row['id'] == $_GET['id']) {
		if ($row['english_name'] == api_get_setting('platformLanguage')) {
			$checked = ' checked="checked" ';
		}

		$row_td[] = '<input type="hidden" name="edit_id" value="'.$_GET['id'].'" /><input type="text" name="txt_name" value="'.$row['original_name'].'" /> '
			. '<input type="checkbox" '.$checked.'name="platformlanguage" id="platformlanguage" value="'.$row['english_name'].'" /><label for="platformlanguage">'.$row['original_name'].' '.get_lang('AsPlatformLanguage').'</label> <input type="submit" name="Submit" value="'.get_lang('Ok').'" /><a name="value" />';
	} else 	{
		$row_td[] = $row['original_name'];
	}
	// the second column
	$row_td[] = $row['english_name'];
	// the third column
	$row_td[] = $row['dokeos_folder'];
	
	if ($row['english_name'] == $row_lang['selected_value']){
		$setplatformlanguage = Display::return_icon('links.gif', get_lang('CurrentLanguagesPortal'));
	} else {		
		$setplatformlanguage = "<a href=\"javascript:if (confirm('".addslashes(get_lang('AreYouSureYouWantToSetThisLanguageAsThePortalDefault'))."')) { location.href='".api_get_self()."?action=setplatformlanguage&id=".$row['id']."'; }\">".Display::return_icon('link_na.gif',get_lang('SetLanguageAsDefault'))."</a>";
	}	
	if (api_get_setting('allow_use_sub_language')=='true') {

		$verified_if_is_sub_language=AdminManager::check_if_language_is_sub_language($row['id']);

		if ($verified_if_is_sub_language===false) {
			$verified_if_is_father=AdminManager::check_if_language_is_father($row['id']);
			$allow_use_sub_language = "&nbsp;<a href='new_sub_language.php?action=definenewsublanguage&id=".$row['id']."'>".Display::return_icon('mas.gif', get_lang('CreateSubLanguage'),array('width'=>'22','height'=>'22'))."</a>";		
			if ($verified_if_is_father===true) {
				$allow_add_term_sub_language = "&nbsp;<a href='register_sub_language.php?action=registersublanguage&id=".$row['id']."'>".Display::return_icon('2rightarrow.gif', get_lang('AddWordForTheSubLanguage'),array('width'=>'22','height'=>'22'))."</a>";						
			} else {
				$allow_add_term_sub_language='';
			}
		} else {
			$allow_use_sub_language='';
			$allow_add_term_sub_language='';			
		}
		
	} else {
		$allow_use_sub_language='';
		$allow_add_term_sub_language='';
	}	
	if ($row['available'] == 1) {
		$row_td[] = "<a class=\"make_visible_and_invisible\" id=\"linktool_".$row['id']."\" href='".api_get_self()."?action=makeunavailable&id=".$row['id']."'>".Display::return_icon('visible.gif', get_lang('MakeUnavailable'),array('id'=>'imglinktool_'.$row['id']))."</a> <a href='".api_get_self()."?action=edit&id=".$row['id']."#value'>".Display::return_icon('edit.gif', get_lang('Edit'))."</a>&nbsp;".$setplatformlanguage.$allow_use_sub_language.$allow_add_term_sub_language;
		//$row_td[] = "<a class=\"make_visible_and_invisible\" id=\"linktool_".$row['id']."\" href='javascript:void(0)'>".Display::return_icon('visible.gif', get_lang('MakeUnavailable'),array('id'=>'imglinktool_'.$row['id']))."</a> <a href='".api_get_self()."?action=edit&id=".$row['id']."#value'>".Display::return_icon('edit.gif', get_lang('Edit'))."</a>&nbsp;".$setplatformlanguage.$allow_use_sub_language.$allow_add_term_sub_language;
	} else {
		$row_td[] = "<a class=\"make_visible_and_invisible\" id=\"linktool_".$row['id']."\" href='".api_get_self()."?action=makeavailable&id=".$row['id']."'>".Display::return_icon('invisible.gif', get_lang('MakeAvailable'),array('id'=>'imglinktool_'.$row['id']))."</a> <a href='".api_get_self()."?action=edit&id=".$row['id']."#value'>".Display::return_icon('edit.gif', get_lang('Edit'))."</a>&nbsp;".$setplatformlanguage.$allow_use_sub_language.$allow_add_term_sub_language;
		//$row_td[] = "<a class=\"make_visible_and_invisible\" id=\"linktool_".$row['id']."\" href='javascript:void(0)'>".Display::return_icon('invisible.gif', get_lang('MakeAvailable'),array('id'=>'imglinktool_'.$row['id']))."</a> <a href='".api_get_self()."?action=edit&id=".$row['id']."#value'>".Display::return_icon('edit.gif', get_lang('Edit'))."</a>&nbsp;".$setplatformlanguage.$allow_use_sub_language.$allow_add_term_sub_language;
	}

	$language_data[] = $row_td;
}

$table = new SortableTableFromArrayConfig($language_data, 1, count($language_data));
$table->set_header(0, '');
$table->set_header(1, get_lang('OriginalName'));
$table->set_header(2, get_lang('EnglishName'));
$table->set_header(3, get_lang('DokeosFolder'));
$table->set_header(4, get_lang('Properties'));
$form_actions = array ();
$form_actions['makeavailable'] = get_lang('MakeAvailable');
$form_actions['makeunavailable'] = get_lang('MakeUnavailable');
$table->set_form_actions($form_actions);
echo '<div id="id_content_message">&nbsp;</div>';
$table->display();

/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>