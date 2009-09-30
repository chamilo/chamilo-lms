<?php // $Id: exampletables.php,v 1.2 2006/03/15 14:34:45 pcool Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Vrije Universiteit Brussel (VUB)

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
*	This file is a code template;
*	copy the code and paste it in a new file to begin your own work.
*
*	@package dokeos.plugin
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// global settings initialisation
// also provides access to main api (inc/lib/main_api.lib.php)
include("../inc/global.inc.php");

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/

//main_api.lib.php by default included
//also the display and database libraries are loaded by default

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

$nameTools = "Table examples (for developers)"; // title of the page (should come from the language file)
Display::display_header($nameTools);
/*
-----------------------------------------------------------
	Constants
-----------------------------------------------------------
*/

define ("REPEAT_COUNT", "5");


/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

api_display_tool_title($nameTools);

$row = 0;
$column_header[$row++] = "Column 1";
$column_header[$row++] = "Column 2";
$column_header[$row++] = "Column 3";

/*
	An important parameter for display_complex_table_header
	is $properties, an array with elements, all of which have defaults

	"width" - the table width, e.g. "85%"
	"class" - the class to use for the table, e.g. "class=\"data_table\""
				by default class is  "class=\"data_table\""
	"cellpadding" - the extra border in each cell, e.g. "8"
*/

/*
-----------------------------------------------------------
	Table that hilites
-----------------------------------------------------------
*/

Display::display_normal_message("The following table hilites on mouseover (hover), this is the Display API default.");

Display::display_complex_table_header($properties, $column_header);
for ($i = 0; $i < REPEAT_COUNT; $i++)
{
	$row = 0;
	$table_row[$row++] = "First";
	$table_row[$row++] = "Second";
	$table_row[$row++] = "Third";
	Display::display_table_row($bgcolor, $table_row, true);
}
Display::display_table_footer();

echo "<br/><br/>";

/*
-----------------------------------------------------------
	Table that alternates row colours
-----------------------------------------------------------
*/
Display::display_normal_message("The following table has alternating row colours and no hilite");

$properties["class"] = ""; //no hilite
$bgcolour = Display::display_complex_table_header($properties, $column_header);
for ($i = 0; $i < REPEAT_COUNT; $i++)
{
	$row = 0;
	$table_row[$row++] = "First";
	$table_row[$row++] = "Second";
	$table_row[$row++] = "Third";
	$bgcolour = Display::display_table_row($bgcolour, $table_row, true);
}
Display::display_table_footer();

/*
==============================================================================
		FOOTER
==============================================================================
*/

Display::display_footer();
?>