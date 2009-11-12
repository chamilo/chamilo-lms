<?php //$id:$
/* For licensing terms, see /dokeos_license.txt */
//error_log(__FILE__);
/**
*	Hotspot languae conversion
*	@package dokeos.exercise
* 	@author
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/

session_cache_limiter("none");

include_once('../inc/global.inc.php');

$hotspot_lang_file = api_get_path(SYS_LANG_PATH);

$search = array('../','\\0');

if(file_exists($hotspot_lang_file . $language_interface . '/hotspot.inc.php'))
	$hotspot_lang_file .= $language_interface . '/hotspot.inc.php';
else
	$hotspot_lang_file .= 'english/hotspot.inc.php';


$file = file($hotspot_lang_file);

$temp = array();

foreach($file as $value)
{
	$explode = explode('=', $value , 2);

	if(count($explode) > 1)
	{
		$explode[0] = trim($explode[0]);
		$explode[0] = '&' . substr($explode[0], 1, strlen($explode[0]));

		$explode[1] = trim($explode[1]);
		$explode[1] = substr($explode[1], 0, strlen($explode[1]) - 1);
		$explode[1] = ereg_replace('"', '', $explode[1]);

		$temp[] = $explode[0] . '=' . $explode[1];
	}
}

foreach($temp as $value)
{
	echo $value . ' ';
}
?>
