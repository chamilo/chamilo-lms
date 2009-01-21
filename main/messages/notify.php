<?php  // $Id: notify.php 9491 2006-10-13 09:03:16Z evie_em $
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) Facultad de Matematicas, UADY (México)
	Copyright (c) Evie, Free University of Brussels (Belgium)
	
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
  include_once('../../main/inc/global.inc.php');
  include_once("functions.inc.php");
  header("Cache-Control: no-cache, must-revalidate");
  echo get_new_messages();
?>