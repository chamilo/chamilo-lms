<?php
/* For licensing terms, see /dokeos_license.txt */
/**
 * This script allows for the addition of sub-languages
 * @package chamilo.admin
 */
/**
 * Initialization section
 */
// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'sub_language.class.php';
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

/**
 *		MAIN CODE
 */
// setting the name of the tool
$tool_name = get_lang('CreateSubLanguage');

// setting breadcrumbs
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'languages.php', 'name' => get_lang('PlatformLanguages'));

require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

/**
 * Add sub-language
 * @param   string  Original language name (Occitan, Wallon, Vlaams)
 * @param   string  English language name (occitan, wallon, flanders)
 * @param   string  ISO code (fr_FR, ...)
 * @param   int     Whether the sublanguage is published (0=unpublished, 1=published)
 * @param   int     ID del idioma padre
 * @return  int     New sub language ID or false on error
 */
function add_sub_language ($original_name,$english_name,$isocode,$sublanguage_available,$parent_id) {
    $tbl_admin_languages    = Database :: get_main_table(TABLE_MAIN_LANGUAGE);
    $original_name          = Database::escape_string($original_name);
    $english_name           = Database::escape_string($english_name);
    $isocode                = Database::escape_string($isocode);
    $sublanguage_available  = Database::escape_string($sublanguage_available);
    $parent_id              = Database::escape_string($parent_id);
    
    $sql='INSERT INTO '.$tbl_admin_languages.'(original_name,english_name,isocode,dokeos_folder,available,parent_id) VALUES ("'.$original_name.'","'.$english_name.'","'.$isocode.'","'.$english_name.'","'.$sublanguage_available.'","'.$parent_id.'")';
    $res = Database::query($sql);
    if ($res === false) {
        return false;
    }
    return Database::insert_id();
}

/**
 * Check if language exists
 * @param   string  Original language name (Occitan, Wallon, Vlaams)
 * @param   string  English language name (occitan, wallon, flanders)
 * @param   string  ISO code (fr_FR, ...)
 * @param   int     Whether the sublanguage is published (0=unpublished, 1=published)
 * @return  array   Array describing the number of items found that match the
 *                  current language insert attempt (original_name => true,
 *                  english_name => true, isocode => true,
 *                  execute_add => true/false). If execute_add is true, then we
 *                  can proceed.
 * @todo This function is not transaction-safe and should probably be included
 *       inside the add_sub_language function.
 */
function check_if_language_exist ($original_name, $english_name, $isocode, $sublanguage_available) {
	$tbl_admin_languages 	= Database :: get_main_table(TABLE_MAIN_LANGUAGE);
	$sql_original_name='SELECT count(*) AS count_original_name FROM '.$tbl_admin_languages.' WHERE original_name="'.Database::escape_string($original_name).'" ';
	$sql_english_name='SELECT count(*) AS count_english_name FROM '.$tbl_admin_languages.' WHERE english_name="'.Database::escape_string($english_name).'" ';
	//$sql_isocode='SELECT count(*) AS count_isocode FROM '.$tbl_admin_languages.' WHERE isocode="'.Database::escape_string($isocode).'" ';
	$rs_original_name=Database::query($sql_original_name);
	$rs_english_name=Database::query($sql_english_name);
	//$rs_isocode=Database::query($sql_isocode);
	$count_original_name=Database::result($rs_original_name,0,'count_original_name');
	$count_english_name=Database::result($rs_english_name,0,'count_english_name');
	//$count_isocode=Database::result($rs_isocode,0,'count_isocode');
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
	
	$iso_list = api_get_platform_isocodes();	
	$iso_list = array_values($iso_list);
	
	if (!in_array($isocode, $iso_list)) {
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
 * Check if language exist, given its ID. This is just a wrapper for the
 * SubLanguageManager::check_if_exist_language_by_id() method and should not exist
 * @param   int     Language ID
 * @return  bool
 * @todo    deprecate this function and use the static method directly
 */
function check_if_exist_language_by_id ($language_id) {
	return SubLanguageManager::check_if_exist_language_by_id($language_id);
}
/**
 * Check if the given language is a parent of any sub-language
 * @param   int     Language ID of the presumed parent
 * @return  bool    True if this language has children, false otherwise
 */
function ckeck_if_is_parent_of_sub_language ($parent_id) {
	$sql='SELECT count(*) AS count FROM language WHERE parent_id="'.Database::escape_string($parent_id).'"';
	$rs=Database::query($sql);
	if (Database::num_rows($rs)>0 && Database::result($rs,0,'count')==1) {
		return true;
	} else {
		return false;
	}
}
/**
 * Get all information of sub-language
 * @param   int     Parent language ID
 * @param   int     Child language ID
 * @return  array
 */
function allow_get_all_information_of_sub_language ($parent_id,$sub_language_id) {
	return SubLanguageManager::get_all_information_of_sub_language($parent_id,$sub_language_id);
}
/*end declare functions*/

//add data

if (isset($_GET['sub_language_id']) && $_GET['sub_language_id']==strval(intval($_GET['sub_language_id']))) {
	$language_name=SubLanguageManager::get_name_of_language_by_id($_GET['sub_language_id']);
		if (check_if_exist_language_by_id ($_GET['sub_language_id'])===true) {
			$sub_language_id=$_GET['sub_language_id'];
			$sub_language_id_exist=true;
		} else {
			$sub_language_id_exist=false;
		}

}

if (isset($_GET['id']) && $_GET['id']==strval(intval($_GET['id']))) {
	$language_name=SubLanguageManager::get_name_of_language_by_id($_GET['id']);
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

$msg = '';

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
			$msg .= Display::return_message(get_lang('AlreadyExists').' "'.get_lang('OriginalName').'" '.'('.$original_name.')','error');
		}
		if ($index_information=='english_name') {
			$msg .= Display::return_message(get_lang('AlreadyExists').' "'.get_lang('EnglishName').'" '.'('.$english_name.')','error');
		}
		if ($index_information=='isocode') {
			$msg .= Display::return_message(get_lang('CodeDoesNotExists').': '.$isocode.'','error');
		}
		if ($index_information=='execute_add' && $value_information===true) {
			$allow_insert_info=true;
		}
	}

	if (strlen($original_name)>0 && strlen($english_name)>0 && strlen($isocode)>0) {
		if ($allow_insert_info===true && $language_id_exist===true) {
			$english_name=str_replace(' ','_',$english_name);
                        //Fixes BT#1636
                        $english_name=api_strtolower($english_name);
            
			$isocode=str_replace(' ','_',$isocode);
			$str_info='<br/>'.get_lang('OriginalName').' : '.$original_name.'<br/>'.get_lang('EnglishName').' : '.$english_name.'<br/>'.get_lang('PlatformCharsetTitle').' : '.$isocode;

			$mkdir_result=SubLanguageManager::add_language_directory($english_name);
			if ($mkdir_result) {
			  	$sl_id = add_sub_language($original_name,$english_name,$isocode,$sublanguage_available,$parent_id);
                                if ($sl_id === false) {
                                    SubLanguageManager::remove_language_directory($english_name);
                                    $msg .= Display::return_message(get_lang('LanguageDirectoryNotWriteableContactAdmin'),'error');
                                } else {
                                    // Here we build the confirmation message and we send the user to the sub language terms definition page, using a little hack - see #3712
                                    $_SESSION['msg'] = Display::return_message(get_lang('TheNewSubLanguageHasBeenAdded').$str_info.$link,'confirm',false);
                                    unset($interbreadcrumb);
                                    $_GET['sub_language_id'] = $_REQUEST['sub_language_id'] = $sl_id;
                                    require 'sub_language.php';
                                    exit();
                                }
			} else {
			    $msg .= Display::return_message(get_lang('LanguageDirectoryNotWriteableContactAdmin'),'error');
			}
		} else {
			if ($language_id_exist===false) {
				$msg .= Display::return_message(get_lang('LanguageParentNotExist'),'error');
			}
		}
	} else {
            $msg .= Display::return_message(get_lang('FormHasErrorsPleaseComplete'),'error');
	}
}

Display :: display_header($language_name);

echo $msg;

if (isset($_POST['SubmitAddDeleteLanguage'])) {
	$rs = SubLanguageManager::remove_sub_language($english_name);
	if ($rs===true) {
		Display::display_confirmation_message(get_lang('TheSubLanguageHasBeenRemoved'));
	} else {
		Display::display_error_message(get_lang('TheSubLanguageHasNotBeenRemoved'));
	}
}
// ckeck_if_is_parent_of_sub_language($parent_id)===false
//
if (isset($_GET['action']) && $_GET['action']=='definenewsublanguage') {
	$text = $language_name;
	$form = new FormValidator('addsublanguage', 'post', 'sub_language_add.php?id='.Security::remove_XSS($_GET['id']).'&action=definenewsublanguage');
	$class='add';
	$form->addElement('header', '', $text);
	$form->addElement('text', 'original_name', get_lang('OriginalName'),'class="input_titles"');
	$form->addRule('original_name', get_lang('ThisFieldIsRequired'), 'required');
	$form->addElement('text', 'english_name', get_lang('EnglishName'),'class="input_titles"');
	$form->addRule('english_name', get_lang('ThisFieldIsRequired'), 'required');
	$form->addElement('text', 'isocode', get_lang('ISOCode'), 'class="input_titles"');
	
	$form->addRule('isocode', get_lang('ThisFieldIsRequired'), 'required');
	$form->addElement('static', null, '&nbsp;', '<i>en, es, fr</i>');
	$form->addElement('checkbox', 'sub_language_is_visible', '', get_lang('Visibility'));
	$form->addElement('style_submit_button', 'SubmitAddNewLanguage', get_lang('CreateSubLanguage'), 'class="'.$class.'"');
        $values['isocode'] = 'es';
        $form->setDefaults($values);
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
		Display::display_normal_message(get_lang('TheSubLanguageForThisLanguageHasBeenAdded'));
	}
}
/**
 * Footer
 */
Display :: display_footer();