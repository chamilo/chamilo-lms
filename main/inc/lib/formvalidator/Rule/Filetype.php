<?php
// $Id: Filetype.php 20456 2009-05-10 17:27:44Z ivantcholakov $
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
 * QuickForm rule to check if a filetype
 */
class HTML_QuickForm_Rule_Filetype extends HTML_QuickForm_Rule
{
	/**
	 * Function to check if a filetype is allowed
	 * @see HTML_QuickForm_Rule
	 * @param array $file Uploaded file
	 * @param array $extensions Allowed extensions
	 * @return boolean True if filetype is allowed
	 */
	function validate($file,$extensions = array())
	{
		$parts = explode('.',$file['name']);
		if( count($parts) < 2 )
		{
			return false;	
		}
		$ext = $parts[count($parts)-1];
		return api_in_array_nocase($ext, $extensions);
	}
}
?>
