<?php
/**
 * sysem base config setting
 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
 * @link www.phpletter.com
 * @since 1/August/2007
 *
 *
 * Modify system config setting for Chamilo
 * @author Juan Carlos Raña Trabado
 * @since 31/December/2008
 */

//error_reporting(E_ALL);
//error_reporting(E_ALL ^ E_NOTICE);

//Access Control Setting
/**
* turn off => false
* by session => true
*/
define('CONFIG_ACCESS_CONTROL_MODE', false);
define("CONFIG_LOGIN_USERNAME", 'ajax');
define('CONFIG_LOGIN_PASSWORD', '123456');
define('CONFIG_LOGIN_PAGE', 'ajax_login.php'); //the url to the login page

//SYSTEM MODE CONFIG
/**
* turn it on when you have this system for demo purpose
*  that means changes made to each image is not physically applied to it
*  and all uploaded files/created folders will be removed automatically
*/
define('CONFIG_SYS_DEMO_ENABLE', false);
define('CONFIG_SYS_VIEW_ONLY', false); //diabled the system, view only
define('CONFIG_SYS_THUMBNAIL_VIEW_ENABLE', true);//REMOVE THE thumbnail view if false

//User Permissions
//Hack by Juan Carlos Raña Trabado
if(empty($_course['path'])) {
	define('CONFIG_OPTIONS_DELETE', true);
	define('CONFIG_OPTIONS_CUT', true);
	define('CONFIG_OPTIONS_COPY', true);
	define('CONFIG_OPTIONS_NEWFOLDER', true);
	define('CONFIG_OPTIONS_RENAME', true);
	define('CONFIG_OPTIONS_UPLOAD', true);
	define('CONFIG_OPTIONS_EDITABLE', false); //disable image editor and text editor
} else {
	
	if(api_is_allowed_to_edit()) {
		//api_is_allowed_to_edit() from Chamilo
		define('CONFIG_OPTIONS_DELETE', true);
		define('CONFIG_OPTIONS_CUT', true);
		define('CONFIG_OPTIONS_COPY', true);
		define('CONFIG_OPTIONS_NEWFOLDER', true);
		define('CONFIG_OPTIONS_RENAME', false);
		define('CONFIG_OPTIONS_UPLOAD', true);
		define('CONFIG_OPTIONS_EDITABLE', false); //disable image editor and text editor
	} else {
		define('CONFIG_OPTIONS_DELETE', true);
		define('CONFIG_OPTIONS_CUT', true);
		define('CONFIG_OPTIONS_COPY', true);
		define('CONFIG_OPTIONS_NEWFOLDER', true);
		define('CONFIG_OPTIONS_RENAME', false);
		define('CONFIG_OPTIONS_UPLOAD', true);
		define('CONFIG_OPTIONS_EDITABLE', false); //disable image editor and text editor
	}
}


//FILESYSTEM CONFIG
/*
* CONFIG_SYS_DEFAULT_PATH is the default folder where the files would be uploaded to
and it must be a folder under the CONFIG_SYS_ROOT_PATH or the same folder
these two paths accept relative path only, don't use absolute path
*/
//define('CONFIG_SYS_DEFAULT_PATH', '../uploaded/'); //accept relative path only
//define('CONFIG_SYS_ROOT_PATH', '../uploaded/');	//accept relative path only

/////////////// Integration for Chamilo

if(!empty($_course['path'])) {
	if(!empty($group_properties['directory'])) {
		$PathChamiloAjaxFileManager='../../../../../../../courses/'.$_course['path'].'/document'.$group_properties['directory'].'/';
	} else {
		if(api_is_allowed_to_edit()) {
			$PathChamiloAjaxFileManager='../../../../../../../courses/'.$_course['path'].'/document/';
		} else {
			$current_session_id = api_get_session_id();
			if($current_session_id==0) {
				$PathChamiloAjaxFileManager='../../../../../../../courses/'.$_course['path'].'/document/shared_folder/sf_user_'.api_get_user_id().'/';
			} else {
				$PathChamiloAjaxFileManager='../../../../../../../courses/'.$_course['path'].'/document/shared_folder_session_'.$current_session_id.'/sf_user_'.api_get_user_id().'/';
			}
		}
	}
} else {
	if (api_is_platform_admin() && $_SESSION['this_section']=='platform_admin') {
		//home page portal
		$PathChamiloAjaxFileManager='../../../../../../../home/default_platform_document/';
	} else {
		//my profile
		$my_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'none');
		$PathChamiloAjaxFileManager='../../../../../../../main/'.$my_path['dir'].'my_files/';
	}

}

define('CONFIG_SYS_DEFAULT_PATH', $PathChamiloAjaxFileManager);
define('CONFIG_SYS_ROOT_PATH', $PathChamiloAjaxFileManager);

////////////// end chamilo



define('CONFIG_SYS_FOLDER_SHOWN_ON_TOP', true); //show your folders on the top of list if true or order by name
define("CONFIG_SYS_DIR_SESSION_PATH", session_save_path()); // Hack by Juan Carlos Raña
define("CONFIG_SYS_PATTERN_FORMAT", 'list'); //three options: reg ,csv, list, this option define the parttern format for the following patterns
/**
* reg => regulare expression
* csv => a list of comma separated file/folder name, (exactly match the specified file/folders)
* list => a list of comma spearated vague file/folder name (partially match the specified file/folders)
*
*/
//more details about regular expression please visit http://nz.php.net/manual/en/function.eregi.php
define('CONFIG_SYS_INC_DIR_PATTERN', ''); //force listing of folders with such pattern(s). separated by , if multiple
define('CONFIG_SYS_EXC_DIR_PATTERN', ''); //will prevent listing of folders with such pattern(s). separated by , if multiple
define('CONFIG_SYS_INC_FILE_PATTERN', ''); //force listing of fiels with such pattern(s). separated by , if multiple
define('CONFIG_SYS_EXC_FILE_PATTERN', ''); //will prevent listing of files with such pattern(s). separated by , if multiple
define('CONFIG_SYS_DELETE_RECURSIVE', 1); //delete all contents within a specific folder if set to be 1

//UPLOAD OPTIONS CONFIG
define('CONFIG_UPLOAD_MAXSIZE', 50000 * 1024 ); //by bytes For Chamilo upgrade from 50 to 50000 (50MB)

define('CONFIG_EDITABLE_VALID_EXTS', 'txt,htm,html'); //make you include all these extension in CONFIG_UPLOAD_VALID_EXTS if you want all valid. For Chamilo exclude original xml, js and css

define('CONFIG_OVERWRITTEN', false); //overwirte when processing paste
define('CONFIG_UPLOAD_VALID_EXTS', 'gif,jpg,jpeg,png,bmp,tif,psd,zip,sit,rar,gz,tar,htm,html,mov,mpg,avi,asf,mpeg,wmv,ogg,ogx,ogv,oga, aif,aiff,wav,mp3,swf,flv, mp4, aac, ppt,rtf,doc, pdf,xls,txt,flv,odt,ods,odp,odg,odc,odf,odb,odi,pps,docx,pptx,xlsx,accdb,xml,mid, midi, svg, svgz');//For Chamilo updated
	
//define viewable valid exts
$viewable='gif,bmp,txt,jpg,jpeg,png,tif,html,htm,mp3,wav,wmv,wma,rm,rmvb,mov,swf,flv,mp4,aac,avi,mpg,mpeg,asf,mid,midi';//updated by Chamilo
$viewable_array = explode(" ",$viewable);

if (api_browser_support('svg')){				
	$viewable_array[]=',svg';
}
if (api_browser_support('ogg')){
	$viewable_array[]=',ogg';
	$viewable_array[]=',ogx';
	$viewable_array[]=',oga';
	$viewable_array[]=',ogv';
}
if (api_browser_support('pdf')){
	$viewable_array[]=',pdf';
}

$viewable = implode(" ",$viewable_array);
$viewable = preg_replace('/\s+/', '', $viewable);//clean spaces

	//print_r($viewable);

define("CONFIG_VIEWABLE_VALID_EXTS", $viewable);

//define invalid exts
define('CONFIG_UPLOAD_INVALID_EXTS', 'php,php3,php4,php5,php6,phps,phtml,asp,aspx,jsp,cfm,cfc,pl,jar,sh,cgi,js,exe,com,bat,pif,scr,msi,ws,wsc,wsf,vb,vbe,vbs,reg,dll,ini'); //For Chamilo added.
//Preview
define('CONFIG_IMG_THUMBNAIL_MAX_X', 100);
define('CONFIG_IMG_THUMBNAIL_MAX_Y', 100);
define('CONFIG_THICKBOX_MAX_WIDTH', 400); //only for html, pdf, svg
define('CONFIG_THICKBOX_MAX_HEIGHT', 330);//only for html, pdf, svg


/**
 * CONFIG_URL_PREVIEW_ROOT was replaced by CONFIG_WEBSITE_DOCUMENT_ROOT since v0.8
* Normally, you don't need to bother with CONFIG_WEBSITE_DOCUMENT_ROOT
* Howerver, some Web Hosts do not have standard php.ini setting
 * which you will find the file manager can not locate your files correctly
* if you do have such issue, please change it to fit your system.
* so what should you to do get it
*   1. create a php script file (let's call it document_root.php)
*   2. add the following codes in in
* 			<?php
* 				echo dirname(__FILE__);
* 			?>
*   3. upload document_root.php to you website root folder which will only be reached when you visit http://www.domain-name.com or http://localhost/ at localhost computer
*   4. run it via http://www.domain-name.com/document_root.php or http://localhost/docuent_root.php if localhost computer, the url has to be exactly like that
*   5. the value shown on the screen is CONFIG_WEBSITE_DOCUMENT_ROOT should be
*   6. enjoy it
*
*/

// Modified by Ivan Tcholakov, JUN-2009.
//define('CONFIG_WEBSITE_DOCUMENT_ROOT', '');
define('CONFIG_WEBSITE_DOCUMENT_ROOT', rtrim(api_get_path(SYS_SERVER_ROOT_PATH), '/'));

//theme related setting
/*
*	options avaialbe for CONFIG_EDITOR_NAME are:
stand_alone
tinymce
fckeditor
*/
//CONFIG_EDITOR_NAME replaced CONFIG_THEME_MODE since @version 0.8
define('CONFIG_EDITOR_NAME', (CONFIG_QUERY_STRING_ENABLE && !empty($_GET['editor'])?secureFileName($_GET['editor']):'fckeditor')); // run mode fckeditor (Chamilo editor)
define('CONFIG_THEME_NAME', (CONFIG_QUERY_STRING_ENABLE && !empty($_GET['theme'])?secureFileName($_GET['theme']):'default'));  //change the theme to your custom theme rather than default
define('CONFIG_DEFAULT_VIEW', (CONFIG_SYS_THUMBNAIL_VIEW_ENABLE?'thumbnail':'detail')); //thumbnail or detail
define('CONFIG_DEFAULT_PAGINATION_LIMIT', 10000); //change 10 by 10000 while pagination is deactivated on Chamilo
define('CONFIG_LOAD_DOC_LATTER', false); //all documents will be loaded up after the template has been loaded to the client


//General Option Declarations

//LANGAUGAE DECLARATIONNS
$ajaxfilemanager_code_translation_table = array('' => 'en', 'pt' => 'pt_pt', 'sr' => 'sr_latn');
$langajaxfilemanager  = strtolower(str_replace('-', '_', api_get_language_isocode()));
$langajaxfilemanager = isset($ajaxfilemanager_code_translation_table[$langajaxfilemanager]) ? $ajaxfilemanager_code_translation_table[$langajaxfilemanager] : $langajaxfilemanager;
$langajaxfilemanager = file_exists(api_get_path(SYS_PATH).'main/inc/lib/fckeditor/editor/plugins/ajaxfilemanager/langs/'.$langajaxfilemanager.'.php') ? $langajaxfilemanager : 'en';

define('CONFIG_LANG_INDEX', 'language'); //the index in the session
define('CONFIG_LANG_DEFAULT', (CONFIG_QUERY_STRING_ENABLE && !empty($_GET['language']) && file_exists(DIR_LANG . secureFileName($_GET['language']) . '.php')?secureFileName($_GET['language']):$langajaxfilemanager)); //change it to be your language file base name, such en
// Language text direction.
define('CONFIG_LANG_TEXT_DIRECTION_DEFAULT', in_array(CONFIG_LANG_DEFAULT, array('ar', 'prs', 'he', 'ps', 'fa')) ? 'rtl' : 'ltr');
