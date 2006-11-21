<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
* This is the debug library for Dokeos.
* Include/require it in your code to use its functionality.
*
* debug functions
*
* @package dokeos.library
============================================================================== 
*/

/**
 * This function displays the contend of a variable, array or object in a nicely formatted way.
 *
 * @param $variable a variable, array or object
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 */
function debug($variable)
{
	echo '<pre>';
	print_r($variable);
	echo '</pre>';
}


function printVar($var, $varName = "@")
{
	GLOBAL $DEBUG;
	if ($DEBUG)
	{
		echo "<blockquote>\n";
		echo "<b>[$varName]</b>";
		echo "<hr noshade size=\"1\" style=\"color:blue\">";
		echo "<pre style=\"color:red\">\n";
		var_dump($var);
		echo "</pre>\n";
		echo "<hr noshade size=\"1\" style=\"color:blue\">";
		echo "</blockquote>\n";
	}
	else
	{
		echo "<!-- DEBUG is OFF -->";
		echo "DEBUG is OFF";
	}
}
?>