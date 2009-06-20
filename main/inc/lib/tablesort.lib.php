<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2008 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)
	
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
*	This is a library with some functions to sort tabular data
*
*	@package dokeos.library
============================================================================== 
*/

define('SORT_DATE', 3);
define('SORT_IMAGE',4);

class TableSort
{
	/**
	 * Create a string to use in sorting.
	 * @param string $txt The string to convert
	 * @author Ren� Haentjens
	 */
	function orderingstring($txt)
	{
		return api_strtolower($txt);
	}
	/**
	 * Sort 2-dimensional table.
	 * @param array $data The data to be sorted.
	 * @param int $column The column on which the data should be sorted (default = 0)
	 * @param string $direction The direction to sort (SORT_ASC (default) or SORT_DESC)
	 * @param constant $type How should data be sorted (SORT_REGULAR, SORT_NUMERIC,
	 * SORT_STRING,SORT_DATE,SORT_IMAGE)
	 * @return array The sorted dataset
	 * @author bart.mollet@hogent.be
	 */
	function sort_table($data, $column = 0, $direction = SORT_ASC, $type = SORT_REGULAR)
	{
		if(!is_array($data) or count($data)==0){return array();}
        if($column != strval(intval($column))){return $data;} //probably an attack
        if(!in_array($direction,array(SORT_ASC,SORT_DESC))){return $data;} // probably an attack
        $compare_function = '';
        if ($type == SORT_REGULAR)
        {
			if (TableSort::is_image_column($data, $column))
			{
				$type = SORT_IMAGE;
			}
			elseif (TableSort::is_date_column($data, $column))
			{
				$type = SORT_DATE;
			}
			elseif (TableSort::is_numeric_column($data, $column))
			{
				$type = SORT_NUMERIC;
			}
			else
			{
				$type = SORT_STRING;
			}
        }
		switch ($type)
		{
			case SORT_NUMERIC :
				$compare_function = 'strip_tags($el1) > strip_tags($el2)';
				break;
			case SORT_IMAGE :
				$compare_function = 'api_strnatcmp(TableSort::orderingstring(strip_tags($el1,"<img>")),TableSort::orderingstring(strip_tags($el2,"<img>"))) > 0';
				break;
			case SORT_DATE :
				$compare_function = 'strtotime(strip_tags($el1)) > strtotime(strip_tags($el2))';
				break;
            case SORT_STRING :
            default:
                $compare_function = 'api_strnatcmp(TableSort::orderingstring(strip_tags($el1)),TableSort::orderingstring(strip_tags($el2))) > 0';
                break;
		}
		$function_body = '$el1 = $a['.$column.']; $el2 = $b['.$column.']; return ('.$direction.' == SORT_ASC ? ('.$compare_function.') : !('.$compare_function.'));';
		// Sort the content
				
		usort($data, create_function('$a,$b', $function_body));
		
		return $data;
	}
	/**
	 * Checks if a column of a 2D-array contains only numeric values
	 * @param array $data The data-array
	 * @param int $column The index of the column to check
	 * @return bool true if column contains only dates, false otherwise
	 * @todo Take locale into account (eg decimal point or comma ?)
	 * @author bart.mollet@hogent.be
	 */
	function is_numeric_column($data, $column)
	{
		$is_numeric = true;

		foreach ($data as $index => $row)
		{
			$is_numeric &= is_numeric(strip_tags($row[$column]));
		}

		return $is_numeric;
	}
	/**
	 * Checks if a column of a 2D-array contains only dates (GNU date syntax)
	 * @param array $data The data-array
	 * @param int $column The index of the column to check
	 * @return bool true if column contains only dates, false otherwise
	 * @author bart.mollet@hogent.be
	 */
	function is_date_column($data, $column)
	{	
		$is_date = true;
		foreach ($data as $index => $row)
		{
			if(strlen(strip_tags($row[$column])) != 0 )
			{
				$check_date = strtotime(strip_tags($row[$column]));
				// strtotime Returns a timestamp on success, FALSE otherwise. 
				// Previous to PHP 5.1.0, this function would return -1 on failure. 
				$is_date &= ($check_date != -1 && $check_date != false);
			}
			else
			{
				$is_date &= false;	
			}
		}
		return $is_date;
	}
	/**
	 * Checks if a column of a 2D-array contains only images (<img src="
	 * path/file.ext" alt=".."/>)
	 * @param array $data The data-array
	 * @param int $column The index of the column to check
	 * @return bool true if column contains only images, false otherwise
	 * @author bart.mollet@hogent.be
	 */
	function is_image_column($data, $column)
	{
		$is_image = true;
		foreach ($data as $index => $row)
		{
			$is_image &= strlen(trim(strip_tags($row[$column],'<img>'))) > 0; // at least one img-tag
			$is_image &= strlen(trim(strip_tags($row[$column]))) == 0; // and no text outside attribute-values
		}
		return $is_image;
	}
	
	
	/**
	 * Sort 2-dimensional table. It is possile of change the columns that will be show and the way that the columns are sorted.
	 * @param array $data The data to be sorted.
	 * @param int $column The column on which the data should be sorted (default = 0)
	 * @param string $direction The direction to sort (SORT_ASC (default) orSORT_DESC)
	 * @param array $column_show The columns that we will show in the table i.e: $column_show=array('1','0','1') we will show the 1st and the 3th column. 
	 * @param array $column_order Changes how the columns will be sorted ie. $column_order=array('1','4','3','4') The 2nd column will be sorted like the 4 column
	 * @param constant $type How should data be sorted (SORT_REGULAR, SORT_NUMERIC,SORT_STRING,SORT_DATE,SORT_IMAGE)	 * 
	 * @return array The sorted dataset
	 * @author bart.mollet@hogent.be
	 */
		
	function sort_table_config($data, $column = 0, $direction = SORT_ASC, $column_show=null, $column_order=null,$type = SORT_REGULAR)
	{
        if(!is_array($data) or count($data)==0){return array();}
        if($column != strval(intval($column))){return $data;} //probably an attack
        if(!in_array($direction,array(SORT_ASC,SORT_DESC))){return $data;} // probably an attack
        $compare_function = '';		
		// Change columns sort 			 
	 	// Here we say that the real way of how the columns are going to be order is manage by the $column_order array
	 	if(is_array($column_order)) 
	 	{
			for($i=0;$i<count($column_order);$i++)
			{
				if ($column== $i+1)
				{
					$column=$column_order[$i];
				}			
			}
	 	}
					
		switch ($type)
		{
			case SORT_REGULAR :
				if (TableSort::is_image_column($data, $column))
				{
					return TableSort::sort_table_config($data, $column, $direction, $column_show, $column_order,SORT_IMAGE);
				}
				elseif (TableSort::is_date_column($data, $column))
				{
					return TableSort::sort_table_config($data, $column, $direction, $column_show, $column_order,SORT_DATE);
				}
				elseif (TableSort::is_numeric_column($data, $column))
				{
					return TableSort::sort_table_config($data, $column, $direction, $column_show, $column_order,SORT_NUMERIC);
				}
				return TableSort::sort_table_config($data, $column, $direction, $column_show, $column_order,SORT_STRING);
				break;
			case SORT_NUMERIC :
				$compare_function = 'strip_tags($el1) > strip_tags($el2)';
				break;
			case SORT_IMAGE :
				$compare_function = 'strnatcmp(TableSort::orderingstring(strip_tags($el1,"<img>")),TableSort::orderingstring(strip_tags($el2,"<img>"))) > 0';
				break;
			case SORT_DATE :
				$compare_function = 'strtotime(strip_tags($el1)) > strtotime(strip_tags($el2))';
				break;
            case SORT_STRING :
            default:
                $compare_function = 'strnatcmp(TableSort::orderingstring(strip_tags($el1)),TableSort::orderingstring(strip_tags($el2))) > 0';
                break;
		}		
				
		$function_body = '$el1 = $a['.$column.']; ' .
						 '$el2 = $b['.$column.']; ' .
						 'return ('.$direction.' == SORT_ASC ? ('.$compare_function.') : !('.$compare_function.'));';

		// Sort the content
		usort($data, create_function('$a,$b', $function_body));
		
		// We show only the columns data that were set up on the $column_show array		
		$new_order_data=array();
		
		if(is_array($column_show))
		{		
			
			for ($j=0;$j<count($data);$j++)
			{
				$k=0;				
				for ($i=0;$i<count($column_show);$i++)
				{
					if ($column_show[$i])
					{
						$new_order_data[$j][$k]=$data[$j][$i];					
					}
					$k++;						
				}			
			}			
			//replace the multi-arrays
			$data=$new_order_data;
		}
		else
		{
			return $data;			
		}		
		return $data;
	}
	
}
?>
