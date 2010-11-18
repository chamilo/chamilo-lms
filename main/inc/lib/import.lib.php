<?php
/* For licensing terms, see /license.txt */
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
	function csv_to_array($filename) {
		$result = array();
		$handle = fopen($filename, 'r');
		if ($handle === false) {
			return $result;
		}
		// Modified by Ivan Tcholakov, 01-FEB-2010.
		//$keys = fgetcsv($handle, 4096, ";");
		$keys = api_fgetcsv($handle, null, ';');
		//
		// Modified by Ivan Tcholakov, 01-FEB-2010.
		//while (($row_tmp = fgetcsv($handle, 4096, ";")) !== FALSE) {
		while (($row_tmp = api_fgetcsv($handle, null, ';')) !== false) {
		//
			$row = array();
			//avoid empty lines in csv
			if (is_array($row_tmp) && count($row_tmp) > 0 && $row_tmp[0] != '') {
				if (!is_null($row_tmp[0])) {
					foreach ($row_tmp as $index => $value) {
						$row[$keys[$index]] = $value;
					}
					$result[] = $row;
				}
			}
		}
		fclose($handle);
		return $result;
	}
}
