<?php
// $Id: html_editor.php 10595 2007-01-05 14:05:42Z elixir_inter $
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
	/**
	 * Class constructor
	 * @param   string  HTML editor name/id
	 * @param   string  HTML editor  label
	 * @param   string  Attributes for the textarea
	 */
	function HTML_QuickForm_html_editor($elementName = null, $elementLabel = null, $attributes = null)
	{
		HTML_QuickForm_element :: HTML_QuickForm_element($elementName, $elementLabel, $attributes);
		$this->_persistantFreeze = true;
		$this->_type = 'html_editor';
		$this->fullPage = false;
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
		global $language_interface, $fck_attribute;
		if(! FCKeditor :: IsCompatible())
		{
			return parent::toHTML();
		}
		@ $editor_lang = Database :: get_language_isocode($language_interface);
		$language_file = api_get_path(WEB_PATH).'/main/inc/lib/fckeditor/editor/lang/'.$editor_lang.'.js';
		if (empty ($editor_lang) || !file_exists($language_file))
		{
			//if there was no valid iso-code, use the english one
			$editor_lang = 'en';
		}
		$name = $this->getAttribute('name');

		$fck_editor = new FCKeditor($name);
		$fck_editor->BasePath = api_get_path(WEB_PATH).'main/inc/lib/fckeditor/';

		$fck_editor->Width = $fck_attribute['Width'] ? $fck_attribute['Width'] : '990';
		$fck_editor->Height = $fck_attribute['Height'] ? $fck_attribute['Height'] : '400';
		$fck_editor->Value = $this->getValue();
		//We get the optionnals config parameters in $fck_attribute array
		$fck_editor->Config = $fck_attribute['Config'] ? $fck_attribute['Config'] : array();
		
		
		$TBL_LANGUAGES = Database::get_main_table(TABLE_MAIN_LANGUAGE);
		
		//We are in a course
		if(isset($_SESSION["_course"]["language"])){
			$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='".$_SESSION["_course"]["language"]."'";
		}
		
		//Else, we get the current session language
		elseif(isset($_SESSION["_user"]["language"])){
			$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='".$_SESSION["_user"]["language"]."'";
		}
		
		//Else we get the default platform language
		else{
			$platform_language=api_get_setting("platformLanguage");
			$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='$platform_language'";
		}
		
		$result_sql=api_sql_query($sql);
		$isocode_language=mysql_result($result_sql,0,0);
		$fck_editor->Config['DefaultLanguage'] = $isocode_language;
		
		
		if($_SESSION['_course']['path']!=''){
			$upload_path = api_get_path(REL_COURSE_PATH).$_SESSION['_course']['path'].'/document/';

		}else{
			$upload_path = api_get_path(REL_PATH)."main/upload/";
		}
		
		$fck_editor->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";

		$fck_editor->ToolbarSet = $fck_attribute['ToolbarSet'] ;
		
		$fck_editor->Config['LinkBrowserURL'] = $fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Connector=connectors/php/connector.php&ServerPath=$upload_path";

		//for image
		$fck_editor->Config['ImageBrowserURL'] = $fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Image&Connector=connectors/php/connector.php&ServerPath=$upload_path";

		$fck_editor->Config['ImageUploadURL'] = $fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Image&ServerPath=$upload_path" ;

		//for flash
		$fck_editor->Config['FlashBrowserURL'] = $fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Flash&Connector=connectors/php/connector.php&ServerPath=$upload_path";

		$fck_editor->Config['FlashUploadURL'] = $fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Flash&ServerPath=$upload_path" ;

		//for MP3
		$fck_editor->Config['MP3BrowserURL'] = $fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=MP3&Connector=connectors/php/connector.php&ServerPath=$upload_path";

		$fck_editor->Config['MP3UploadURL'] = $fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=MP3&ServerPath=$upload_path" ;

		//for other media
		$fck_editor->Config['VideoBrowserURL'] = $fck_editor->BasePath . "editor/filemanager/browser/default/browser.html?Type=Video&Connector=connectors/php/connector.php&ServerPath=$upload_path";

		$fck_editor->Config['VideoUploadURL'] = $fck_editor->BasePath . "editor/filemanager/upload/php/upload.php?Type=Video&ServerPath=$upload_path" ;


		$result .=$fck_editor->CreateHtml();


		$result .= '<small><a href="#" onclick="MyWindow=window.open('."'".api_get_path(WEB_CODE_PATH)."help/allowed_html_tags.php?fullpage=". ($this->fullPage ? '1' : '0')."','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=500,height=600,left=200,top=20'".'); return false;">'.get_lang('AllowedHTMLTags').'</a></small>';
		return $result;
	}
}
?>