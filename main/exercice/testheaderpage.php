<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Istvan Mandak
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
============================================================================== 
*/
/**
============================================================================== 
*	Code library for HotPotatoes integration.
*
*	@author Istvan Mandak
*	@package dokeos.exercise
============================================================================== 
*/
	// name of the language file that needs to be included 
$language_file='exercice';
	include('../inc/global.inc.php');
	
	require_once($_configuration['root_sys'].'main/exercice/hotpotatoes.lib.php'); 
	$documentPath= api_get_path(SYS_COURSE_PATH).$_course['path']."/document";
	$title = GetQuizName($_GET['file'],$documentPath);		
	if ($title =='')
		{
			$title = GetFileName($_GET['file']);		
		}
	$nameTools = $title;
	$noPHP_SELF=true;
	$interbreadcrumb[]= array ("url"=>"./exercice.php", "name"=> get_lang('Exercices'));
	Display::display_header($nameTools,"Exercise");
	echo "<a name='TOP'></a>";
	

?>