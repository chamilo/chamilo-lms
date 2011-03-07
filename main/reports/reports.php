<?php
/* For licensing terms, see /license.txt */

/**
* Reports
* @author Arnaud Ligot <arnaud@cblue.be>
* @copyrights CBLUE SPRL 2011
* @package chamilo.reports
*/

// name of the language file that needs to be included
$language_file = array ('index', 'tracking', 'userInfo', 'admin', 'gradebook'); // FIXME
$cidReset = true;

// including files 
require_once '../inc/global.inc.php';
require_once 'reports.lib.php';

// protect script
api_block_anonymous_users();

// defining constants

// current section
$this_section = SECTION_REPORTS;

// setting the name of the tool
$tool_name=get_lang('Reports');

// loading templates
reports_loadTemplates();

// outputing a link to csv file instead of outputing csv data directly
if ($_REQUEST['format'] == 'csv')  {
	// converting post vars to get uri
	$params = '';
	$kv = array();
	foreach ($_POST as $key => $value)
		if ($key != 'format')
			$kv[] = $key.'='.urlencode($value);
	$query_string = join("&", $kv);
	die('<a href="reports.php?format=downloadcsv&'.$query_string.'">download file</a>');
} else if ($_REQUEST['format'] == 'downloadcsv') {
	header('content-type: application/csv'); // fixme
	$_REQUEST['format'] = 'csv';
}



if (is_array($reports_template[$_REQUEST['type']])) {
	$query = $reports_template[$_REQUEST['type']]['getSQL']();
	if ($_REQUEST['format'] == 'sql')
		die($query);
	$result = Database::query($query);
} else {
	die('<b>'.get_lang('error while building your report').'</b>');
}

if ($_REQUEST['format'] == 'html') {
	echo '<script type="text/javascript" charset="utf-8">
			$(document).ready(function() {
				$("#reportsData").dataTable();
			} );
		</script>';
	echo '<table id="reportsData" class="display">'; // FIXME style
	$nfields = mysql_num_fields($result);
	$columns = array();	
	$columns_islink = array();
	echo '<thead><tr>';
	for ($i=0; $i < $nfields; $i++)	{
		$columns[$i] = mysql_field_name($result, $i);
		if (substr($columns[$i], -5, 5) != '_link') {
			$column_islink[$i] = false;
			echo '<th>'.$columns[$i].'</th>';
		} else 
			$columns_islink[$i] = true;
	}

	// checking resolving link column id
	$columns_flip = array_flip($columns);
	$columns_link = array();
	for ($i=0; $i < $nfields; $i++)
		if ($column_islink[$i] == false && array_key_exists($columns[$i].'_link', $columns_flip))
			$columns_link[$i] = $columns_flip[$columns[$i].'_link'];
		else
			$columns_link[$i] = '';
	error_log("result1: ".$nfields);
	echo '</tr></thead><tbody>';
	while ($row = Database::fetch_row($result)) {
		echo '<tr>';
		for ($i = 0; $i<$nfields; $i++)
			if (!$columns_islink[$i]){ // ignore links
				if ($columns_link[$i] != '') // link is defined
					echo '<td><a href="'.$row[$columns_link[$i]].'">'.$row[$i].'</a></td>'; 
				else
					echo '<td>'.$row[$i].'</td>';
			}
		echo "</tr>\n";
	}
	echo '</tbody></table>';
} else if ($_REQUEST['format'] == 'csv') {
	$nfields = mysql_num_fields($result);
	$columns = array();	
	$columns_islink = array();
	for ($i=0; $i < $nfields; $i++)	{
		$columns[$i] = mysql_field_name($result, $i);
		if (substr($columns[$i], -5, 5) != '_link') {
			$column_islink[$i] = false;
			echo $columns[$i].',';
		} else 
			$columns_islink[$i] = true;
	}

	echo "\n";
	while ($row = Database::fetch_row($result)) {
		for ($i = 0; $i<$nfields; $i++)
			if (!$columns_islink[$i]) // ignore links
				echo $row[$i].',';  // fixme
		echo "\n";
	}
} else die('format unknown');

?>
