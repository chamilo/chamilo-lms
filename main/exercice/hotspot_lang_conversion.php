<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


/**
*	Hotspot languae conversion
*	@package dokeos.exercise
* 	@author
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
*/


include_once('../inc/global.inc.php');

$hotspot_lang_file = api_get_path(SYS_LANG_PATH);

if(isset($_GET['lang'])) {
	//$search = array('../','\\0','\\');
	$lang = urldecode($_GET['lang']);
	if (preg_match('/^[a-zA-Z0-9\._-]+$/', $lang)) {		
		//$lang = str_replace($search,$replace,urldecode($_GET['lang']));
		if(file_exists($hotspot_lang_file . $lang . '/hotspot.inc.php'))
			$hotspot_lang_file .= $lang . '/hotspot.inc.php';
		else
			$hotspot_lang_file .= 'english/hotspot.inc.php';
	}
} else
	$hotspot_lang_file .= 'english/hotspot.inc.php';

$file = file($hotspot_lang_file);

$temp = array();

foreach($file as $value)
{
	$explode = explode('=', $value);

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