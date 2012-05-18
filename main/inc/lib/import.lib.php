<?php
/* For licensing terms, see /license.txt */
/**
 * This class provides some functions which can be used when importing data from
 * external files into Chamilo.
 * @package	 chamilo.library
 */
/**
 * Class
 * @package	 chamilo.library
 */
class Import {
    
    static function csv_reader($path)
    {
        return new CsvReader($path);
    }

	/**
	 * Reads a CSV-file into an array. The first line of the CSV-file should contain the array-keys.
	 * The encoding of the input file is tried to be detected.
	 * The elements of the returned array are encoded in the system encoding.
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
	 * @param string $filename	The path to the CSV-file which should be imported.
	 * @return array			Returns an array (in the system encoding) that contains all data from the CSV-file.
     * 
     * 
     * @deprecated use cvs_reader instead
	 */
	function csv_to_array($filename) {
		$result = array();

		// Encoding detection.

		$handle = fopen($filename, 'r');
		if ($handle === false) {
			return $result;
		}
		$buffer = array();
		$i = 0;
		while (!feof($handle) && $i < 200) {
			// We assume that 200 lines are enough for encoding detection.
			$buffer[] = fgets($handle);
			$i++;
		}
		fclose($handle);
		$buffer = implode("\n", $buffer);
		$from_encoding = api_detect_encoding($buffer);
		unset($buffer);

		// Reading the file, parsing and importing csv data.

		$handle = fopen($filename, 'r');
		if ($handle === false) {
			return $result;
		}
		$keys = api_fgetcsv($handle, null, ';');
		foreach ($keys as $key => &$key_value) {
			$key_value = api_to_system_encoding($key_value, $from_encoding);
		}
		while (($row_tmp = api_fgetcsv($handle, null, ';')) !== false) {
			$row = array();
			// Avoid empty lines in csv.
			if (is_array($row_tmp) && count($row_tmp) > 0 && $row_tmp[0] != '') {
				if (!is_null($row_tmp[0])) {
					foreach ($row_tmp as $index => $value) {
						$row[$keys[$index]] = api_to_system_encoding($value, $from_encoding);
					}
					$result[] = $row;
				}
			}
		}
		fclose($handle);
		return $result;
	}
}
