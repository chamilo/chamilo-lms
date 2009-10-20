<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This is the array library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================
*/

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/


/**
 * Removes duplicate values from a dimensional array
 *
 * @param array a dimensional array
 * @return array an array with unique values
 */
function array_unique_dimensional($array)
{
    if(!is_array($array))
		return $array;

    foreach ($array as &$myvalue){
        $myvalue=serialize($myvalue);
    }

    $array=array_unique($array);

    foreach ($array as &$myvalue){
        $myvalue=unserialize($myvalue);
    }
    return $array;
}
?>