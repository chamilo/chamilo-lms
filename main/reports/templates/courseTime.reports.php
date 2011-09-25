<?php

$reports_template['courseTime'] = array(
	'description' => 'Time spent by students in each courses',
	'getSQL' => 'reports_template_courseTime_getSQL',
	'wizard' => 
'
<span id="courseTime" class="step">
	<span class="font_normal_07em_black">This test does not need any particular settings</span><br />
	<input type="hidden" class="link" value="format" />
</span>
');

function reports_template_courseTime_getSQL() {
	// fetch columns
	$result = array();
	$query = 'select r.id as kid, c.title as course '.
		'from '.
		Database::get_main_table(TABLE_MAIN_REPORTS_KEYS).' r, '.
		Database::get_main_table(TABLE_MAIN_COURSE).' c '.
		'where r.course_id=c.id and r.tool_id is null and r.child_id is null'.
		' order by c.title';
	$columns = Database::query($query);
	if (Database::num_rows($columns) == 0)
		die('<b>'.get_lang('no data found: '.$query).'</b>');
	$columns = Database::store_result($columns);

	// fetch data
	$query = 'select u.lastname Name, u.firstname Firstname';
	foreach ($columns as $key => $column)
		$query .= ', sec_to_time(k'.$key.'.report_time) as `'.
				$column['course'].'` '; 
	$query .= ' from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
	foreach ($columns as $key => $column) // fixme sessions
		$query .= 'left outer join '.
			Database::get_main_table(TABLE_MAIN_REPORTS_VALUES).
			' k'.$key.
			' on k'.$key.'.key_id = '.$column['kid'].
				' and k'.$key.'.user_id = u.user_id ';
	return $query;
}
