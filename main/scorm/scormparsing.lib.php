<?php //$id: $
/*
----------------------------------------------------------------------
Dokeos - elearning and course management software

Copyright (c) 2005 Dokeos S.A.
Copyright (c) Yannick Warnier (yannick.warnier@dokeos.com)
Copyright (c) Denes Nagy (darkden@freemail.hu)

For a full list of contributors, see "credits.txt".
The full license can be read in "license.txt".

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

See the GNU General Public License for more details.

Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
----------------------------------------------------------------------
*/
/**
============================================================================== 
*	 Library containing functions to parse the imsmanifest.xml file that goes
* along every SCORM content.
*
*	@author   Denes Nagy <darkden@freemail.hu>
*	@access   public
*
*	@package dokeos.scorm
*
============================================================================== 
*/

/**
 * Sets the href string value of the element whose identifierref field equals the given identifier. 
 * Modifies the $items array.
 * @param	string	Identifier seeked
 * @param	string href link to set in the $items array 
 * @return	void	Nothing
 */ 
function put_it_to_items($ident,$href) { //from the resources array, it puts the href to the appropriate place in items array
	global $items;
	$i=1;
	while ($items[$i]) {
		if ($items[$i]['identifierref']==$ident) { $items[$i]['href']=$href; }
		$i++;
	}
}

/**
 * This handles the start tags. Modifies the $items array.
 * name is the name of the tag, if more attributes than $attribs is an array
 * @param	resource	Parser handler
 * @param	string		Name of the tag
 * @param	mixed		String or array of attributes
 * @return  void	Nothing
 */ 
function startElement($parser, $name, $attribs)
{
   global $xml_parser, $defaultorgref, $defaultorgtitle, $inorg, $initem, $intitle, $inmeta, $items, $itemindex, $tabledraw, $inversion, $prereq, $previouslevel, $clusterinfo, $ingeneral;

   if ($tabledraw) {
     echo "<tr><td>";
     echo xml_get_current_line_number($xml_parser);
     echo "</td><td>$inorg</td><td>$initem</td>";
   }

   if ($name=='ORGANIZATIONS') {
    list($key, $value) = each($attribs);
    $defaultorgref=$value;
   }
   if (($name=='ORGANIZATION') or ($name=='tableofcontents')) {
    $inorg=true;
   }
   if ($name=='SCHEMAVERSION') {
    $inversion=true;
   }
   if ($name=='ADLCP:PREREQUISITES') {
    $prereq=true;
   }
   if ($name=='METADATA') {
    $inmeta=true;
   }
   if ($name=='GENERAL') {
    $ingeneral=true;
   }


   if ($name=='ITEM') {
	  $initem++;
	  $itemindex++;
      while (list($key, $value) = each($attribs)) {
		if ($key=='IDENTIFIERREF') { $items[$itemindex]['identifierref']=$value;  }
		if ($key=='IDENTIFIER') { $items[$itemindex]['identifier']=$value;  }
		if ($key=='PARAMETERS') { $items[$itemindex]['parameters']=$value;  }
		if ($key=='TITLE') { $items[$itemindex]['title']=$value; }
	  }
 	  $items[$itemindex]['index']=$itemindex;
 	  $items[$itemindex]['level']=$initem;

	  if ($initem==$previouslevel) { $clusterinfo++; }
	  if ($initem>$previouslevel) { $clusterinfo=$clusterinfo*10+1; $previouslevel=$initem; }
	  if ($initem<$previouslevel) { $clusterinfo=floor($clusterinfo/10)+1; $previouslevel=$initem; }
	  $items[$itemindex]['clusterinfo']=$clusterinfo;
   }
   if ($name=='TITLE') {
	$intitle=true;
   }
   if ($name=='RESOURCE') {  //by the time it gets there, all the items are already read (overpassed)
      while (list($key, $value) = each($attribs)) {
		if ($key=='HREF') { $href=$value;  }
		if ($key=='IDENTIFIER') { $ident=$value;  }
	  }
	  put_it_to_items($ident,$href);
   }

   reset($attribs);  //drawing the table
   if ($tabledraw) {
     echo "<td><font color='#0000cc'>$name</font></td>";
     if (sizeof($attribs)) {
         while (list($k, $v) = each($attribs)) {
             echo "<td><font color='#009900'>$k</font>=<font color='#990000'>$v</font></td>";
         }
     }
   }

}

/**
 * Handles the end tags
 * @param	resource	Parser handler
 * @param	string	Name of the closing tag
 * @return void Nothing
 */ 
function endElement($parser, $name)
{
   global $inorg,$initem,$inmeta,$ingeneral,$intitle,$inversion,$prereq;

   if (($name=='ORGANIZATION') or ($name=='tableofcontents')) {
	$inorg=false;
   }
   if ($name=='ITEM') {
	$initem--;
   }
   if ($name=='METADATA') {
	$inmeta==false;
   }
   if ($name=='GENERAL') {
	$ingeneral==false;
   }
   if ($name=='TITLE') {
	$intitle=false;
   }
   if ($name=='SCHEMAVERSION') {
	$inversion=false;
   }
   if ($name=='ADLCP:PREREQUISITES') {  //this is only good for scorm 1.2
		$prereq=false;
   }

}

/**
 * handles the data between start and and tags. Modifies the $items array.
 * note : if f.ex. Steering & sailing is the data, then this function is called 3 times !!!
 * @param		resource	Parser handler
 * @param		string	The data between start and stop tags
 * @return	void	Nothing 
 */ 
function characterData($parser, $data)
{
   global $defaultorgtitle, $initem, $intitle, $inmeta, $inorg, $items, $itemindex, $inversion, $version, $prereq, $ingeneral;
   //echo "<td>int:$intitle ino:$inorg ini:$initem inm:$inmeta</td>";
   if (($intitle==true) and ($inorg==true) and ($initem==false) and ($ingeneral==false)) {
	    $defaultorgtitle.=$data;
        return;
   }
   if (($intitle==true) and ($initem==true)) {
		$items[$itemindex]['title'].=$data;
		return;
 	}
   if ($inversion==true) {
		$version=$data; //this will provide the last schemaversion tag version number only !
		return;
 	}
   if ($prereq==true) {
		$items[$itemindex]['prereq']=$data;
	}

}

/**
 * Creates a new xml parser and returns an array containing the parser resource and the xml file resource.
 * @param		string	Path to the XML file we want to parse
 * @return	mixed		Array containing the parser handler and the XML file handler. 
 */ 
function new_xml_parser($request_file)
{
   global $parser_file;

   $xml_parser = xml_parser_create();
   xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 1);
   xml_set_element_handler($xml_parser, "startElement", "endElement");  //telling which function will handle start and end tags
   xml_set_character_data_handler($xml_parser, "characterData");

   if (!($fp = @fopen($request_file, "r"))) {
       return false;
   }
   if (!is_array($parser_file)) {
       settype($parser_file, "array");
   }
   $parser_file[$xml_parser] = $request_file;
   return array($xml_parser, $fp);
}
?>