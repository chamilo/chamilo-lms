<?php
// $Id: html_editor.php 22027 2009-07-13 11:03:41Z ivantcholakov $
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
	 * @param array $editor_config	Optional configuration settings for the online editor.
	 */
	function HTML_QuickForm_html_editor($elementName = null, $elementLabel = null, $attributes = null, $config = null)
	{
		global $fck_attribute;

		HTML_QuickForm_element :: HTML_QuickForm_element($elementName, $elementLabel, $attributes);
		$this->_persistantFreeze = true;
		$this->_type = 'html_editor';
		$this->fullPage = false;

		$name = $this->getAttribute('name');
		$this -> fck_editor = new FCKeditor($name);

		$this->fck_editor->ToolbarSet = $fck_attribute['ToolbarSet'] ;
		$this -> fck_editor->Width = !empty($fck_attribute['Width']) ? $fck_attribute['Width'] : '990';
		$this -> fck_editor->Height = !empty($fck_attribute['Height']) ? $fck_attribute['Height'] : '400';
		//We get the optionnals config parameters in $fck_attribute array
		$this -> fck_editor->Config = !empty($fck_attribute['Config']) ? $fck_attribute['Config'] : array();

		// This is an alternative (a better) way to pass configuration data to the editor.
		if (is_array($config)) {
			foreach ($config as $key => $value) {
				$this->fck_editor->Config[$key] = $config[$key];
			}
			if (isset($config['ToolbarSet'])) {
				$this->fck_editor->ToolbarSet = $config['ToolbarSet'];
			}
			if (isset($config['Width'])) {
				$this->fck_editor->Width = $config['Width'];
			}
			if (isset($config['Height'])) {
				$this->fck_editor->Height = $config['Height'];
			}
			if (isset($config['FullPage'])) {
				$this->fullPage = $config['FullPage'];
			}
		}
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
		$result .= $this->fck_editor->CreateHtml();

		//Add a link to open the allowed html tags window 
		//$result .= '<small><a href="#" onclick="MyWindow=window.open('."'".api_get_path(WEB_CODE_PATH)."help/allowed_html_tags.php?fullpage=". ($this->fullPage ? '1' : '0')."','MyWindow','toolbar=no,location=no,directories=no,status=yes,menubar=no,scrollbars=yes,resizable=yes,width=500,height=600,left=200,top=20'".'); return false;">'.get_lang('AllowedHTMLTags').'</a></small>';
		return $result;
	}
}
?>
