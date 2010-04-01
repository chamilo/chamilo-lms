<?php
/* For licensing terms, see /license.txt */

/**
 *	This is a library with some functions to sort tabular data
 *
 *	@package chamilo.library
 */

define('SORT_DATE', 3);
define('SORT_IMAGE', 4);

class TableSort {

	/**
	 * This is a method for date comparison, using hidden raw values if they are given.
	 * Date formats vary a lot, alse they have localized values. For avoiding using
	 * unreliable in this case date parsing routine, this method checks first whether raw
	 * date walues have been intentionaly passed in order precise sorting to be achieved.
	 * Here is the format of the date value, hidden in a comment: <!--uts=1234685716-->
	 * @param string $el1	The first element provided from the table.
	 * @param string $el2	The second element provided from the table.
	 * @result bool			Tre comparison result.
	 * @author Ivan Tcholakov, 2010.
	 */
	public function date_compare($el1, $el2) {
		if (($pos1 = strpos($el1, '<!--uts=')) !== false && ($pos2 = strpos($el1, '-->', $pos1)) !== false) {
			$el1 = intval(substr($el1, $pos1 + 8, $pos2 - $pos1 - 8));
		} else {
			$el1 = strtotime(strip_tags($el1));
		}
		if (($pos1 = strpos($el2, '<!--uts=')) !== false && ($pos2 = strpos($el2, '-->', $pos1)) !== false) {
			$el2 = intval(substr($el2, $pos1 + 8, $pos2 - $pos1 - 8));
		} else {
			$el2 = strtotime(strip_tags($el2));
		}
		if ($el1 > $el2) {
			return 1;
		} elseif ($el1 < $el2) {
			return -1;
		}
		return 0;
	}

	/**
	 * Sorts 2-dimensional table.
	 * @param array $data The data to be sorted.
	 * @param int $column The column on which the data should be sorted (default = 0)
	 * @param string $direction The direction to sort (SORT_ASC (default) or SORT_DESC)
	 * @param constant $type How should data be sorted (SORT_REGULAR, SORT_NUMERIC,
	 * SORT_STRING,SORT_DATE,SORT_IMAGE)
	 * @return array The sorted dataset
	 * @author bart.mollet@hogent.be
	 */
	function sort_table($data, $column = 0, $direction = SORT_ASC, $type = SORT_REGULAR) {
		if (!is_array($data) or count($data) == 0) {
			return array();
		}
        if ($column != strval(intval($column))) {
        	// Probably an attack
        	return $data;
        }
        if (!in_array($direction, array(SORT_ASC, SORT_DESC))) {
        	// Probably an attack
        	return $data;
        }

        $compare_function = '';
        if ($type == SORT_REGULAR) {
			if (TableSort::is_image_column($data, $column)) {
				$type = SORT_IMAGE;
			} elseif (TableSort::is_date_column($data, $column)) {
				$type = SORT_DATE;
			} elseif (TableSort::is_numeric_column($data, $column)) {
				$type = SORT_NUMERIC;
			} else {
				$type = SORT_STRING;
			}
        }

		switch ($type) {
			case SORT_NUMERIC :
				$compare_function = 'strip_tags($el1) > strip_tags($el2)';
				break;
			case SORT_IMAGE :
				$compare_function = 'api_strnatcmp(api_strtolower(strip_tags($el1,"<img>")),api_strtolower(strip_tags($el2,"<img>"))) > 0';
				break;
			case SORT_DATE :
				$compare_function = 'TableSort::date_compare($el1, $el2) > 0';
				break;
            case SORT_STRING :
            default:
                $compare_function = 'api_strnatcmp(api_strtolower(strip_tags($el1)),api_strtolower(strip_tags($el2))) > 0';
                break;
		}

		$function_body = '$el1 = $a['.$column.']; $el2 = $b['.$column.']; return '.($direction == SORT_ASC ? ' ' : ' !').'('.$compare_function.');';

		// Sort the content
		usort($data, create_function('$a,$b', $function_body));

		return $data;
	}

	/**
	 * Checks whether a column of a 2D-array contains only numeric values
	 * @param array $data The data-array
	 * @param int $column The index of the column to check
	 * @return bool true if column contains only dates, false otherwise
	 * @todo Take locale into account (eg decimal point or comma ?)
	 * @author bart.mollet@hogent.be
	 */
	function is_numeric_column($data, $column) {
		$is_numeric = true;

		foreach ($data as $index => & $row) {
			$is_numeric &= is_numeric(strip_tags($row[$column]));
			if (!$is_numeric) {
				break;
			}
		}

		return $is_numeric;
	}

	/**
	 * Checks whether a column of a 2D-array contains only dates (GNU date syntax)
	 * @param array $data The data-array
	 * @param int $column The index of the column to check
	 * @return bool true if column contains only dates, false otherwise
	 * @author bart.mollet@hogent.be
	 */
	function is_date_column($data, $column) {
		$is_date = true;
		foreach ($data as $index => & $row) {
			if (strpos($row[$column], '<!--uts=') !== false) {
				// A hidden raw date value (an integer Unix time stamp) has been detected. It is needed for precise sorting.
				$is_date &= true;
			} elseif (strlen(strip_tags($row[$column])) != 0) {
				$check_date = strtotime(strip_tags($row[$column]));
				// strtotime Returns a timestamp on success, FALSE otherwise.
				// Previous to PHP 5.1.0, this function would return -1 on failure.
				$is_date &= ($check_date != -1 && $check_date != false);
			} else {
				$is_date &= false;
			}
			if (!$is_date) {
				break;
			}
		}
		return $is_date;
	}

	/**
	 * Checks whether a column of a 2D-array contains only images (<img src="
	 * path/file.ext" alt=".."/>)
	 * @param array $data The data-array
	 * @param int $column The index of the column to check
	 * @return bool true if column contains only images, false otherwise
	 * @author bart.mollet@hogent.be
	 */
	function is_image_column($data, $column) {
		$is_image = true;
		foreach ($data as $index => & $row) {
			$is_image &= strlen(trim(strip_tags($row[$column], '<img>'))) > 0; // at least one img-tag
			$is_image &= strlen(trim(strip_tags($row[$column]))) == 0; // and no text outside attribute-values
			if (!$is_image) {
				break;
			}
		}
		return $is_image;
	}

	/**
	 * Sorts 2-dimensional table. It is possile changing the columns that will be shown and the way that the columns are to be sorted.
	 * @param array $data The data to be sorted.
	 * @param int $column The column on which the data should be sorted (default = 0)
	 * @param string $direction The direction to sort (SORT_ASC (default) orSORT_DESC)
	 * @param array $column_show The columns that we will show in the table i.e: $column_show=array('1','0','1') we will show the 1st and the 3th column.
	 * @param array $column_order Changes how the columns will be sorted ie. $column_order=array('1','4','3','4') The 2nd column will be sorted like the 4 column
	 * @param constant $type How should data be sorted (SORT_REGULAR, SORT_NUMERIC,SORT_STRING,SORT_DATE,SORT_IMAGE)	 *
	 * @return array The sorted dataset
	 * @author bart.mollet@hogent.be
	 */
	function sort_table_config($data, $column = 0, $direction = SORT_ASC, $column_show = null, $column_order = null, $type = SORT_REGULAR) {
        if (!is_array($data) or count($data) == 0) {
        	return array();
        }
        if ($column != strval(intval($column))) {
        	// Probably an attack
        	return $data;
        }
        if (!in_array($direction, array(SORT_ASC, SORT_DESC))) {
        	// Probably an attack
        	return $data;
        }

        $compare_function = '';
		// Change columns sort
	 	// Here we say that the real way of how the columns are going to be order is manage by the $column_order array
	 	if (is_array($column_order)) {
			for ($i = 0; $i < count($column_order); $i++) {
				if ($column == $i + 1) {
					$column = $column_order[$i];
				}
			}
	 	}

		if ($type == SORT_REGULAR) {
			if (TableSort::is_image_column($data, $column)) {
				$type = SORT_IMAGE;
			} elseif (TableSort::is_date_column($data, $column)) {
				$type =  SORT_DATE;
			} elseif (TableSort::is_numeric_column($data, $column)) {
				$type =  SORT_NUMERIC;
			} else {
				$type = SORT_STRING;
			}
		}

	 	switch ($type) {
			case SORT_NUMERIC :
				$compare_function = 'strip_tags($el1) > strip_tags($el2)';
				break;
			case SORT_IMAGE :
				$compare_function = 'api_strnatcmp(api_strtolower(strip_tags($el1,"<img>")),api_strtolower(strip_tags($el2,"<img>"))) > 0';
				break;
			case SORT_DATE :
				$compare_function = 'TableSort::date_compare($el1, $el2) > 0';
				break;
            case SORT_STRING :
            default:
                $compare_function = 'api_strnatcmp(api_strtolower(strip_tags($el1)),api_strtolower(strip_tags($el2))) > 0';
                break;
		}

		$function_body = '$el1 = $a['.$column.']; $el2 = $b['.$column.']; return '.($direction == SORT_ASC ? ' ' : ' !').'('.$compare_function.');';

		// Sort the content
		usort($data, create_function('$a,$b', $function_body));

		if (is_array($column_show)) {
			// We show only the columns data that were set up on the $column_show array
			$new_order_data = array();
			$count_data = count($data);
			$count_column_show = count($column_show);
			for ($j = 0; $j < $count_data; $j++) {
				$k = 0;
				for ($i = 0; $i < $count_column_show; $i++) {
					if ($column_show[$i]) {
						$new_order_data[$j][$k] = $data[$j][$i];
					}
					$k++;
				}
			}
			// Replace the multi-arrays
			$data = $new_order_data;
		}

		return $data;
	}

}
