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

if ($_REQUEST['format'] == 'csv')  {
	// converting post vars to get uri
	$params = '';
	$kv = array();
	foreach ($_POST as $key => $value)
		if ($key != 'format')
			$kv[] = $key.'='.urlencode($value);
	$query_string = join("&", $kv);
	echo '<a href="reports.php?format=downloadcsv&'.$query_string.'">download file</a>';
} else if ($_REQUEST['format'] == 'downloadcsv') {
	header('content-type: application/csv'); // fixme
	$_REQUEST['format'] = 'csv';
}

if ($_REQUEST['type'] == "exercicesMultiCourses") {
	// foreach quiz
	$result = array();
	// fixme database name
	$columns = Database::query('select r.id as kid, c.title as course, r.child_name as test from reports_keys r, course c where r.course_id=c.id order by r.course_id, r.child_name');
	if (Database::num_rows($columns) == 0)
		die('<b>'.get_lang('no data found').'</b>');
	$query = 'select u.user_id, u.lastname, u.firstname ';
	$columns = Database::store_result($columns);
	foreach ($columns as $key => $column)
		$query .= ', avg(k'.$key.'.score) as `'.$column['course'].'-'.$column['test'].'` '; // FIXME function
	$query .= ' from user u ';
	foreach ($columns as $key => $column) // fixme sessions
		$query .= 'left outer join reports_values k'.$key.' on k'.$key.'.key_id = '.$column['kid'].' and k'.$key.'.uid = u.user_id ';
	if ($_REQUEST['format'] == 'sql')
		die($query);
	$result = Database::query($query);
} else {
	die('<b>'.get_lang('error while building your report').'</b>');
}

if ($_REQUEST['format'] == 'html') {
	echo '<table border="1">'; // FIXME style
	while ($row = Database::fetch_row($result)) {
		echo '<tr>';
		foreach ($row as $col)
			echo '<td>'.$col.'</td>'; 
		echo '</tr>';
	}
	echo '</table>';
} else if ($_REQUEST['format'] == 'csv') {
	while ($row = Database::fetch_row($result)) {
		foreach ($row as $col)
			echo $col.',';  // fixme
		echo "\n";
	}
} else die('format unknown');

?>
