<?php
/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2009 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 *
 * This is the integration file for PHP 5.
 *
 * It defines the FCKeditor class that can be used to create editor
 * instances in PHP pages on server side.
 */

// Code to adapt the editor to the Dokeos LMS has been added by the Dokeos team, FEB-2009.

/**
 * Check if browser is compatible with FCKeditor.
 * Return true if is compatible.
 *
 * @return boolean
 */
function FCKeditor_IsCompatibleBrowser()
{
	if ( isset( $_SERVER ) ) {
		$sAgent = $_SERVER['HTTP_USER_AGENT'] ;
	}
	else {
		global $HTTP_SERVER_VARS ;
		if ( isset( $HTTP_SERVER_VARS ) ) {
			$sAgent = $HTTP_SERVER_VARS['HTTP_USER_AGENT'] ;
		}
		else {
			global $HTTP_USER_AGENT ;
			$sAgent = $HTTP_USER_AGENT ;
		}
	}

	if ( strpos($sAgent, 'MSIE') !== false && strpos($sAgent, 'mac') === false && strpos($sAgent, 'Opera') === false )
	{
		$iVersion = (float)substr($sAgent, strpos($sAgent, 'MSIE') + 5, 3) ;
		return ($iVersion >= 5.5) ;
	}
	else if ( strpos($sAgent, 'Gecko/') !== false )
	{
		$iVersion = (int)substr($sAgent, strpos($sAgent, 'Gecko/') + 6, 8) ;
		return ($iVersion >= 20030210) ;
	}
	else if ( strpos($sAgent, 'Opera/') !== false )
	{
		$fVersion = (float)substr($sAgent, strpos($sAgent, 'Opera/') + 6, 4) ;
		return ($fVersion >= 9.5) ;
	}
	else if ( preg_match( "|AppleWebKit/(\d+)|i", $sAgent, $matches ) )
	{
		$iVersion = $matches[1] ;
		return ( $matches[1] >= 522 ) ;
	}
	else
		return false ;
}

class FCKeditor
{
	/**
	 * Name of the FCKeditor instance.
	 *
	 * @access protected
	 * @var string
	 */
	public $InstanceName ;
	/**
	 * Path to FCKeditor relative to the document root.
	 *
	 * @var string
	 */
	public $BasePath ;
	/**
	 * Width of the FCKeditor.
	 * Examples: 100%, 600
	 *
	 * @var mixed
	 */
	public $Width ;
	/**
	 * Height of the FCKeditor.
	 * Examples: 400, 50%
	 *
	 * @var mixed
	 */
	public $Height ;
	/**
	 * Name of the toolbar to load.
	 *
	 * @var string
	 */
	public $ToolbarSet ;
	/**
	 * Initial value.
	 *
	 * @var string
	 */
	public $Value ;
	/**
	 * This is where additional configuration can be passed.
	 * Example:
	 * $oFCKeditor->Config['EnterMode'] = 'br';
	 *
	 * @var array
	 */
	public $Config ;

	/**
	 * Main Constructor.
	 * Refer to the _samples/php directory for examples.
	 *
	 * @param string $instanceName
	 */
	public function __construct( $instanceName )
 	{
		$this->InstanceName	= $instanceName ;
		$this->BasePath		= '/fckeditor/' ;
		$this->Width		= '100%' ;
		$this->Height		= '200' ;
		$this->ToolbarSet	= 'Default' ;
		$this->Value		= '' ;

		$this->Config		= array() ;
	}

	/**
	 * Display FCKeditor.
	 *
	 */
	public function Create()
	{
		echo $this->CreateHtml() ;
	}

	/**
	 * Return the HTML code required to run FCKeditor.
	 *
	 * @return string
	 */
	public function CreateHtml()
	{
		/*
		 * Adaptation for the Dokeos LMS.
		 */

		// Default configuration settings will be calculated when the editor
		// is (still) created directly, without using the formvalidator module.
		// These default settings might not cover all possible cases.

		global $language_interface;

		$this->BasePath = api_get_path(REL_PATH).'main/inc/lib/fckeditor/';

		@ $editor_lang = Database :: get_language_isocode($language_interface);

		// Making a compatible code in order it to be accepted by the editor.
		$editor_lang = strtolower(str_replace('_', '-', $editor_lang));

		if (empty ($editor_lang))
		{
			$editor_lang = 'en';
		}

		// Checking for availability of a corresponding language file.
		$language_file = api_get_path(SYS_PATH).'main/inc/lib/fckeditor/editor/lang/'.$editor_lang.'.js';
		if (!file_exists($language_file))
		{
			// If there was no language file, use the english one.
			$editor_lang = 'en';
		}

		$this->Config['DefaultLanguage'] = $editor_lang;

		// css should be dokeos ones
		$this->Config['EditorAreaCSS'] = $this->Config['ToolbarComboPreviewCSS'] = api_get_path(REL_PATH).'main/css/'.api_get_setting('stylesheets').'/default.css';

		// we should set the $fck_attribute['ToolbarStartExpanded']= false to hide the toolbar by default we show the toolbar
		if (is_string($this->Config['ToolbarStartExpanded']))
		{
			$this->Config['ToolbarStartExpanded'] = (empty($this->Config['ToolbarStartExpanded']) || $this->Config['ToolbarStartExpanded'] == 'false') ? false : true;
		}

		// Default configuration settings for document repositories.

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

					if (empty($this->Config['CreateDocumentWebDir']))
					{
						$this->Config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/';
					}

					if (is_null($this->Config['CreateDocumentDir']))
					{
						$this->Config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document/';
					}

					if (empty($this->Config['BaseHref']))
					{
						$this->Config['BaseHref'] = $script_path;
					}

					$upload_path = api_get_path(REL_COURSE_PATH).api_get_course_path().'/document/';
				}
				else
				{
					// 1.2. Student

					if (empty($this->Config['CreateDocumentWebDir']))
					{
						$this->Config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
					}

					if (is_null($this->Config['CreateDocumentDir']))
					{
						$this->Config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
					}

					if (empty($this->Config['BaseHref']))
					{
						$this->Config['BaseHref'] = $script_path;
					}

					$upload_path = api_get_path(REL_COURSE_PATH).api_get_course_path().'/document/shared_folder/'.api_get_user_id().'/';
				}
			}
			else
			{
				// 2. Inside a course and inside a group.

				global $group_properties;

				if (empty($this->Config['CreateDocumentWebDir']))
				{
					$this->Config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
				}

				if (is_null($this->Config['CreateDocumentDir']))
				{
					$this->Config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document'.$group_properties['directory'].'/';
				}

				if (empty($this->Config['BaseHref']))
				{
					$this->Config['BaseHref'] = $script_path;
				}

				$upload_path = api_get_path(REL_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
			}
		}
		else
		{
			if (api_is_platform_admin() && $_SESSION['this_section'] == 'platform_admin')
			{
				// 3. Platform administration activities.

				if (empty($this->Config['CreateDocumentWebDir']))
				{
					$this->Config['CreateDocumentWebDir'] = api_get_path(WEB_PATH).'home/default_platform_document/';
				}

				if (is_null($this->Config['CreateDocumentDir']))
				{
					$this->Config['CreateDocumentDir'] = api_get_path(WEB_PATH).'home/default_platform_document/'; // A side-effect is in use here.
				}

				if (empty($this->Config['BaseHref']))
				{
					$this->Config['BaseHref'] = api_get_path(WEB_PATH).'home/default_platform_document/';
				}

				$upload_path = api_get_path(REL_PATH).'home/default_platform_document/';
			}
			else
			{
				// 4. The user is outside courses.

				if (empty($this->Config['CreateDocumentWebDir']))
				{
					$this->Config['CreateDocumentWebDir'] = api_get_path('WEB_PATH').'main/upload/users/'.api_get_user_id().'/my_files/';
				}

				if (is_null($this->Config['CreateDocumentDir']))
				{
					$this->Config['CreateDocumentDir'] = $relative_path_prefix.'upload/users/'.api_get_user_id().'/my_files/';
				}

				if (empty($this->Config['BaseHref']))
				{
					$this->Config['BaseHref'] = $script_path;
				}

				$upload_path = api_get_path(REL_PATH).'main/upload/users/'.api_get_user_id().'/my_files/';
			}
		}

		// Setting hyperlinks used to call file managers.

		if ($use_advanced_filemanager)
		{
			// Let javascripts "know" which file manager has been chosen.
			$this->Config['AdvancedFileManager'] = true;

			// Configuration path when advanced file manager is used.
			$this->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";
			//$this->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig_afm.js";

			// URLs for opening the file browser for different resource types (file types):

			// for images
			$this->Config['ImageBrowserURL'] = $this->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';

			// for flash
			$this->Config['FlashBrowserURL'] = $this->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';

			// for audio files (mp3)
			$this->Config['MP3BrowserURL'] = $this->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';

			// for videos
			$this->Config['VideoBrowserURL'] = $this->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';

			// for videos (flv)
			$this->Config['MediaBrowserURL'] = $this->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';

			// for links (any resource type)
			$this->Config['LinkBrowserURL'] = $this->BasePath.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
		}
		else
		{
			// Passing the file manager setting to the editor's javascripts.
			$this->Config['AdvancedFileManager'] = false;

			// Configuration path when simple file manager is used.
			$this->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";

			// URLs for opening the file browser for different resource types (file types):

			// for images
			$this->Config['ImageBrowserURL'] = $this->BasePath . "editor/filemanager/browser/default/browser.html?Type=Images&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			// for flash
			$this->Config['FlashBrowserURL'] = $this->BasePath . "editor/filemanager/browser/default/browser.html?Type=Flash&Connector=connectors/php/connector.php&ServerPath=$upload_path";
	
			// for audio files (mp3)
			$this->Config['MP3BrowserURL'] = $this->BasePath . "editor/filemanager/browser/default/browser.html?Type=MP3&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			// for videos
			$this->Config['VideoBrowserURL'] = $this->BasePath . "editor/filemanager/browser/default/browser.html?Type=Video&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			// for videos (flv)
			$this->Config['MediaBrowserURL'] = $this->BasePath . "editor/filemanager/browser/default/browser.html?Type=Video/flv&Connector=connectors/php/connector.php&ServerPath=$upload_path";

			// for links (any resource type)
			$this->Config['LinkBrowserURL'] = $this->BasePath . "editor/filemanager/browser/default/browser.html?Type=File&Connector=connectors/php/connector.php&ServerPath=$upload_path";
		}

		// URLs for making quick uplods for different resource types (file types).
		// These URLs are used by the dialogs' quick upload tabs:

		// for images
		$this->Config['ImageUploadURL'] = $this->BasePath . "editor/filemanager/upload/php/upload.php?Type=Images&ServerPath=$upload_path" ;

		// for flash
		$this->Config['FlashUploadURL'] = $this->BasePath . "editor/filemanager/upload/php/upload.php?Type=Flash&ServerPath=$upload_path" ;
	
		// for audio files (mp3)
		$this->Config['MP3UploadURL'] = $this->BasePath . "editor/filemanager/upload/php/upload.php?Type=MP3&ServerPath=$upload_path" ;

		// for videos
		$this->Config['VideoUploadURL'] = $this->BasePath . "editor/filemanager/upload/php/upload.php?Type=Video&ServerPath=$upload_path" ;

		// for videos (flv)
		$this->Config['MediaUploadURL'] = $this->BasePath . "editor/filemanager/upload/php/upload.php?Type=Video/flv&ServerPath=$upload_path" ;

		// for links (any resource type)
		$this->Config['LinkUploadURL'] = $this->BasePath . "editor/filemanager/upload/php/upload.php?Type=File&ServerPath=$upload_path" ;

		// Passing user status related data to the editor.
		$this->Config['UserIsCourseAdmin'] = api_is_allowed_to_edit() ? true : false;
		$this->Config['UserIsPlatformAdmin'] = api_is_platform_admin() ? true : false;


		// The MimeTeX plugin support.

		$check_mimetex_installed = true; // Change to false in case of unexpected problems.
		//---------------------------------------------------------------------------------

    static $is_mimetex_installed = null;
		$server_base = explode('/', api_get_path(WEB_PATH));
		//$server_base = $server_base[0].'/'.$server_base[1].'/'.$server_base[2].'/'; // Problematic on Windows Vista.
		$server_base = $server_base[0].'/'.$server_base[1].'/127.0.0.1/';
		if (defined('PHP_OS'))
		{
			$os = PHP_OS;
		}
		else
		{
			$os = php_uname();
		}
		if (strtoupper(substr($os, 0, 3 )) === 'WIN')
		{
			$this->Config['MimetexUrl'] = $server_base.'cgi-bin/mimetex.exe';
		}
		else
		{
			$this->Config['MimetexUrl'] = $server_base.'cgi-bin/mimetex.cgi';
		}
		if ($check_mimetex_installed)
		{
			if (!isset($is_mimetex_installed))
			{
				$this->Config['IsMimetexInstalled'] = $this->url_exists($this->Config['MimetexUrl'].'?'.rand(), 0.05);
			}
			else
			{
				$this->Config['IsMimetexInstalled'] = $is_mimetex_installed;
			}
		}
		else
		{
			$this->Config['IsMimetexInstalled'] = true;
		}
		$is_mimetex_installed = $this->Config['IsMimetexInstalled'];


		/*
		 * The original code starts from here.
		 */

		$HtmlValue = htmlspecialchars( $this->Value ) ;

		$Html = '' ;

		if ( $this->IsCompatible() )
		{
			if ( isset( $_GET['fcksource'] ) && $_GET['fcksource'] == "true" )
				$File = 'fckeditor.original.html' ;
			else
				$File = 'fckeditor.html' ;

			$Link = "{$this->BasePath}editor/{$File}?InstanceName={$this->InstanceName}" ;

			if ( $this->ToolbarSet != '' )
				$Link .= "&amp;Toolbar={$this->ToolbarSet}" ;

			// Render the linked hidden field.
			$Html .= "<input type=\"hidden\" id=\"{$this->InstanceName}\" name=\"{$this->InstanceName}\" value=\"{$HtmlValue}\" style=\"display:none\" />" ;

			// Render the configurations hidden field.
			$Html .= "<input type=\"hidden\" id=\"{$this->InstanceName}___Config\" value=\"" . $this->GetConfigFieldString() . "\" style=\"display:none\" />" ;

			// Render the editor IFRAME.
			$Html .= "<iframe id=\"{$this->InstanceName}___Frame\" src=\"{$Link}\" width=\"{$this->Width}\" height=\"{$this->Height}\" frameborder=\"0\" scrolling=\"no\"></iframe>" ;
		}
		else
		{
			if ( strpos( $this->Width, '%' ) === false )
				$WidthCSS = $this->Width . 'px' ;
			else
				$WidthCSS = $this->Width ;

			if ( strpos( $this->Height, '%' ) === false )
				$HeightCSS = $this->Height . 'px' ;
			else
				$HeightCSS = $this->Height ;

			$Html .= "<textarea name=\"{$this->InstanceName}\" rows=\"4\" cols=\"40\" style=\"width: {$WidthCSS}; height: {$HeightCSS}\">{$HtmlValue}</textarea>" ;
		}

		return $Html ;
	}

	/**
	 * Returns true if browser is compatible with FCKeditor.
	 *
	 * @return boolean
	 */
	public function IsCompatible()
	{
		return FCKeditor_IsCompatibleBrowser() ;
	}

	/**
	 * Get settings from Config array as a single string.
	 *
	 * @access protected
	 * @return string
	 */
	public function GetConfigFieldString()
	{
		$sParams = '' ;
		$bFirst = true ;

		foreach ( $this->Config as $sKey => $sValue )
		{
			if ( $bFirst == false )
				$sParams .= '&amp;' ;
			else
				$bFirst = false ;

			if ( $sValue === true )
				$sParams .= $this->EncodeConfig( $sKey ) . '=true' ;
			else if ( $sValue === false )
				$sParams .= $this->EncodeConfig( $sKey ) . '=false' ;
			else
				$sParams .= $this->EncodeConfig( $sKey ) . '=' . $this->EncodeConfig( $sValue ) ;
		}

		return $sParams ;
	}

	/*
	 * Checks whether a given url exists.
	 * 
	 * @url string
	 * @return boolean
	 * 
	 * @author Ivan Tcholakov, FEB-2009
	 */

	private function url_exists($url, $timeout = 30)
	{
		$parsed = parse_url($url);
		$scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
		$host = $parsed['host'];
		$port = isset($parsed['port']) ? $parsed['port'] : ($scheme == 'http' ? 80 : ($scheme == 'https' ? 443 : -1 ));

		$file_exists = false;
		$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
		if ($fp)
		{
			$request = "HEAD ".$url." / HTTP/1.1\r\n";
			$request .= "Host: ".$host."\r\n";
			$request .= "Connection: Close\r\n\r\n";

			@fwrite($fp, $request);
			while (!@feof($fp))
			{
				$header = @fgets($fp, 128);
				if(@preg_match('#HTTP/1.1 200 OK#', $header))
				{
					$file_exists = true;
					break;
				}
			}
		}
		@fclose($fp);
		return $file_exists;
	}

	/**
	 * Encode characters that may break the configuration string
	 * generated by GetConfigFieldString().
	 *
	 * @access protected
	 * @param string $valueToEncode
	 * @return string
	 */
	public function EncodeConfig( $valueToEncode )
	{
		$chars = array(
			'&' => '%26',
			'=' => '%3D',
			'"' => '%22' ) ;

		return strtr( $valueToEncode,  $chars ) ;
	}
}
