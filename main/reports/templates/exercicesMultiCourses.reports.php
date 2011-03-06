<?php

$reports_template['exercicesMultiCourses'] = array(
	'description' => 'Result of each test per student',
	'getSQL' => 'reports_template_exercicesMultiCourses_getSQL',
	'wizard' => 
'
<span id="exercicesMultiCourses" class="step">
	<span class="font_normal_07em_black">Result of each test per student</span><br />

	<label for="scoremin">Score min</label><br />
	<input class="input_field_25em" name="scoremin" id="scoremin" value="0"><br />
	<label for="scoremax">Score max</label><br />
	<input class="input_field_25em" name="scoremax" id="scoremax" value="0"><br />
	<label for="tattempt">How to treat Attempts</label><br />
	<select name="tattempt" id="tattempt">
		<option value="first">take only the first one</option>
		<option value="last">take only the last one</option>
		<option value="average">take the average value</option>
		<option value="min">take the minimum value</option>
		<option value="max">take the maximum value</option>
	</select><br />
	<label name="gcourses">Do you want to group quiz per courses</label><br />
	<select name="gcourses" id="gcourses">
		<option value="nogroup">Do not group</option>
		<option value="average">group and take the average value</option>
		<option value="min">group and take the minimum value</option>
		<option value="max">group and take the maximum value</option>
	</select></br>
	<input type="hidden" class="link" value="format" />
</span>
');

function reports_template_exercicesMultiCourses_getSQL() {
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

	return $query;
}
