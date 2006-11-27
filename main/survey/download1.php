<?
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
*	@package dokeos.survey
* 	@author 
* 	@version $Id: download1.php 10223 2006-11-27 14:45:59Z pcool $
*/

$survey_name=$_GET['survey_name'];
$filename ="temp/".$survey_name.".csv";
$survey_namem=$survey_name.".csv";
header('Expires: Wed, 01 Jan 1990 00:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: public');
header('Pragma: no-cache');
header('Content-Type:application/csv');
header('Content-Length: '.filesize($filename));
header('Content-Disposition: attachment; filename='.$survey_namem);
readfile($filename);
?>