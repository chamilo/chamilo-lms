<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
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

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
 * Prints an HTML page with a table containing the gradebook data
 * @param	array 	Array containing the data to be printed in the table
 * @param	array	Table headers
 * @param	string	View to print as a title for the table
 * @param	string	Course name to print as title for the table
 */
function print_table ($data_array,$header_names,$view,$coursename) {
	$printdata= '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>'.get_lang('Print').'</title>

<style type="text/css">
body {
	font-size: 12px;
	color: #000;
	margin: 10px;
	padding: 0;
}

a:link {text-decoration: none; font-weight : bold; color : black;}
a:visited {text-decoration: none; font-weight : bold; color : black;}
a:active {text-decoration: none; font-weight : bold;  color : black;}

.data_table{
  	border-collapse: collapse;
	width: 100%;
	padding: 5px;
	border: 1px;
}
.data_table th{
  	padding: 5px;
	vertical-align: top;
  	border-top: 1px solid black;
  	border-bottom: 1px solid black;
  	border-right: 1px solid black;
  	border-left: 1px solid black;
}
.data_table tr.row_odd{
  	background-color: #fafafa;
  }
.data_table tr.row_even{
  	background-color: #fff;
}
.data_table td{
  	padding: 5px;
	  vertical-align: top;
  	border-bottom: 1px solid black;
  	border-right: 1px solid black;
  	border-left: 1px solid black;
}
</style>
</head>
<body><div id="main">';

	$printdata .= '<h2>'.$view.' : '.$coursename.'</h2>';
	$printdata .= '<h3>'.get_lang('Date').' : '.date('j/n/Y g:i').'</h3>';
	$printdata .= '<table border="1" width="90%" cellspacing="1" cellpadding="1">';
	foreach ($header_names as $header) {
		$printdata .= '<th>'.$header.'</th>';

	}
	foreach ($data_array as $data) {
		$printdata .= '<tr>';
		foreach ($data as $rowdata) {
			$printdata .= '<td>'.$rowdata.'</td>';
		}
		$printdata .= '</tr>';

	}
	$printdata .= '</table></div></body></html>';
	return $printdata;
}
/**
 * Exports the data as a table on a PDF page
 * @param	resource	The PDF object (ezpdf class) used to generate the file
 * @param	array		The data array
 * @param	array		Table headers
 * @param	string		Format (portrait or landscape)
 */
function export_pdf($pdf,$newarray,$header_names,$format) {
	$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
	$pdf->ezSetCmMargins(0,0,0,0);
	$pdf->ezSetY(($format=='portrait')?'820':'570');
	$pdf->selectFont(api_get_path(LIBRARY_PATH).'ezpdf/fonts/Courier.afm');
	$pdf->ezText(get_lang('FlatView').' ('. date('j/n/Y g:i') .')',12,array('justification'=>'center'));
	if ($format=='portrait') {
		$pdf->line(40,790,540,790);
		$pdf->line(40,40,540,40);
	} else {
		$pdf->line(40,540,790,540);
		$pdf->line(40,40,790,40);
	}
	$pdf->ezSetY(($format=='portrait')?'750':'520');
	$pdf->ezTable($newarray,$header_names,'',array('showHeadings'=>1,'shaded'=>1,'showLines'=>1,'rowGap'=>3,'width'=>(($format=='portrait')?'500':'750')));
	$pdf->ezStream();

}