<?php
// $Id: select_language.php 19251 2009-03-24 21:23:16Z cfasanando $
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
require_once ('HTML/QuickForm/select.php');
/**
* A dropdownlist with all languages to use with QuickForm
*/
class HTML_QuickForm_Select_Language extends HTML_QuickForm_select
{
	/**
	 * Class constructor
	 */
	function HTML_QuickForm_Select_Language($elementName=null, $elementLabel=null, $options=null, $attributes=null)
	{
		parent::HTML_QuickForm_Select($elementName, $elementLabel, $options, $attributes);
		// Get all languages
		$languages = api_get_languages();
		$this->_options = array();
		$this->_values = array();
		foreach ($languages['name'] as $index => $name)
		{
			if($languages['folder'][$index] == api_get_setting('platformLanguage')) {
				$this->addOption($name,$languages['folder'][$index],array('selected'=>'selected'));
			} else {
				$this->addOption($name,$languages['folder'][$index]);
			}
		}
	}
}
?>