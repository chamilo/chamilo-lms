<?php
// $Id: DateCompare.php 6187 2005-09-07 10:23:57Z bmol $
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
require_once 'HTML/QuickForm/Rule/Compare.php';
/**
 * QuickForm rule to compare 2 dates
 */
class HTML_QuickForm_Rule_DateCompare extends HTML_QuickForm_Rule_Compare
{
    /**
     * Validate 2 dates
     * @param array $values Array with the 2 dates. Each element in this array
     * should be an array width keys  F (month), d (day) and Y (year)
     * @param string $operator The operator to use (default '==')
     * @return boolean True if the 2 given dates match the operator
     */
    function validate($values, $operator = null)
    {
    	$date1 = $values[0];
    	$date2 = $values[1];
        $time1 = mktime($date1['H'],$date1['i'],0,$date1['F'],$date1['d'],$date1['Y']);
        $time2 = mktime($date2['H'],$date2['i'],0,$date2['F'],$date2['d'],$date2['Y']);
		return parent::validate(array($time1,$time2),$operator);
    }
}
?>