<?php // $Id: XMLencode.php 4083 2005-04-06 19:54:16Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

 Copyright (c) 2005 Dokeos S.A.
 Copyright (c) 2005 Warnier Yannick
 Copyright (c) 2004 Denes Nagy

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
*	@package dokeos.scorm
============================================================================== 
*/
/**
 * Gets the encoding of the XML file given
 * @param   string  File path
 * @return  string  Encoding found
 * @author  imandak80, main author
 * @author  Yannick Warnier <ywarnier@beeznest.org>, fixes
 * @date    unknown, reviewed on 6 April 2005
*/
	function GetXMLEncode($file)
	{
			if (!($fp = fopen($file, "r"))) {
   				echo "could not open XML input : $file";
			}
            
			$fline = fgets($fp);
    
    // if some Windows special chars are found, return Windows encoding
			fseek($fp,0);
			$thefile=fread($fp,filesize($file));
			if (strpos($thefile,'&#233;') or strpos($thefile,'&#235;')) { return('windows-1252'); }

    // else get the string located between double quotes after string "ing=" (for "encoding")
    $match = array(); //initialize result var
			preg_match('/encoding="([0-9a-zA-Z-]*)"/i',$fline,$match); //find quoted encoding
			return $match[1];   // return with encoding type
	}
?>