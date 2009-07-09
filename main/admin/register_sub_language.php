<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
	@author Isaac flores - Added 9 july of 2009
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'admin.class.php';
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] ='<script type="text/javascript">
 $(document).ready(function() {
 
	$("#sl_original_file").change(function () { 
	
		current_action=$("#Loadlanguage").attr("action");
		current_action=current_action+"&original_file="+$(this).attr("value")
		$("#Loadlanguage").attr("action",current_action);
	
	 }); 
	
	$(window).load(function () { 
		/*current_action=$("#Loadlanguage").attr("action");
		current_action=current_action+"&original_file="+$("#sl_original_file").attr("value")
		$("#Loadlanguage").attr("action",current_action);*/
	});

 	$("#sl_original_file option[@value='.Security::remove_XSS(($_REQUEST['original_file'])).']").attr("selected","selected"); 

	$(".save").click(function() {
		
		button_name=$(this).attr("name");	
		button_name=button_name.split("_");
		button_name=button_name[1];
		is_id=$("#id_hidden_original_file").attr("value");
		is_variable_language="$"+button_name;
		is_new_language=$("#txtid_"+button_name).attr("value");
		if (is_new_language=="undefined") {
			is_new_language="_";
		}
		is_file_language="'.Security::remove_XSS(($_REQUEST['original_file'])).'";
		if (is_new_language.length>0 && is_new_language!="_") {
			$.ajax({
				contentType: "application/x-www-form-urlencoded",
				beforeSend: function(objeto) {
					$("#div_message_information_id").html("<div class=\"normal-message\"><img src=\'../inc/lib/javascript/indicator.gif\' /></div>");
				
				},
				type: "POST",
				url: "../admin/add_by_ajax_sub_language.inc.php",
				data: "new_language="+is_new_language+"&variable_language="+is_variable_language+"&file_language="+is_file_language+"&id="+is_id,
				success: function(datos) {
					$("#div_message_information_id").html("<div class=\"confirmation-message\">'.get_lang('TheNewWordHasBeenAdded').'</div>");
				
			} }); 
		} else {
			$("#div_message_information_id").html("<div class=\"error-message\">'.get_lang('FormHasErrorsPleaseComplete').'</div>");
		}
				

	});

 		});
</script>';	
/*
============================================================================== 
		MAIN CODE
============================================================================== 
*/
// setting the name of the tool
$tool_name = get_lang('CreateSubLanguage');

// setting breadcrumbs  
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'languages.php', 'name' => get_lang('PlatformLanguages'));

require_once api_get_path(LIBRARY_PATH).'text.lib.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

/*declare functions*/
/**
 * Get name of language by id
 */
function get_name_of_language_by_id ($language_id) {
	return AdminManager::get_name_of_language_by_id($language_id);
}
function check_if_exist_language_by_id ($language_id) {
	return AdminManager::check_if_exist_language_by_id($language_id); 
}
/*end declare functions*/


if (isset($_GET['id']) && $_GET['id']==strval(intval($_GET['id']))) {
	$language_name=get_name_of_language_by_id($_GET['id']);
	$all_data_of_language=AdminManager::get_all_information_of_language($_GET['id']);
	$my_language=$language_name;
	if (check_if_exist_language_by_id ($_GET['id'])===true) {
		$parent_id=$_GET['id'];
		$language_id_exist=true;
	} else {
		$language_id_exist=false;
	}
} else {
	$language_name='';
	$language_id_exist=false;	
}


$language_name=get_lang('RegisterTermsOfSubLanguageForLanguage').' ( '.strtolower($language_name).' )';

// including the header file (which includes the banner itself)

//ADD ALL SUB LANGUAGE
if (isset($_POST['action']) && $_POST['action']=='addsublanguage') {
	/*$all_data_of_sub_language=AdminManager::get_all_information_of_sub_language($_POST['code_language_id']);
	$dokeos_sub_language_path_folder=api_get_path('SYS_LANG_PATH').$all_data_of_sub_language['dokeos_folder'].'/'.Security::remove_XSS($_REQUEST['original_file']);
	$all_file_of_directory=AdminManager::get_all_language_variable_in_file($dokeos_sub_language_path_folder);
	//AdminManager::add_file_in_language_directory ($dokeos_path_folder);

	foreach ($_POST['id'] as $index_post_id =>$value_post_id) {
		foreach ($_POST as $index_post_txt =>$value_post_txt) {
			if (is_string($index_post_txt) && strlen($index_post_txt)>4) {
				if ($value_post_id==substr($index_post_txt,4,strlen($index_post_txt))) {
					$new_language=$value_post_txt;
					$index_variable_of_sub_language='$'.$index_post_txt;
					$all_file_of_directory[$index_variable_of_sub_language]="\"".$new_language."\";";
				}
			}
		}
	}
*/
	//var_dump($all_file_of_directory);
	//update variable language
	
	
	
	/*foreach ($all_file_of_directory as $key_value=>$value_info) {
		AdminManager::write_data_in_file ($dokeos_path_folder,$value_info,$key_value);
	}*/
}
//END ALL SUB LANGUAGE

$dokeos_path_folder=api_get_path('SYS_LANG_PATH').$all_data_of_language['dokeos_folder'];
//get file name example : forum.inc.php,gradebook.inc.php

if (!is_dir($dokeos_path_folder) || strlen($all_data_of_language['dokeos_folder'])==0) {
	api_not_allowed(true);
}

Display :: display_header($language_name);

$all_file_of_directory=AdminManager::get_all_data_of_dokeos_folder ($dokeos_path_folder);
$load_array_in_select=array();
sort($all_file_of_directory);
foreach ($all_file_of_directory as $value_all_file_of_directory) {
	$load_array_in_select[$value_all_file_of_directory]=$value_all_file_of_directory;
}

$request_file='';

if (isset($_POST['original_file']) && $_POST['original_file']!='') {
	$request_file=Security::remove_XSS($_POST['original_file']);
} 
if (isset($_GET['original_file']) && $_GET['original_file']!='') {
	$request_file=Security::remove_XSS($_GET['original_file']);
}

$form = new FormValidator('Loadlanguage', 'post', 'register_sub_language.php?id='.Security::remove_XSS($_GET['id']).'&original_file='.$request_file);
$class='add';
$form->addElement('header', '', $language_name);			
$form->addElement('select', 'original_file', get_lang('File'),$load_array_in_select,array('id'=>'sl_original_file'));
$form->addElement('hidden','id_hidden_original_file',Security::remove_XSS($_REQUEST['id']),array('id'=>'id_hidden_original_file'));
$form->addElement('style_submit_button', 'SubmitLoadLanguage', get_lang('LoadLanguageFile'), 'class="'.$class.'"');
$form->display();
echo '<br/>';

//id
echo '<div id="div_message_information_id">&nbsp;</div>';
echo '<div class="actions"><strong>';
echo get_lang('AddTermsOfThisSubLanguage');
echo '</strong></div>';


//allow see data in sortetable
if ($_REQUEST['original_file']) {

	$parent_id=Security::remove_XSS($_REQUEST['id']);
	$get_all_info_of_sub_language=AdminManager::get_all_information_of_sub_language ($parent_id);
	$dokeos_path_file=api_get_path('SYS_LANG_PATH').$all_data_of_language['dokeos_folder'].'/'.$request_file;	

	$dokeos_english_path_file=api_get_path('SYS_LANG_PATH').'english/'.$request_file;
	$dokeos_sub_language_path_file=api_get_path('SYS_LANG_PATH').$get_all_info_of_sub_language['dokeos_folder'].'/'.$request_file;		
	if (file_exists($dokeos_sub_language_path_file)) {
		$sub_language_exist=true;
	} else {
		$sub_language_exist=false;
	}

	$all_language_variable=AdminManager::get_all_language_variable_in_file ($dokeos_path_file);
	$all_english_language_variable=AdminManager::get_all_language_variable_in_file($dokeos_english_path_file);
	if ($sub_language_exist===true) {
		$get_all_sub_language_variable=AdminManager::get_all_language_variable_in_file($dokeos_sub_language_path_file);
	}
	$i=0;
	foreach ($all_language_variable as $index_language_variable =>$value_language_variable) {
		$use_field_name=substr($index_language_variable,1);
		
		if ($sub_language_exist===true) {
			foreach ($get_all_sub_language_variable as $index_get_all_sub_language_variable =>$value_get_all_sub_language_variable) {
				if ($index_get_all_sub_language_variable==$index_language_variable) {
					$value_sub_language=$value_get_all_sub_language_variable;
					break;
				} else {
					$value_sub_language='';
				}
			}
		}
		$value_sub_language=strlen($value_sub_language)>0 ? $value_sub_language : '';
		
		$obj_text='<input name="txt_'.$use_field_name.'" id="txtid_'.$use_field_name.'" value="'.substr($value_sub_language,1,(strlen($value_sub_language)-3)).'">';
		
		$obj_button='<button class="save" type="button" name="btn_'.$use_field_name.'" id="btnid_'.$use_field_name.'"  />'.get_lang('Save').'</button>';		
		
		$new_element_html=='<input type="hidden" name="code_language_id" id="code_language_id" value="'.Security::remove_XSS($_GET['id']).'" />';
		
		if ($i==0) {
			$obj_button=$obj_button.$new_element_html;
		} else {
			$obj_button=$obj_button;
		}
		foreach ($all_english_language_variable as $index_english_language_variable =>$value_english_language_variable) {
			if ($index_english_language_variable==$index_language_variable) {
				$add_english_language_in_array=$value_english_language_variable;
				break;
			}
		}
		//FIRST OPTION substr($index_language_variable,1,strlen($index_language_variable)),
		$list_info[]=array($index_language_variable,substr($add_english_language_in_array,1,(strlen($add_english_language_in_array)-3)),substr($value_language_variable,1,(strlen($value_language_variable)-3)),$obj_text,$obj_button);
	$i++;
	}
}


$parameters=array('id'=>Security::remove_XSS($_GET['id']),'original_file'=>$request_file);
$table = new SortableTableFromArrayConfig($list_info, 1,20,'data_info');
$table->set_additional_parameters($parameters);
//$table->set_header(0, '');	
$table->set_header(0, get_lang('LanguageVariable'));
$table->set_header(1, get_lang('EnglishName'));
$table->set_header(2, get_lang('OriginalName'));
$table->set_header(3, get_lang('SubLanguage'));
$table->set_header(4, get_lang('Register'));
/*$form_actions = array ();
$form_actions['addsublanguage'] = get_lang('AddSubLanguage');
$table->set_form_actions($form_actions);*/

$table->display();

/*
==============================================================================
		FOOTER 
==============================================================================
*/
		
Display :: display_footer();
?>