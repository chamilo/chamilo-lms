<?php
/* For licensing terms, see /license.txt */
/**
 * Script for sub-language administration
 * @package chamilo.admin.sub_language
 */
/**
 * Init section
 */
// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
$this_script = 'sub_language';
require_once '../inc/global.inc.php';
require_once 'sub_language.class.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$htmlHeadXtra[] ='<script type="text/javascript">
 $(document).ready(function() {

	$(".save").click(function() {
		button_name=$(this).attr("name");
		button_array=button_name.split("|");
		button_name=button_array[1];
		file_id=button_array[2];
		is_variable_language="$"+button_name;
			
		is_new_language=$("#txtid_"+file_id+"_"+button_name).attr("value");
   		if (is_new_language=="undefined") {
			is_new_language="_";
    	}
		if (is_new_language.length>0 && is_new_language!="_" && file_id!="" && button_name!="") {
			$.ajax({
				contentType: "application/x-www-form-urlencoded",
				beforeSend: function(objeto) {
					$("#div_message_information_id").html("<div class=\"normal-message\"><img src=\'../inc/lib/javascript/indicator.gif\' /></div>");
				},
				type: "POST",
				url: "../admin/sub_language_ajax.inc.php",
				data: "new_language="+is_new_language+"&variable_language="+is_variable_language+"&file_id="+file_id+"&id="+'.intval($_REQUEST['id']).'+"&sub="+'.intval($_REQUEST['sub_language_id']).',
				success: function(datos) { 	
					if (datos == "1") {
						$("#div_message_information_id").html("<div class=\"confirmation-message\">'.get_lang('TheNewWordHasBeenAdded').'</div>");
					} else {
						$("#div_message_information_id").html("<div class=\"warning-message\">" + datos +"</div>");
					}
				} 
			});
		} else {
			$("#div_message_information_id").html("<div class=\"error-message\">'.get_lang('FormHasErrorsPleaseComplete').'</div>");
		}
	});
 		});
</script>';
/**
 * Main code
 */
// setting the name of the tool
$tool_name = get_lang('CreateSubLanguage');
// setting breadcrumbs
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'languages.php', 'name' => get_lang('PlatformLanguages'));

$sublanguage_folder_error = false;

if (isset($_GET['id']) && $_GET['id']==strval(intval($_GET['id']))) {
	$language_name              = SubLanguageManager::get_name_of_language_by_id ($_GET['id']);
	$sub_language_name          = SubLanguageManager::get_name_of_language_by_id ($_GET['sub_language_id']);	
	$all_data_of_language       = SubLanguageManager::get_all_information_of_language($_GET['id']);
	$all_data_of_sublanguage    = SubLanguageManager::get_all_information_of_language($_GET['sub_language_id']);
	$sub_language_file          = api_get_path(SYS_LANG_PATH).$all_data_of_sublanguage['dokeos_folder'];		
	
	if (!file_exists($sub_language_file) || !is_writable($sub_language_file)) {
	    $sublanguage_folder_error = $sub_language_file.' '.get_lang('IsNotWritable');
	}		
	if (SubLanguageManager::check_if_exist_language_by_id($_GET['id'])===true) {		
		$language_id_exist = true;
	} else {
		$language_id_exist = false;
	}
} else {
	$language_name='';
	$language_id_exist=false;
}

$language_name = get_lang('RegisterTermsOfSubLanguageForLanguage').' ( '.strtolower($sub_language_name).' )';
$path_folder = api_get_path(SYS_LANG_PATH).$all_data_of_language['dokeos_folder'];

if (!is_dir($path_folder) || strlen($all_data_of_language['dokeos_folder'])==0) {
	api_not_allowed(true);
}

Display :: display_header($language_name);

echo '<div class="actions-message" >';
echo $language_name;
echo '</div>';

if (!empty($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
} else {
    echo '<br />';
}

$txt_search_word = Security::remove_XSS($_REQUEST['txt_search_word']);
$html.='<div style="float:left" class="actions">';
$html.='<form style="float:left"  id="Searchlanguage" name="Searchlanguage" method="GET" action="sub_language.php">';
$html.='&nbsp;'.get_lang('OriginalName').'&nbsp; :&nbsp;';

$html.='<input name="id" type="hidden"  id="id" value="'.Security::remove_XSS($_REQUEST['id']).'" />';
$html.='<input name="sub_language_id" type="hidden"  id="id" value="'.Security::remove_XSS($_REQUEST['sub_language_id']).'" />';
$html.='<input name="txt_search_word" type="text" size="50"  id="txt_search_word" value="'.Security::remove_XSS($_REQUEST['txt_search_word']).'" />';
$html.="&nbsp;".'<button name="SubmitSearchLanguage" class="search" type="submit">'.get_lang('Search').'</button>';
$html.='</form>';
$html.='</div>';
echo $html;
echo '<br /><br /><br />';
if (!empty($sublanguage_folder_error)) {
    Display::display_warning_message($sublanguage_folder_error);
}
echo '<div id="div_message_information_id">&nbsp;</div>';

/**
 * Search a term in the language
 * @param string the term to search
 * @param bool the search will include the variable definition of the term
 * @param bool the search will include the english language variables
 * @param bool the search will include the parent language variables of the sub language
 * @param bool the search will include the sub language variables
 * @author Julio Montoya
 *
 */
function search_language_term($term, $search_in_variable = true , $search_in_english = true, $search_in_parent = true, $search_in_sub_language= true) {
	//These the $_REQUEST['id'] and the $_REQUEST['sub_language_id'] variables are process in global.inc.php (LOAD LANGUAGE FILES SECTION)
	/*
		These 4 arrays are set in global.inc.php with the condition that will be load from sub_language.php or sub_language_ajax.inc.php
		$english_language_array
		$parent_language_array
		$sub_language_array
		$language_files_to_load
	*/
	//echo '<pre>';
	// array with the list of files to load i.e trad4fall, notification, etc set in global.inc.php

	global $language_files_to_load, $sub_language_array, $english_language_array, $parent_language_array;
	$language_files_to_load_keys = array_flip($language_files_to_load);
	$array_to_search = $parent_language_array;
	$list_info = array();
	//echo '<pre>';
	//print_r($language_files_to_load);
	$term='/'.Security::remove_XSS(trim($_REQUEST['txt_search_word'])).'/i';
	//@todo optimize this foreach
	foreach ($language_files_to_load as $lang_file) {
		//searching in parent language of the sub language
		if ($search_in_parent) {
			$variables = $parent_language_array[$lang_file];
			foreach ($variables as $parent_name_variable =>$parent_variable_value) {
				//arrays are avoided
				if (is_array($parent_variable_value)) {
					continue;
				}
				$founded = false;
				// searching the item in the parent tool
				if (preg_match($term,$parent_variable_value)!==0) {
					$founded = true;
				}
				if ($founded) {
					//loading variable from the english array
					$sub_language_name_variable = $sub_language_array[$lang_file][$parent_name_variable];
					//loading variable from the english array
					$english_name_variable = $english_language_array[$lang_file][$parent_name_variable];

					//config buttons
					/*if (strlen($english_name_variable)>1500) {
						$size =20;
					} else {
						$size =4;
					}*/

					$obj_text='<textarea rows="10" cols="40" name="txt|'.$parent_name_variable.'|'.$language_files_to_load_keys[$lang_file].'" id="txtid_'.$language_files_to_load_keys[$lang_file].'_'.$parent_name_variable.'" >'.$sub_language_name_variable.'</textarea>';
					$obj_button='<button class="save" type="button" name="btn|'.$parent_name_variable.'|'.$language_files_to_load_keys[$lang_file].'" id="btnid_'.$parent_name_variable.'"  />'.get_lang('Save').'</button>';

					$list_info[]=array($lang_file.'.inc.php',
									   $parent_name_variable,
									   $english_name_variable,
									   $parent_variable_value,$obj_text,$obj_button);
				}
			}
		}

		//search in english
		if ($search_in_english || $search_in_variable) {
			$variables = $english_language_array[$lang_file];
			foreach ($variables as $name_variable =>$variable_value) {
				if (is_array($variable_value)) {
					continue;
				}
				
				if (is_array($variable_value))
					echo $lang_file;
				$founded = false;
				if ($search_in_english && $search_in_variable) {
					// searching the item in the parent tool
					if (preg_match($term,$variable_value)!==0 || preg_match($term,$name_variable)!==0 ) {
						$founded = true;
					}
				} else {
					if ($search_in_english) {
						if (preg_match($term,$variable_value)!==0) {
							$founded = true;
						}
					} else {
						if (preg_match($term,$name_variable)!==0) {
							$founded = true;
						}
					}
				}

				if ($founded) {
					//loading variable from the english array
					$sub_language_name_variable = $sub_language_array[$lang_file][$name_variable];
					$parent_variable_value 		= $parent_language_array[$lang_file][$name_variable];
					//config buttons
					$obj_text='<textarea rows="10" cols="40" name="txt|'.$name_variable.'|'.$language_files_to_load_keys[$lang_file].'" id="txtid_'.$language_files_to_load_keys[$lang_file].'_'.$name_variable.'" >'.$sub_language_name_variable.'</textarea>';
					$obj_button='<button class="save" type="button" name="btn|'.$name_variable.'|'.$language_files_to_load_keys[$lang_file].'" id="btnid_'.$name_variable.'"  />'.get_lang('Save').'</button>';

					//loading variable from the english array
					$english_name_variable = $english_language_array[$lang_file][$name_variable];

					$list_info[]=array($lang_file.'.inc.php',
									   $name_variable,
									   $english_name_variable,
									   $parent_variable_value,$obj_text,$obj_button);
				}
			}
		}


		//search in sub language
		if ($search_in_sub_language) {
			$variables = $sub_language_array[$lang_file];
			foreach ($variables as $name_variable =>$variable_value) {
				if (is_array($parent_variable_value)) {
					continue;
				}

				$founded = false;
				// searching the item in the parent tool
				if (preg_match($term,$variable_value)!==0) {
					$founded = true;
				}
				if ($founded) {
					//loading variable from the english array
					$sub_language_name_variable = $sub_language_array[$lang_file][$name_variable];
					$parent_variable_value 		= $parent_language_array[$lang_file][$name_variable];
					//config buttons
					$obj_text='<textarea rows="10" cols="40" name="txt|'.$name_variable.'|'.$language_files_to_load_keys[$lang_file].'" id="txtid_'.$language_files_to_load_keys[$lang_file].'_'.$name_variable.'" >'.$sub_language_name_variable.'</textarea>';
					$obj_button='<button class="save" type="button" name="btn|'.$name_variable.'|'.$language_files_to_load_keys[$lang_file].'" id="btnid_'.$name_variable.'"  />'.get_lang('Save').'</button>';

					//loading variable from the english array
					$english_name_variable = $english_language_array[$lang_file][$name_variable];
					$list_info[]=array($lang_file.'.inc.php',
									   $name_variable,
									   $english_name_variable,
									   $parent_variable_value,$obj_text,$obj_button);
				}
			}
		}
	}

	$list_info = array_unique_dimensional($list_info);
	return $list_info;
}
/**
 * Output
 */
//allow see data in sortetable
if (isset($_REQUEST['txt_search_word'])) {
	//@todo fix to accept a char with 1 char
	if (strlen(trim($_REQUEST['txt_search_word']))>2) {
		$list_info = search_language_term($_REQUEST['txt_search_word'],true, true, true,true);
	}
}

$parameters=array('id'=>intval($_GET['id']),'sub_language_id'=>intval($_GET['sub_language_id']),'txt_search_word'=> $txt_search_word);
$table = new SortableTableFromArrayConfig($list_info, 1,20,'data_info');
$table->set_additional_parameters($parameters);
//$table->set_header(0, '');
$table->set_header(0, get_lang('LanguageFile'));
$table->set_header(1, get_lang('LanguageVariable'));
$table->set_header(2, get_lang('EnglishName'));
$table->set_header(3, get_lang('OriginalName'));
$table->set_header(4, get_lang('SubLanguage'),false);
$table->set_header(5, get_lang('Edit'),false);
$table->display();

/*	FOOTER	*/
Display :: display_footer();
