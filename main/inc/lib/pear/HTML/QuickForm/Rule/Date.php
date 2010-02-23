<?php
// $Id: Date.php 6187 2005-09-07 10:23:57Z bmol $
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
require_once ('HTML/QuickForm/Rule.php');
/**
 * QuickForm rule to check a date
 */
class Html_Quickform_Rule_Date extends HTML_QuickForm_Rule
{
	/**
	 * Function to check a date
	 * @see HTML_QuickForm_Rule
	 * @param array $date An array with keys F (month), d (day) and Y (year)
	 * @return boolean True if date is valid
	 */
	function validate($date)
	{
		$compareDate = create_function('$a', 'return checkdate($a[\'M\'],$a[\'d\'],$a[\'Y\']);');
        return $compareDate($date);
	}
}
?>
