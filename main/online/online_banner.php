<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

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
*	Dokeos banner
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

// name of the language file that needs to be included
$language_file='chat';

include('../inc/global.inc.php');

$nameTools=get_lang('OnlineConference');

$noPHP_SELF=true;

Display::display_header($nameTools,"Online");
?>

</body>
</html>