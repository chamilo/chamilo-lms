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

//require_once api_get_path(LIBRARY_PATH).'/media.lib.php';  // This fails in some pages (Tests).
require_once dirname(__FILE__).'/../media.lib.php';

// Configuration constants.

// The MimeTeX plugin support, a check whether the server executable file has been installed.
define ( 'CHECK_MIMETEX_PLUGIN_INSTALLED', true ) ; // Change to false in case of unexpected problems. Then installed state will be assumed.
define ( 'CHECK_MIMETEX_PLUGIN_INSTALLED_TIMEOUT', 0.05 ) ; // Response timeout in seconds. Keep this value as low as possible on Windows servers.
define ( 'CHECK_MIMETEX_PLUGIN_INSTALLED_URL_BASE', // This setting is about how to check mimetex executable presense. Possible values: 'ip' and 'domain_name'
	IS_WINDOWS_OS
	? 'ip'				// http://127.0.0.1/mimetex.exe will be checked for presense,
						// this is a preferable setting for Windows Vista, because its firewall does not block this address by default.
	: 'domain_name'		// http://www.mydokeos.com/mimetex.cgi will be checked for presense. If DNS has problems (if it is slow for example). try using the 'ip' setting.
) ;

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
		// Adaptation for the Dokeos LMS ---------------------------------------------------------

		$this->BasePath = api_get_path(REL_PATH).'main/inc/lib/fckeditor/';

		// Configuration data comes from different sources. To resolve conflicts between
		// options with a same name, these differnt sources have assigned priorities.
		
		// 1-st level (the highest priority)
		// Conficuration settings that are created during the initialization of
		// an editor's instance, they are "hardcoded" by a developer.

		// 2-nd level
		// Configuration settings from myconfig.php.
		$config = $this->get_custom_configuration();
		$this->read_configuration($config);

		// 3-rd level
		// Default configuration settings that are determined automatically here, on the php-side.
		$config = $this->get_default_configuration();
		$this->read_configuration($config);

		if ((api_is_allowed_to_edit() || api_is_platform_admin()) && isset($this->Config['BlockCopyPaste'])) {
			$this->Config['BlockCopyPaste'] = false;
		}

		// 4-th level
		// Configuration settings from myconfig.js.
		// This file may be modified for customization purposes.

		// 5-th level (the lowest priority)
		// Configuration settings from myconfig.js. This file is "as is" in the original source.
		// It is not recommended modifying myconfig.js.

		//----------------------------------------------------------------------------------------

		$HtmlValue = htmlspecialchars( $this->Value ) ;

		$Html = '' ;

		if ( $this->IsCompatible() )
		{
			// For debugging purposes the editor may run using original source versions of its javascripts,
			// not "compressed" versions. When you want use this feature, go to the platform administration
			// settings page and switch the system into "test server" mode.
			if ( api_get_setting('server_type') == 'test' )
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
			if ( $bFirst == false ) {
				$sParams .= '&amp;' ;
			} else {
				$bFirst = false ;
			}
			if ( is_string( $sValue ) ) {
				$sParams .= $this->EncodeConfig( $sKey ) . '=' . $this->EncodeConfig( $sValue ) ;
			} else {
				$sParams .= $this->EncodeConfig( $sKey ) . '=' . $this->EncodeConfig( $this->to_js( $sValue ) ) ;
			}
		}

		return $sParams ;
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

	/**
	 * Converts a PHP variable into its Javascript equivalent.
	 * The code of this method has been "borrowed" from the funcion drupal_to_js() within the Drupal CMS.
	 * @param mixed $var	The variable to be converted into Javascript syntax
	 * @return string		Returns a string 
	 * Note: This function is similar to json_encode(), in addition it produces HTML-safe strings, i.e. with <, > and & escaped.
	 * @link http://drupal.org/
	 */
	private function to_js( $var ) {
		switch ( gettype( $var ) ) {
			case 'boolean' :
				return $var ? 'true' : 'false' ; // Lowercase necessary!
			case 'integer' :
			case 'double' :
				return (string) $var ;
			case 'resource' :
			case 'string' :
				return '"' . str_replace( array( "\r", "\n", "<", ">", "&" ), array( '\r', '\n', '\x3c', '\x3e', '\x26' ), addslashes( $var ) ) . '"' ;
			case 'array' :
			// Arrays in JSON can't be associative. If the array is empty or if it
			// has sequential whole number keys starting with 0, it's not associative
			// so we can go ahead and convert it as an array.
				if ( empty( $var ) || array_keys( $var ) === range( 0, sizeof( $var ) - 1 ) ) {
					$output = array() ;
					foreach ( $var as $v ) {
						$output[] = $this->to_js( $v ) ;
					}
					return '[ ' . implode( ', ', $output ) . ' ]' ;
				}
			// Otherwise, fall through to convert the array as an object.
			case 'object' :
				$output = array() ;
				foreach ( $var as $k => $v ) {
					$output[] = $this->to_js( strval( $k ) ) . ': ' . $this->to_js( $v ) ;
				}
				return '{ ' . implode(', ', $output) . ' }' ;
			default:
				return 'null' ;
		}
	}

	/**
	 * This method reads configuration data for the current editor's instance without overriding settings that already exist.  
	 * @return array
	 */
	function read_configuration(& $config) {
		$toolbar_set = $this->ToolbarSet;
		$toolbar_set_maximized = $this->ToolbarSet.'Maximized';
		foreach ($config as $key => $value) {
			switch ($key) {
				case 'ToolbarSets':
					if (!empty($toolbar_set) && $toolbar_set != 'Default') {
						foreach ($value as $toolbar_name => $toolbar_data) {
							if ($toolbar_set == $toolbar_name || $toolbar_set_maximized == $toolbar_name) {		
								if (!isset($this->Config[$key][$toolbar_name])) {
									$this->Config[$key][$toolbar_name] = $toolbar_data;
								}
								break;
							}
						}
					}
					break;
				case 'BlockCopyPaste':
				case 'ToolbarCanCollapse':
				case 'ToolbarStartExpanded':
					if (!empty($toolbar_set) && $toolbar_set != 'Default') {
						foreach ($value as $toolbar_name => $toolbar_data) {
							if ($toolbar_set == $toolbar_name) {		
								if (!isset($this->Config[$key][$toolbar_name])) {
									$this->Config[$key] = (boolean) $toolbar_data;
								}
								break;
							}
						}
					}
					break;
				case 'ToolbarLocation':
					if (!empty($toolbar_set) && $toolbar_set != 'Default') {
						foreach ($value as $toolbar_name => $toolbar_data) {
							if ($toolbar_set == $toolbar_name) {		
								if (!isset($this->Config[$key][$toolbar_name])) {
									$this->Config[$key] = (string) $toolbar_data;
								}
								break;
							}
						}
					}
					break;
				default:
					if (!isset($this->Config[$key])) {
						$this->Config[$key] = $value;
					}
			}
		}
	}

	/**
	 * This method returns editor's custom configuration settings read from a php-file.  
	 * @return array	Custom configuration data.
	 */
	private function & get_custom_configuration() {
		static $config;
		if (!isset($config)) {
			require api_get_path(LIBRARY_PATH).'fckeditor/myconfig.php';
		}
		if (!isset($config['ToolbarSets'][$this->ToolbarSet])) {
			@include api_get_path(LIBRARY_PATH).'fckeditor/'.$config['ToolbarSets']['Directory'].'/'.self::camel_case_to_underscore($this->ToolbarSet).'.php';
			if (!isset($config['ToolbarSets'][$this->ToolbarSet])) {
				$this->ToolbarSet = 'Default';
			}
		}
		return $config;
	}

	/**
	 * This method returns automatically determined editor's configuration settings (default settings).  
	 * @return array
	 */
	private function & get_default_configuration() {
		return array_merge(
			self::get_javascript_custom_configuration_file(),
			self::get_css_configuration_paths(),
			self::get_editor_language(),
			$this->get_repository_configuration(),
			self::get_media_configuration(),
			self::get_user_configuration_data(),
			self::get_mimetex_plugin_configuration()
		);
	}

	/**
	 * This method returns the path to the javascript custom configuration file.
	 * @return array
	 */
	private function & get_javascript_custom_configuration_file() {
		return array('CustomConfigurationsPath' => api_get_path(REL_PATH).'main/inc/lib/fckeditor/myconfig.js');
	}

	/**
	 * This method returns CSS-related configuration data (paths to style files).
	 * @return array
	 */
	private function & get_css_configuration_paths() {
		// CSS should come from the system.
		$config['EditorAreaCSS'] = api_get_path(REL_PATH).'main/css/'.api_get_setting('stylesheets').'/default.css';
		$config['ToolbarComboPreviewCSS'] = $config['EditorAreaCSS'];
		return $config;
	}

	/**
	 * This method determines editor's interface language and returns it as compatible with the editor langiage code.
	 * @return array
	 */
	private function & get_editor_language() {
		static $config;
		if (!is_array($config)) {
			global $language_interface;
			@ $editor_lang = Database :: get_language_isocode($language_interface);
			$editor_lang = strtolower(str_replace('_', '-', $editor_lang));
			if (empty ($editor_lang)) {
				$editor_lang = 'en';
			}
			$language_file = api_get_path(SYS_PATH).'main/inc/lib/fckeditor/editor/lang/'.$editor_lang.'.js';
			if (!file_exists($language_file)) {
				// If there was no language file, use the English one.
				$editor_lang = 'en';
			}
			$config['DefaultLanguage'] = $editor_lang;
		}
		return $config;
	}
	
	/**
	 * This method returns default configuration for document repository that is to be used by the editor.
	 * @return array
	 */
	private function & get_repository_configuration() {

		// Preliminary calculations for assembling required paths.
		$base_path = $this->BasePath;
		$script_name = substr($_SERVER['PHP_SELF'], strlen(api_get_path(REL_PATH)));
		$script_path = explode('/', $script_name);
		$script_path[count($script_path) - 1] = '';
		if (api_is_in_course()) {
			$relative_path_prefix = str_repeat('../', count($script_path) - 1);
		} else {
			$relative_path_prefix = str_repeat('../', count($script_path) - 2);
		}
		$script_path = implode('/', $script_path);
		$script_path = api_get_path(WEB_PATH).$script_path;

		$use_advanced_filemanager = api_get_setting('advanced_filemanager') == 'true';
		// Let javascripts "know" which file manager has been chosen.
		$config['AdvancedFileManager'] = $use_advanced_filemanager;

		if (api_is_in_course()) {
			if (!api_is_in_group()) {

				// 1. We are inside a course and not in a group.
				if (api_is_allowed_to_edit()) {

					// 1.1. Teacher (tutor and coach are not authorized to change anything in the "content creation" tools)
					$config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/';
					$config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document/';
					$config['BaseHref'] = $script_path;
					$upload_path = api_get_path(REL_COURSE_PATH).api_get_course_path().'/document/';

				} else {

					// 1.2. Student
					$config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/shared_folder/sf_user_'.api_get_user_id().'/';
					$config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document/shared_folder/sf_user_'.api_get_user_id().'/';
					$config['BaseHref'] = $script_path;
					$upload_path = api_get_path(REL_COURSE_PATH).api_get_course_path().'/document/shared_folder/sf_user_'.api_get_user_id().'/';
				}
			} else {

				// 2. Inside a course and inside a group.
				global $group_properties;
				$config['CreateDocumentWebDir'] = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
				$config['CreateDocumentDir'] = $relative_path_prefix.'courses/'.api_get_course_path().'/document'.$group_properties['directory'].'/';
				$config['BaseHref'] = $script_path;
				$upload_path = api_get_path(REL_COURSE_PATH).api_get_course_path().'/document'.$group_properties['directory'].'/';
			}
		} else {

			if (api_is_platform_admin() && $_SESSION['this_section'] == 'platform_admin') {

				// 3. Platform administration activities.
				$config['CreateDocumentWebDir'] = api_get_path(WEB_PATH).'home/default_platform_document/';
				$config['CreateDocumentDir'] = api_get_path(WEB_PATH).'home/default_platform_document/'; // A side-effect is in use here.
				$config['BaseHref'] = api_get_path(WEB_PATH).'home/default_platform_document/';
				$upload_path = api_get_path(REL_PATH).'home/default_platform_document/';

			} else {

				// 4. The user is outside courses.
				$config['CreateDocumentWebDir'] = api_get_path('WEB_PATH').'main/upload/users/'.api_get_user_id().'/my_files/';
				$config['CreateDocumentDir'] = $relative_path_prefix.'upload/users/'.api_get_user_id().'/my_files/';
				$config['BaseHref'] = $script_path;
				$upload_path = api_get_path(REL_PATH).'main/upload/users/'.api_get_user_id().'/my_files/';
			}
		}

		// URLs for opening the file browser for different resource types (file types):
		if ($use_advanced_filemanager) {
			// for images
			$config['ImageBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
			// for flash
			$config['FlashBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
			// for audio files (mp3)
			$config['MP3BrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
			// for video
			$config['VideoBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
			// for video (flv)
			$config['MediaBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
			// for links (any resource type)
			$config['LinkBrowserURL'] = $base_path.'/editor/plugins/ajaxfilemanager/ajaxfilemanager.php';
		} else {
			// for images
			$config['ImageBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=Images&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php&ServerPath='.$upload_path;
			// for flash
			$config['FlashBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=Flash&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php&ServerPath='.$upload_path;
			// for audio files (mp3)
			$config['MP3BrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=MP3&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php&ServerPath='.$upload_path;
			// for video
			$config['VideoBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=Video&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php&ServerPath='.$upload_path;
			// for video (flv)
			$config['MediaBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=Video/flv&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php&ServerPath='.$upload_path;
			// for links (any resource type)
			$config['LinkBrowserURL'] = $base_path.'editor/filemanager/browser/default/browser.html?Type=File&Connector='.$base_path.'editor/filemanager/connectors/php/connector.php&ServerPath='.$upload_path;
		}

		// URLs for making quick uplods for different resource types (file types).
		// These URLs are used by the dialogs' quick upload tabs:
		// for images
		$config['ImageUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=Images&ServerPath='.$upload_path;
		// for flash
		$config['FlashUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=Flash&ServerPath='.$upload_path;
		// for audio files (mp3)
		$config['MP3UploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=MP3&ServerPath='.$upload_path;
		// for video
		$config['VideoUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=Video&ServerPath='.$upload_path;
		// for video (flv)
		$config['MediaUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=Video/flv&ServerPath='.$upload_path;
		// for links (any resource type)
		$config['LinkUploadURL'] = $base_path.'editor/filemanager/connectors/php/upload.php?Type=File&ServerPath='.$upload_path;

		return $config;
	}

	/**
	 * This method returns multi-media related configuration data.
	 * @return array
	 */
	private function & get_media_configuration() {
		$config['FlashPlayerAudio'] = Media::get_path(FLASH_PLAYER_AUDIO, REL_PATH);
		$config['FlashPlayerVideo'] = Media::get_path(FLASH_PLAYER_VIDEO, REL_PATH);
		$config['ScriptSWFObject'] = Media::get_path(SCRIPT_SWFOBJECT, REL_PATH);
		return $config;
	}

	/**
	 * This method returns current user specific configuration data.
	 * @return array
	 */
	private function & get_user_configuration_data() {
		$config['UserIsCourseAdmin'] = api_is_allowed_to_edit() ? true : false;
		$config['UserIsPlatformAdmin'] = api_is_platform_admin() ? true : false;
		return $config;
	}

	/**
	 * This method returns detected configuration data about editor's MimeTeX plugin.  
	 * @return array
	 */
	private function & get_mimetex_plugin_configuration() {
		static $config ;
		if ( !is_array( $config ) ) {
			$server_base = explode( '/', api_get_path( WEB_PATH ) ) ;
			$server_base_ip = $server_base[0] . '/' . $server_base[1] . '/127.0.0.1' ;
			$server_base = $server_base[0]. '/' . $server_base[1]. '/' . $server_base[2] ;
			$url_relative = '/cgi-bin/mimetex' . ( IS_WINDOWS_OS ? '.exe' : '.cgi' ) ;
			if ( CHECK_MIMETEX_PLUGIN_INSTALLED ) {
				$check_mimetex_url = ( CHECK_MIMETEX_PLUGIN_INSTALLED_URL_BASE == 'ip' ? $server_base_ip : $server_base ) . $url_relative . '?' . rand() ;
				$config['IsMimetexInstalled'] = self::url_exists( $check_mimetex_url, CHECK_MIMETEX_PLUGIN_INSTALLED_TIMEOUT ) ;
			} else {
				$config['IsMimetexInstalled'] = true ;
			}
			$config['MimetexUrl'] = $server_base . $url_relative ;
		}
		return $config;
	}

	/*
	 * Checks whether a given url exists.
	 * @param string $url
	 * @param int $timeout
	 * @return boolean
	 * @author Ivan Tcholakov, FEB-2009
	 */
	private function url_exists($url, $timeout = 30) {
		$parsed = parse_url($url);
		$scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
		$host = $parsed['host'];
		$port = isset($parsed['port']) ? $parsed['port'] : ($scheme == 'http' ? 80 : ($scheme == 'https' ? 443 : -1 ));

		$file_exists = false;
		$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
		if ($fp) {
			$request = "HEAD ".$url." / HTTP/1.1\r\n";
			$request .= "Host: ".$host."\r\n";
			$request .= "Connection: Close\r\n\r\n";

			@fwrite($fp, $request);
			while (!@feof($fp)) {
				$header = @fgets($fp, 128);
				if(@preg_match('#HTTP/1.1 200 OK#', $header)) {
					$file_exists = true;
					break;
				}
			}
		}
		@fclose($fp);
		return $file_exists;
	}

	/*
	 * Convers a string from camel case to underscore.
	 * @param string $string
	 * @return string
	 */
	private function camel_case_to_underscore($string) {
		return strtolower(preg_replace('/([a-z])([A-Z])/', "$1_$2", $string));
	}
}
