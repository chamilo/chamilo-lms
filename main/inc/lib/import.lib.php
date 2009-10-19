<?php
// $Id: import.lib.php 13806 2007-11-28 06:29:03Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004,2005 Dokeos S.A.
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
/**
==============================================================================
* This class provides some functions which can be used when importing data from
* external files into Dokeos
* @package	 dokeos.library
==============================================================================
*/
class Import
{
	/**
	 * Reads a CSV-file into an array. The first line of the CSV-file should
	 * contain the array-keys.
	 * Example:
	 *   FirstName;LastName;Email
	 *   John;Doe;john.doe@mail.com
	 *   Adam;Adams;adam@mail.com
	 *  returns
	 *   $result [0]['FirstName'] = 'John';
	 *   $result [0]['LastName'] = 'Doe';
	 *   $result [0]['Email'] = 'john.doe@mail. com';
	 *   $result [1]['FirstName'] = 'Adam';
	 *   ...
	 * @param string $filename Path to the CSV-file which should be imported
	 * @return array An array with all data from the CSV-file
	 */
	function csv_to_array($filename)
	{
		$result = array ();
		$handle = fopen($filename, "r");
		if($handle === false)
		{
			return $result;
		}
		$keys = fgetcsv($handle, 1000, ";");
		while (($row_tmp = fgetcsv($handle, 1000, ";")) !== FALSE)
		{

			$row = array ();
			foreach ($row_tmp as $index => $value)
			{
				$row[$keys[$index]] = $value;
			}
			$result[] = $row;
		}
		fclose($handle);
		return $result;
	}
}
?>