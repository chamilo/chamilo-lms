<?php
/*
==============================================================================
	Chamilo - elearning and course management software

	Copyright (c) 2010 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Mail: info@chamilo.com
==============================================================================
*/

function get_table_type_course($type,$course) {
	global $_configuration;
	global $table_evaluated;
	return Database::get_course_table($table_evaluated[$type][0],$_configuration['db_prefix'].$course);
}

function get_printable_data($users,$alleval, $alllinks) {
	$datagen = new FlatViewDataGenerator ($users, $alleval, $alllinks);
	$offset = isset($_GET['offset']) ? $_GET['offset'] : '0';
	$count = (($offset + 10) > $datagen->get_total_items_count()) ? ($datagen->get_total_items_count() - $offset) : 10;
	$header_names = $datagen->get_header_names($offset, $count);
	$data_array = $datagen->get_data(FlatViewDataGenerator :: FVDG_SORT_LASTNAME, 0, null, $offset, $count, true);
	$newarray = array();
	foreach ($data_array as $data) {
		$newarray[] = array_slice($data, 1);
	}
	return array ($header_names, $newarray);
}

/**
 * XML-parser: handle character data
 */
 
function character_data($parser, $data) {
	global $current_value;
	$current_value= $data;
}

/**
 * XML-parser: handle end of element
 */
 
function element_end($parser, $data) {
	global $user;
	global $users;
	global $current_value;
	switch ($data) {
	case 'Result' :
		$users[]= $user;
		break;
	default :
		$user[$data]= $current_value;
		break;
	}
}

/**
 * XML-parser: handle start of element
 */
 
function element_start($parser, $data) {
	global $user;
	global $current_tag;
	switch ($data) {
	case 'Result' :
		$user= array ();
		break;
	default :
		$current_tag= $data;
	}
}

function overwritescore($resid, $importscore, $eval_max) {
	$result= Result :: load($resid);
	if ($importscore > $eval_max) {
		header('Location: gradebook_view_result.php?selecteval=' .Security::remove_XSS($_GET['selecteval']) . '&overwritemax=');
		exit;
	}
	$result[0]->set_score($importscore);
	$result[0]->save();
	unset ($result);
}

/**
 * Read the XML-file
 * @param string $file Path to the XML-file
 * @return array All userinformation read from the file
 */
 
function parse_xml_data($file) {
	global $current_tag;
	global $current_value;
	global $user;
	global $users;
	$users= array ();
	$parser= xml_parser_create();
	xml_set_element_handler($parser, 'element_start', 'element_end');
	xml_set_character_data_handler($parser, "character_data");
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
	xml_parse($parser, file_get_contents($file));
	xml_parser_free($parser);
	return $users;
}

