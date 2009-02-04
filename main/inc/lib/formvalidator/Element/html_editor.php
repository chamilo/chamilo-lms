<?php
// $Id: html_editor.php 18226 2009-02-04 14:55:54Z ivantcholakov $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
require_once ('HTML/QuickForm/textarea.php');
require_once (api_get_path(LIBRARY_PATH).'fckeditor/fckeditor.php');
/**
* A html editor field to use with QuickForm
*/
class HTML_QuickForm_html_editor extends HTML_QuickForm_textarea
{
	/**
	 * Full page
	 */
	var $fullPage;
	var $fck_editor;
	/**
	 * Class constructor
	 * @param   string  HTML editor name/id
	 * @param   string  HTML editor  label
	 * @param   string  Attributes for the textarea
	 */
	function HTML_QuickForm_html_editor($elementName = null, $elementLabel = null, $attributes = null)
	{
		global $language_interface, $fck_attribute;
		HTML_QuickForm_element :: HTML_QuickForm_element($elementName, $elementLabel, $attributes);
		$this->_persistantFreeze = true;
		$this->_type = 'html_editor';
		$this->fullPage = false;
						
		@ $editor_lang = Database :: get_language_isocode($language_interface);
		$language_file = api_get_path(SYS_PATH).'main/inc/lib/fckeditor/editor/lang/'.$editor_lang.'.js';
		if (empty ($editor_lang) || !file_exists($language_file))
		{
			//if there was no valid iso-code, use the english one
			$editor_lang = 'en';
		}
		$name = $this->getAttribute('name');
		
		$this -> fck_editor = new FCKeditor($name);
		$this -> fck_editor->BasePath = api_get_path(REL_PATH).'main/inc/lib/fckeditor/';

		$this -> fck_editor->Width = !empty($fck_attribute['Width']) ? $fck_attribute['Width'] : '990';
		$this -> fck_editor->Height = !empty($fck_attribute['Height']) ? $fck_attribute['Height'] : '400';
		
		//We get the optionnals config parameters in $fck_attribute array
		$this -> fck_editor->Config = !empty($fck_attribute['Config']) ? $fck_attribute['Config'] : array();
		// we should set the $fck_attribute['ToolbarStartExpanded']= false to hide the toolbar by default we show the toolbar
		if($fck_attribute['ToolbarStartExpanded']=='false'){
			$this ->fck_editor->Config['ToolbarStartExpanded']=false;
		}
								
		$TBL_LANGUAGES = Database::get_main_table(TABLE_MAIN_LANGUAGE);
		
		//We are in a course
		if(isset($_SESSION["_course"]["language"])) {
			$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='".$_SESSION["_course"]["language"]."'";
		} elseif(isset($_SESSION["_user"]["language"])) {
			//Else, we get the current session language
			$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='".$_SESSION["_user"]["language"]."'";
		} else  {
			//Else we get the default platform language
			$platform_language=api_get_setting("platformLanguage");
			$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='$platform_language'";
		}
		
		$result_sql=api_sql_query($sql);
		$isocode_language=mysql_result($result_sql,0,0);
		$this -> fck_editor->Config['DefaultLanguage'] = $isocode_language;		
		$this -> fck_editor->ToolbarSet = $fck_attribute['ToolbarSet'] ;
		// css should be dokeos ones
		$this -> fck_editor->Config['EditorAreaCSS'] = $this -> fck_editor->Config['ToolbarComboPreviewCSS'] = api_get_path(REL_PATH).'main/css/'.api_get_setting('stylesheets').'/default.css';

		// Default configuration settings for document repositories.
		// These default settings do not cover all possible cases.

		// Preliminary calculations for assembling required paths.
		$script_name = substr($_SERVER['PHP_SELF'], strlen(api_get_path(REL_PATH)));
		$script_path = explode('/', $script_name);
		$script_path[count($script_path) - 1] = '';
		if (api_is_in_course())
		{
			$relative_path_prefix = str_repeat('../', count($script_path) - 1);
		}
		else
		{
			$relative_path_prefix = str_repeat('../', count($script_path) - 2);
		}
		$script_path = implode('/', $script_path);
		$script_path = api_get_path(WEB_PATH).$script_path;

		$use_advanced_filemanager = api_get_setting('advanced_filemanager') == 'true';

		if (api_is_in_course())
		{
			if (!api_is_in_group())
			{
				// 1. We are inside a course and not in a group.

				if (api_is_allowed_to_edit())
				{
					// 1.1. Teacher (tutor and coach are not authorized to change anything in the "content creation" tools)

					if (empty($this -> fck_editor->Config['CreateDocumentWebDir']))
					{
						$this -> fck_editor->Config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/';
					}

					if (is_null($this -> fck_editor->Config['CreateDocumentDir']))
					{
						$this -> fck_editor->Config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document/';
					}

					if (empty($this -> fck_editor->Config['BaseHref']))
					{
						$this -> fck_editor->Config['BaseHref'] = $script_path;
					}

					$upload_path = api_get_path(REL_COURSE_PATH).api_get_course_path().'/document/';
				}
				else
				{
					// 1.2. Student

					if (empty($this -> fck_editor->Config['CreateDocumentWebDir']))
					{
						$this -> fck_editor->Config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
					}

					if (is_null($this -> fck_editor->Config['CreateDocumentDir']))
					{
						$this -> fck_editor->Config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
					}

					if (empty($this -> fck_editor->Config['BaseHref']))
					{
						$this -> fck_editor->Config['BaseHref'] = $script_path;
					}

					$upload_path = api_get_path(REL_COURSE_PATH).api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
				}
			}
			else
			{
				// 2. Inside a course and inside a group.

				global $group_properties;

				if (empty($this -> fck_editor->Config['CreateDocumentWebDir']))
				{
					$this -> fck_editor->Config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
				}

				if (is_null($this -> fck_editor->Config['CreateDocumentDir']))
				{
					$this -> fck_editor->Config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document'.$group_properties['directory'].'/';
				}

				if (empty($this -> fck_editor->Config['BaseHref']))
				{
					$this -> fck_editor->Config['BaseHref'] = $script_path;
				}

				$upload_path = api_get_path(REL_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
			}
		}
		else
		{
			if (api_is_platform_admin() && $_SESSION['this_section'] == 'platform_admin')
			{
				// 3. Platform administration activities.

				if (empty($this -> fck_editor->Config['CreateDocumentWebDir']))
				{
					$this -> fck_editor->Config['CreateDocumentWebDir'] = api_get_path(WEB_PATH).'home/default_platform_document/';
				}

				if (is_null($this -> fck_editor->Config['CreateDocumentDir']))
				{
					$this -> fck_editor->Config['CreateDocumentDir'] = api_get_path(WEB_PATH).'home/default_platform_document/'; // A side-effect is in use here.
				}

				if (empty($this -> fck_editor->Config['BaseHref']))
				{
					$this -> fck_editor->Config['BaseHref'] = api_get_path(WEB_PATH).'home/default_platform_document/';
				}

				$upload_path = api_get_path(REL_PATH).'home/default_platform_document/';
			}
			else
			{
				// 4. The user is outside courses.

				if (empty($this -> fck_editor->Config['CreateDocumentWebDir']))
				{
					$this -> fck_editor->Config['CreateDocumentWebDir'] = api_get_path('WEB_PATH').'main/upload/users/'.api_get_user_id().'/my_files/';
				}

				if (is_null($this -> fck_editor->Config['CreateDocumentDir']))
				{
					$this -> fck_editor->Config['CreateDocumentDir'] = $relative_path_prefix.'upload/users/'.api_get_user_id().'/my_files/';
				}

				if (empty($this -> fck_editor->Config['BaseHref']))
				{
					$this -> fck_editor->Config['BaseHref'] = $script_path;
				}

				$upload_path = api_get_path(REL_PATH).'main/upload/users/'.api_get_user_id().'/my_files/';
			}
		}

		// Setting hyperlinks used to call file managers.

		if ($use_advanced_filemanager)
		{
			// Let javascripts "know" which file manager has been chosen.
			$this -> fck_editor->Config['AdvancedFileManager'] = true;

			// Configuration path when advanced file manager is used.
			$this -> fck_editor->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig_afm.js";

			// URLs for opening the file browser for different resource types (file types):

			// for images
			$this -> fck_editor->Config['ImageBrowserURL'] = $this -> fck_editor->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';	

			// for flash
			$this -> fck_editor->Config['FlashBrowserURL'] = $this -> fck_editor->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';

			// for audio files (mp3)
			$this -> fck_editor->Config['MP3BrowserURL'] = $this -> fck_editor->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';

			// for videos
			$this -> fck_editor->Config['VideoBrowserURL'] = $this -> fck_editor->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';

			// for videos (flv)
			$this -> fck_editor->Config['MediaBrowserURL'] = $this -> fck_editor->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';

			// for links (any resource type)
			$this -> fck_editor->Config['LinkBrowserURL'] = $this -> fck_editor->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
		}
		else
		{
			// Passing the file manager setting to javascripts too.
			$this -> fck_editor->Config['AdvancedFileManager'] = false;

			// Configuration path when simple file manager is used.
			$this -> fck_editor->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";

			// URLs for opening the file browser for different resource types (file types):

			// for images
			$this -> fck_editor->Config['ImageBrowserURL'] = $this -> fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Images&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			// for flash
			$this -> fck_editor->Config['FlashBrowserURL'] = $this -> fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Flash&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			// for audio files (mp3)
			$this -> fck_editor->Config['MP3BrowserURL'] = $this -> fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=MP3&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			// for videos
			$this -> fck_editor->Config['VideoBrowserURL'] = $this -> fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Video&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			// for videos (flv)
			$this -> fck_editor->Config['MediaBrowserURL'] = $this -> fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Video/flv&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			// for links (any resource type)
			$this -> fck_editor->Config['LinkBrowserURL'] = $this -> fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=File&Connector=connectors/php/connector.php&ServerPath=$upload_path";
		}

		// URLs for making quick uplods for different resource types (file types).
		// These URLs are used by the dialogs' quick upload tabs:

		// for images
		$this -> fck_editor->Config['ImageUploadURL'] = $this -> fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Images&ServerPath=$upload_path" ;

		// for flash
		$this -> fck_editor->Config['FlashUploadURL'] = $this -> fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Flash&ServerPath=$upload_path" ;
	
		// for audio files (mp3)
		$this -> fck_editor->Config['MP3UploadURL'] = $this -> fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=MP3&ServerPath=$upload_path" ;

		// for videos
		$this -> fck_editor->Config['VideoUploadURL'] = $this -> fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Video&ServerPath=$upload_path" ;

		// for videos (flv)
		$this -> fck_editor->Config['MediaUploadURL'] = $this -> fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Video/flv&ServerPath=$upload_path" ;

		// for links (any resource type)
		$this -> fck_editor->Config['LinkUploadURL'] = $this -> fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=File&ServerPath=$upload_path" ;
	}
	
	/**
	 * Check if the browser supports FCKeditor
	 *
	 * @access public
	 * @return boolean
	 */
	function browserSupported()
	{
		return FCKeditor :: IsCompatible();
	}
	/**
	 * Return the HTML editor in HTML
	 * @return string
	 */
	function toHtml()
	{
		$value = $this->getValue();
		
		if ($this->fullPage)
		{
			if (strlen(trim($value)) == 0)
			{
				// TODO: To be considered whether here to be added DOCTYPE, language and character set declarations.
				$value = '<html><head><title></title><style type="text/css" media="screen, projection">/*<![CDATA[*/body{font-family: arial, verdana, helvetica, sans-serif;font-size: 12px;}/*]]>*/</style></head><body></body></html>';
				$this->setValue($value);
			}
		}
		if ($this->_flagFrozen)
		{
			return $this->getFrozenHtml();
		}
		else
		{
			return $this->build_FCKeditor();
		}
	}
	/**
	 * Returns the htmlarea content in HTML
	 *@return string
	 */
	function getFrozenHtml()
	{
		return $this->getValue();
	}
	/**
	 * Build this element using FCKeditor
	 */
	function build_FCKeditor()
	{
		$result = '';
		if(! FCKeditor :: IsCompatible())
		{
			return parent::toHTML();
		}
		$this -> fck_editor->Value = $this->getValue();
		$result .=$this -> fck_editor->CreateHtml();

		//Add a link to open the allowed html tags window 
		//$result .= '<small><a href="#" onclick="MyWindow=window.open('."'".api_get_path(WEB_CODE_PATH)."help/allowed_html_tags.php?fullpage=". ($this->fullPage ? '1' : '0')."','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=500,height=600,left=200,top=20'".'); return false;">'.get_lang('AllowedHTMLTags').'</a></small>';
		return $result;
	}
}
?>
