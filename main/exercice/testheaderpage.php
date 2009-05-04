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
*	Code library for HotPotatoes integration.
*	@package dokeos.exercise
* 	@author Istvan Mandak
* 	@version $Id: testheaderpage.php 20279 2009-05-04 15:55:58Z juliomontoya $
*/

// name of the language file that needs to be included
$language_file='exercice';

include('../inc/global.inc.php');

	require_once(api_get_path(SYS_CODE_PATH).'exercice/hotpotatoes.lib.php');
	$documentPath= api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
	$my_file = Security::remove_XSS($_GET['file']);
	$title = GetQuizName($my_file,$documentPath);
	if ($title =='') {
		$title = GetFileName($my_file);
	}
	$nameTools = $title;
	$noPHP_SELF=true;
	if (isset($_SESSION['gradebook'])){
		$gradebook=	$_SESSION['gradebook'];
	}
	
	if (!empty($gradebook) && $gradebook=='view') {	
		$interbreadcrumb[]= array (
				'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
				'name' => get_lang('Gradebook')
			);
	}	
	$interbreadcrumb[]= array ("url"=>"./exercice.php", "name"=> get_lang('Exercices'));
	Display::display_header($nameTools,"Exercise");
	echo "<a name='TOP'></a>";
?>