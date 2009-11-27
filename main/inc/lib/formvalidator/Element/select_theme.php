<?php
// $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
require_once ('HTML/QuickForm/select.php');
/**
* A dropdownlist with all themes to use with QuickForm
*/
class HTML_QuickForm_Select_Theme extends HTML_QuickForm_select
{
	/**
	 * Class constructor
	 */
	function HTML_QuickForm_Select_Theme($elementName=null, $elementLabel=null, $options=null, $attributes=null)
	{
		parent::HTML_QuickForm_Select($elementName, $elementLabel, $options, $attributes);
		// Get all languages
		$themes = api_get_themes();
		$this->_options = array();
		$this->_values = array();
		$this->addOption('--',''); // no theme select
		for ($i=0; $i< count($themes[0]);$i++)
		{
			$this->addOption($themes[1][$i],$themes[0][$i]);
		}
		/*foreach ($themes as $theme)
		{
			$this->addOption((empty($theme)?'--':$theme),$theme);
		}*/
	}
}
?>