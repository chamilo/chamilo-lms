<?php
/*
----------------------------------------------------------------------
Dokeos - elearning and course management software

Copyright (c) 2004 Dokeos S.A.
Copyright (c) Denes Nagy (darkden@freemail.hu)

For a full list of contributors, see "credits.txt".
The full license can be read in "license.txt".

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

See the GNU General Public License for more details.

Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
----------------------------------------------------------------------
*/
/**
*
* The starter frame of the new browser window. In case of Onload and onunload, the frameset of thisit modifies a
* variable in the opener window.
*
* @author   Denes Nagy <darkden@freemail.hu>
* @version  v 0.1
* @access   public
*	@package dokeos.scorm
*/

$langFile = "scorm";
include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

header('Content-Type: text/html; charset='. $charset);  ?>

<html><head>
<script type='text/javascript'>

</script>
<link rel='stylesheet' type='text/css' href='../css/scorm.css' />
</head>

<body bgcolor='#EEEEEE'>
All the Tools you need are in the original Dokeos window...
</body></html>