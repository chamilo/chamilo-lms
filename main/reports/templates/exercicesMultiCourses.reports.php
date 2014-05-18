<?php

$reports_template['exercicesMultiCourses'] = array(
	'description' => 'Result of each test per student',
	'getSQL' => 'reports_template_exercicesMultiCourses_getSQL',
	'wizard' => 
'
<span id="exercicesMultiCourses" class="step">
	<span class="font_normal_07em_black">Result of each test per student</span><br />

<!--	<label for="scoremin">Score min</label><br />
	<input class="input_field_25em" name="scoremin" id="scoremin" value="0"><br />
	<label for="scoremax">Score max</label><br />
	<input class="input_field_25em" name="scoremax" id="scoremax" value="0"><br />
-->	<label for="tattempt">How to treat Attempts</label><br />
	<select name="tattempt" id="tattempt">
<!--		<option value="first">take only the first one</option>
		<option value="last">take only the last one</option>
-->		<option value="avg">take the average value</option>
		<option value="min">take the minimum value</option>
		<option value="max">take the maximum value</option>
	</select><br />
<!--	<label name="gcourses">Do you want to group quiz per courses</label><br />
	<select name="gcourses" id="gcourses">
		<option value="nogroup">Do not group</option>
		<option value="average">group and take the average value</option>
		<option value="min">group and take the minimum value</option>
		<option value="max">group and take the maximum value</option>
	</select></br>
-->	<input type="hidden" class="link" value="format" />
</span>
');

function reports_template_exercicesMultiCourses_getSQL() {
	// foreach quiz
	$result = array();
	$columns = Database::query('select r.id as kid, c.title as course, '.
		'r.child_name as test from '.
		Database::get_main_table(TABLE_MAIN_REPORTS_KEYS).' r, '.
		Database::get_main_table(TABLE_MAIN_COURSE).' c '.
		'where r.course_id=c.id and r.tool_id='.
		reports_getToolId(TOOL_QUIZ).
		' order by r.course_id, r.child_name');
	if (Database::num_rows($columns) == 0)
		die('<b>'.get_lang('no data found').'</b>');
	$query = 'select u.lastname Name, u.firstname Firstname';
	$columns = Database::store_result($columns);
	if ($_REQUEST['tattempt'] == 'min' || $_REQUEST['tattempt'] == 'max')
		$function = $_REQUEST['tattempt'];
	else
		$function = 'avg';
	foreach ($columns as $key => $column)
		$query .= ', '.$function.'(k'.$key.'.score) as `'.
				$column['course'].' - '.
				$column['test'].'` '; 
	$query .= ' from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
	foreach ($columns as $key => $column) // fixme sessions
		$query .= 'left outer join '.
			Database::get_main_table(TABLE_MAIN_REPORTS_VALUES).
			' k'.$key.
			' on k'.$key.'.key_id = '.$column['kid'].
				' and k'.$key.'.user_id = u.user_id ';
	$query .= ' group by ';
	foreach ($columns as $key => $column) // grouping attempt
		$query .= 'k'.$key.'.attempt, ';
	$query = substr($query, 0, -2); // removing last ', ';


	return $query;
}
