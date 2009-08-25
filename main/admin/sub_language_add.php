<?php
/* For licensing terms, see /dokeos_license.txt */
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'sub_language.class.php';
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

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
 * Add sub-language
 */
function add_sub_language ($original_name,$english_name,$isocode,$sublanguage_available,$parent_id) {
	$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
	$sql='INSERT INTO '.$tbl_admin_languages.'(original_name,english_name,isocode,dokeos_folder,available,parent_id) VALUES ("'.api_htmlentities($original_name).'","'.$english_name.'","'.$isocode.'","'.$english_name.'","'.$sublanguage_available.'","'.$parent_id.'")';
	Database::query($sql,__FILE__,__LINE__);
}
/**
 * check if language exists
 */
function check_if_language_exist ($original_name,$english_name,$isocode,$sublanguage_available) {
	$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
	$sql_original_name='SELECT count(*) AS count_original_name FROM '.$tbl_admin_languages.' WHERE original_name="'.Database::escape_string(api_htmlentities($original_name)).'" ';
	$sql_english_name='SELECT count(*) AS count_english_name FROM '.$tbl_admin_languages.' WHERE english_name="'.Database::escape_string($english_name).'" ';
	$sql_isocode='SELECT count(*) AS count_isocode FROM '.$tbl_admin_languages.' WHERE isocode="'.Database::escape_string($isocode).'" ';
	$rs_original_name=Database::query($sql_original_name,__FILE__,__LINE__);
	$rs_english_name=Database::query($sql_english_name,__FILE__,__LINE__);
	$rs_isocode=Database::query($sql_isocode,__FILE__,__LINE__);
	$count_original_name=Database::result($rs_original_name,0,'count_original_name');
	$count_english_name=Database::result($rs_english_name,0,'count_english_name');
	$count_isocode=Database::result($rs_isocode,0,'count_isocode');
	$has_error=false;
	$message_information=array();

	if ($count_original_name==1) {
		$has_error=true;
		$message_information['original_name']=true;
	} 
	if ($count_english_name==1) {
		$has_error=true;
		$message_information['english_name']=true;
	} 
	if ($count_isocode==1) {
		$has_error=true;
		$message_information['isocode']=true;
	} 
	if ($has_error===true) {
		$message_information['execute_add']=false;
	} 
	if ($has_error===false) {
		$message_information['execute_add']=true;
	}

	return $message_information;
}

/**
 * get name of language by id
 */
function get_name_of_language_by_id ($language_id) {
	return SubLanguageManager::get_name_of_language_by_id($language_id);
}
/**
 * check if language exist by id
 */
function check_if_exist_language_by_id ($language_id) {
	return SubLanguageManager::check_if_exist_language_by_id($language_id); 
}
/**
 * check if is parent of sub-language
 */
function ckeck_if_is_parent_of_sub_language ($parent_id) {
	$sql='SELECT count(*) AS count FROM language WHERE parent_id="'.Database::escape_string($parent_id).'"';
	$rs=Database::query($sql,__FILE__,__LINE__);
	if (Database::num_rows($rs)>0 && Database::result($rs,0,'count')==1) {
		return true;	
	} else {
		return false;
	}	
}
/**
 * Get all information of sub-language
 */
function allow_get_all_information_of_sub_language ($parent_id,$sub_language_id) {
	return SubLanguageManager::get_all_information_of_sub_language($parent_id,$sub_language_id); 
}

/**
 * Add directory for sub-language 
 */
function add_directory_of_sub_language ($path_sub_language) {
	return SubLanguageManager::add_directory_of_sub_language($path_sub_language);
}
/**
 * Remove directory of sub-language
 */
function remove_directory_of_sub_language ($path) {
	$content=SubLanguageManager::get_all_data_of_dokeos_folder($path);

	if (count($content)>0) {
		foreach ($content as $value_content) {
			$path_file=$path.'/'.$value_content;
			unlink($path_file);
		}
		$rs=@rmdir($path);
		if ($rs===true) {
			return true;
		} else {
			return false;
		}
	} else {
		$rs=@rmdir($path);
		if ($rs===true) {
			return true;
		} else {
			return false;
		}
	}
		
}
/*end declare functions*/

//add data

if (isset($_GET['sub_language_id']) && $_GET['sub_language_id']==strval(intval($_GET['sub_language_id']))) {
	$language_name=get_name_of_language_by_id($_GET['sub_language_id']);
		if (check_if_exist_language_by_id ($_GET['sub_language_id'])===true) {
			$sub_language_id=$_GET['sub_language_id'];
			$sub_language_id_exist=true;
		} else {
			$sub_language_id_exist=false;
		}
		
}

if (isset($_GET['id']) && $_GET['id']==strval(intval($_GET['id']))) {
	$language_name=get_name_of_language_by_id($_GET['id']);
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

//removed and register

if ((isset($_GET['id']) && $_GET['id']==strval(intval($_GET['id']))) && (isset($_GET['sub_language_id']) && $_GET['sub_language_id']==strval(intval($_GET['sub_language_id'])))) {
	if (check_if_exist_language_by_id($_GET['id'])===true && check_if_exist_language_by_id($_GET['sub_language_id'])===true) {
	 	$get_all_information=allow_get_all_information_of_sub_language ($_GET['id'],$_GET['sub_language_id']);
		$original_name=$get_all_information['original_name'];
		$english_name=$get_all_information['english_name'];
		$isocode=$get_all_information['isocode'];
		
	} 
}

$language_name=get_lang('CreateSubLanguageForLanguage').' ( '.strtolower($language_name).' )';

if (ckeck_if_is_parent_of_sub_language ($parent_id)===true && isset($_GET['action']) && $_GET['action']=='deletesublanguage') {
	$language_name=get_lang('DeleteSubLanguage');
}

Display :: display_header($language_name);

if (isset($_POST['SubmitAddNewLanguage'])) {
	$original_name=$_POST['original_name'];
	$english_name=$_POST['english_name'];
	$isocode=$_POST['isocode'];
	$english_name=str_replace(' ','_',$english_name);
	$isocode=str_replace(' ','_',$isocode);
			
	$sublanguage_available=$_POST['sub_language_is_visible'];
	$check_information=array();	
	$check_information=check_if_language_exist($original_name,$english_name,$isocode,$sublanguage_available);
	foreach ($check_information as $index_information => $value_information) {
		$allow_insert_info=false;
		if ($index_information=='original_name') {
			Display::display_error_message(get_lang('AlreadyExists').' "'.get_lang('OriginalName').'" '.'('.$original_name.')');
		}
		if ($index_information=='english_name') {
			Display::display_error_message(get_lang('AlreadyExists').' "'.get_lang('EnglishName').'" '.'('.$english_name.')');			
		}
		if ($index_information=='isocode') {
			Display::display_error_message(get_lang('AlreadyExists').' "'.get_lang('PlatformCharsetTitle').'" '.'('.$isocode.')');			
		}	
		if ($index_information=='execute_add' && $value_information===true) {
			$allow_insert_info=true;
		}

	}
	
	if (strlen($original_name)>0 && strlen($english_name)>0 && strlen($isocode)>0) {
		if ($allow_insert_info===true && $language_id_exist===true) {
			$english_name=str_replace(' ','_',$english_name);
			$isocode=str_replace(' ','_',$isocode);			
			$str_info='<br/>'.get_lang('OriginalName').' : '.$original_name.'<br/>'.get_lang('EnglishName').' : '.$english_name.'<br/>'.get_lang('PlatformCharsetTitle').' : '.$isocode;
			$path=api_get_path('SYS_LANG_PATH').$english_name;
			
			$mkdir_result=add_directory_of_sub_language($path);
			if ($mkdir_result===true) {
			  	add_sub_language($original_name,$english_name,$isocode,$sublanguage_available,$parent_id);
			  	Display::display_confirmation_message(get_lang('TheNewSubLanguageHasBeenAdd').$str_info);
			} else {
				  Display::display_error_message(get_lang('LanguageDirectoryNotWriteableContactAdmin'));			
			}
		} else {
			if ($language_id_exist===false) {
				Display::display_error_message(get_lang('LanguageParentNotExist'));	
			}	
		}
	} else {
			Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'));		
	}
}
if (isset($_POST['SubmitAddDeleteLanguage'])) {
	$path=api_get_path('SYS_LANG_PATH').$english_name;
	if (is_dir($path)) {
		$rs=remove_directory_of_sub_language($path);
		if ($rs===true) {
			SubLanguageManager::removed_sub_language($parent_id,$sub_language_id);
			Display::display_confirmation_message(get_lang('TheSubLanguageHasBeenRemoved'));
		}
		
	}
}
     // ckeck_if_is_parent_of_sub_language($parent_id)===false
	//
	if (isset($_GET['action']) && $_GET['action']=='definenewsublanguage') {
		$text=$language_name;
		$form = new FormValidator('addsublanguage', 'post', 'sub_language_add.php?id='.Security::remove_XSS($_GET['id']).'&action=definenewsublanguage');
		$class='add';
		$form->addElement('header', '', $text);			
		$form->addElement('text', 'original_name', get_lang('OriginalName'),'class="input_titles"');
		$form->addRule('original_name', get_lang('ThisFieldIsRequired'), 'required');	
		$form->addElement('text', 'english_name', get_lang('EnglishName'),'class="input_titles"');
		$form->addRule('english_name', get_lang('ThisFieldIsRequired'), 'required');	
		$form->addElement('text', 'isocode', get_lang('PlatformCharsetTitle'),'class="input_titles"');
		$form->addRule('isocode', get_lang('ThisFieldIsRequired'), 'required');	
		$form->addElement('checkbox', 'sub_language_is_visible', '', get_lang('Visibility'));	
		$form->addElement('style_submit_button', 'SubmitAddNewLanguage', get_lang('CreateSubLanguage'), 'class="'.$class.'"');
		$form->display();
	} else {
		if (isset($_GET['action']) && $_GET['action']=='deletesublanguage') {
			$text=$language_name;
			$form = new FormValidator('deletesublanguage', 'post', 'sub_language_add.php?id='.Security::remove_XSS($_GET['id']).'&sub_language_id='.Security::remove_XSS($_GET['sub_language_id']));
			$class='minus';
			$form->addElement('header', '', $text);
			$form->addElement('static', '', get_lang('OriginalName'),$original_name);
			$form->addElement('static', '', get_lang('EnglishName'),$english_name);
			$form->addElement('static', '', get_lang('PlatformCharsetTitle'),$isocode);				
			$form->addElement('style_submit_button', 'SubmitAddDeleteLanguage', get_lang('DeleteSubLanguage'), 'class="'.$class.'"');
			$form->display();		
		}
		if (isset($_GET['action']) && $_GET['action']=='definenewsublanguage') {
			Display::display_normal_message(get_lang('TheSubLanguageForThisLanguageHasBeenAdd'));
		}
	}
	
/*
==============================================================================
		FOOTER 
==============================================================================
*/	
Display :: display_footer();
?>