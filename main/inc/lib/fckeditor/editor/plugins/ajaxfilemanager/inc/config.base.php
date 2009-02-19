<?php
	/**
	 * sysem base config setting
	 * @author Logan Cai (cailongqun [at] yahoo [dot] com [dot] cn)
	 * @link www.phpletter.com
	 * @since 1/August/2007
	 *
	 *
	 * Modify system config setting for Dokeos
	 * @author Juan Carlos Raña
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
	if(empty($_course['path']))
	{
		define('CONFIG_OPTIONS_DELETE', true);
		define('CONFIG_OPTIONS_CUT', true);
		define('CONFIG_OPTIONS_COPY', true);
		define('CONFIG_OPTIONS_NEWFOLDER', true);
		define('CONFIG_OPTIONS_RENAME', true);
		define('CONFIG_OPTIONS_UPLOAD', true); //
		define('CONFIG_OPTIONS_EDITABLE', false); //disable image editor and text editor	
	}
	else
	{
		if(api_is_allowed_to_edit())
		{	
		//api_is_allowed_to_edit() from Dokeos
			define('CONFIG_OPTIONS_DELETE', false);
			define('CONFIG_OPTIONS_CUT', false);
			define('CONFIG_OPTIONS_COPY', false);
			define('CONFIG_OPTIONS_NEWFOLDER', false);
			define('CONFIG_OPTIONS_RENAME', false);
			define('CONFIG_OPTIONS_UPLOAD', true); //
			define('CONFIG_OPTIONS_EDITABLE', false); //disable image editor and text editor
	
		}
		else
		{		
			define('CONFIG_OPTIONS_DELETE', false);
			define('CONFIG_OPTIONS_CUT', false);
			define('CONFIG_OPTIONS_COPY', false);
			define('CONFIG_OPTIONS_NEWFOLDER', false);
			define('CONFIG_OPTIONS_RENAME', false);
			define('CONFIG_OPTIONS_UPLOAD', true); //
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
		
	/////////////// Integration for Dokeos
		
	if(!empty($_course['path']))
	{
		if(!empty($group_properties['directory']))
		{		 
			$PathDokeosAjaxFileManager='../../../../../../../courses/'.$_course['path'].'/document'.$group_properties['directory'].'/';		   
		}
		else
		{
			if(api_is_allowed_to_edit())
			{
				$PathDokeosAjaxFileManager='../../../../../../../courses/'.$_course['path'].'/document/';			   
			}
			else
			{					
				$PathDokeosAjaxFileManager='../../../../../../../courses/'.$_course['path'].'/document/shared_folder/'.api_get_user_id().'/';					
			}		   
		}
	}
	else
	{	
		if (api_is_platform_admin() && $_SESSION['this_section']=='platform_admin')
		{
			//home page portal
			$PathDokeosAjaxFileManager='../../../../../../../home/default_platform_document/';
		}
		else
		{		
			//my profile
			$PathDokeosAjaxFileManager='../../../../../../../main/upload/users/'.api_get_user_id().'/my_files/';	
		}

	}
	
	define('CONFIG_SYS_DEFAULT_PATH', $PathDokeosAjaxFileManager);
	define('CONFIG_SYS_ROOT_PATH', $PathDokeosAjaxFileManager);		
	
	////////////// end dokeos
	
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
	define('CONFIG_UPLOAD_MAXSIZE', 50000 * 1024 ); //by bytes For Dokeos upgrade from 50 to 50000 (50MB)
	
	define('CONFIG_EDITABLE_VALID_EXTS', 'txt,htm,html'); //make you include all these extension in CONFIG_UPLOAD_VALID_EXTS if you want all valid. For Dokeos exclude original xml, js and css
	
	define('CONFIG_OVERWRITTEN', false); //overwirte when processing paste
	define('CONFIG_UPLOAD_VALID_EXTS', 'gif,jpg,png,bmp,tif,psd,zip,sit,rar,gz,tar,htm,html,mov,mpg,avi,asf,mpeg,wmv,aif,aiff,wav,mp3,swf, flv, mp4, aac, ppt,rtf,doc, pdf,xls,txt,flv,odt,ods,odp,odg,odc,odf,odb,odi,pps,docx,pptx,xlsx,accdb,xml');//For Dokeos updated
	define("CONFIG_VIEWABLE_VALID_EXTS", 'gif,bmp,txt,jpg,png,tif,html,htm,mp3, wav,wmv,wma,rm,rmvb,mov,swf,flv,mp4,aac,avi,mpg,mpeg,asf');//For Dokeos updated
	define('CONFIG_UPLOAD_INVALID_EXTS', 'php,jar,sh,cgi,js,exe,com,bat,pif,scr,msi,ws,wsc,wsf,vb,vbe,vbs,reg,dll'); //For Dokeos added.
	//Preview
	define('CONFIG_IMG_THUMBNAIL_MAX_X', 100);
	define('CONFIG_IMG_THUMBNAIL_MAX_Y', 100);
	define('CONFIG_THICKBOX_MAX_WIDTH', 700);
	define('CONFIG_THICKBOX_MAX_HEIGHT', 430);
	
	
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
		
	// Modified by Ivan Tcholakov.
	//define('CONFIG_WEBSITE_DOCUMENT_ROOT', '');
	if (preg_match('@'.api_get_path(REL_PATH).'$@', api_get_path(SYS_PATH))) // Sanity check.
	{
		define('CONFIG_WEBSITE_DOCUMENT_ROOT', preg_replace('@'.api_get_path(REL_PATH).'$@', '', api_get_path(SYS_PATH)));
	}
	else
	{
		define('CONFIG_WEBSITE_DOCUMENT_ROOT', ''); // Let the ajaxfilemanager try alone to find the document root.
	}

	//theme related setting
			/*
			*	options avaialbe for CONFIG_EDITOR_NAME are:
					stand_alone
					tinymce
					fckeditor
			*/
	//CONFIG_EDITOR_NAME replaced CONFIG_THEME_MODE since @version 0.8			
	define('CONFIG_EDITOR_NAME', (CONFIG_QUERY_STRING_ENABLE && !empty($_GET['editor'])?secureFileName($_GET['editor']):'fckeditor')); // run mode fckeditor (Dokeos editor)
	define('CONFIG_THEME_NAME', (CONFIG_QUERY_STRING_ENABLE && !empty($_GET['theme'])?secureFileName($_GET['theme']):'default'));  //change the theme to your custom theme rather than default
	define('CONFIG_DEFAULT_VIEW', (CONFIG_SYS_THUMBNAIL_VIEW_ENABLE?'detail':'detail')); //thumnail or detail
	define('CONFIG_DEFAULT_PAGINATION_LIMIT', 100);// change 10 by 100 because there may be hidden directories that are counted
	define('CONFIG_LOAD_DOC_LATTER', false); //all documents will be loaded up after the template has been loaded to the client 
	
	
	//General Option Declarations
	//LANGAUGAE DECLARATIONNS
	/*
	$langdoktoajaxfile= api_get_language_isocode(); //from dokeos. return, en, es... 
	if ($langdoktoajaxfile=='en' || $langdoktoajaxfile=='zh' || $langdoktoajaxfile=='es') // ajaxfilemanager full translations (only with all variables translated).
	{
	   $langajaxfilemanager=$langdoktoajaxfile;
	}
	else
	{
		$langajaxfilemanager='en';//default
	}	
	*/

	@ $langajaxfilemanager = Database :: get_language_isocode($language_interface);

	// Some code translations are needed.
	$langajaxfilemanager = strtolower(str_replace('_', '-', $langajaxfilemanager));
	if (empty ($langajaxfilemanager))
	{
		$langajaxfilemanager = 'en';
	}
	switch ($langajaxfilemanager)
	{
		case 'uk':
			$langajaxfilemanager = 'ukr';
			break;
		case 'pt':
			$langajaxfilemanager = 'pt_pt';
			break;
		case 'pt-br':
			$langajaxfilemanager = 'pt_br';
			break;
		// Code here other noticed exceptions.
	}


	// Checking for availability of a corresponding language file.
	if (!file_exists(api_get_path(SYS_PATH).'main/inc/lib/fckeditor/editor/plugins/ajaxfilemanager/langs/'.$langajaxfilemanager.'.php'))
	{
		// If there was no language file, use the english one.
		$langajaxfilemanager = 'en';
	}

	define('CONFIG_LANG_INDEX', 'language'); //the index in the session
	define('CONFIG_LANG_DEFAULT', (CONFIG_QUERY_STRING_ENABLE && !empty($_GET['language']) && file_exists(DIR_LANG . secureFileName($_GET['language']) . '.php')?secureFileName($_GET['language']):$langajaxfilemanager)); //change it to be your language file base name, such en
?>
