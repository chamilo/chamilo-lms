<?php

$reports_template['Generic'] = array(
	'description' => 'Generic',
	'getSQL' => 'reports_template_Generic_getSQL',
	'wizard' => 
'
<span id="Generic" class="step">
	<span class="font_normal_07em_black">This report does not need any particular settings</span><br />
	<input type="hidden" class="link" value="format" />
</span>
');

function reports_template_Generic_getSQL() {
	// settings


	// Nom, prenom
	$query = 'select u.lastname as "Last name", u.firstname as "First name" ';
	$query .= 'from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
	$query .= ' where u.user_id in ('.reports_getVisibilitySQL().') ';
	$query .= ' order by u.user_id ';
	$queries[0] = $query;


	
	// Custom Field
	foreach (array(10 => "description") as $k => $v) { 
		$query = 'select ufv.field_value  as "'.$v.'" ';
		$query .= 'from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
		$query .= 'left outer join '.Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES).' ufv ';
		$query .= ' on ufv.user_id = u.user_id and ufv.field_id = '.$k;
		$query .= ' where u.user_id in ('.reports_getVisibilitySQL().') ';
		$query .= ' order by u.user_id ';
		$queries[] = $query;
	}

	// Stored Value
	$sv = array();
	foreach ($sv as $k => $v) {
		$query = 'select sec_to_time(sv.sv_value) as "'.$v.'" ';
		$query .= 'from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
		$query .= ' left outer join '.Database::get_main_database().'.stored_values sv ';
		$query .= 'on sv.user_id = u.user_id and sv_key = "'.$k.'" ';
		$query .= ' where u.user_id in ('.reports_getVisibilitySQL().') ';
		$query .= ' order by u.user_id ';
		$queries[] = $query;
	}
	
	// premiere connexion
	$query = 'select min(tel.login_date) as "First connection", max(tel.logout_date) as "Latest connection"  ';
	$query .= 'from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
	$query .= 'left outer join '.Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN).' tel ';
	$query .= ' on tel.login_user_id = u.user_id ';
	$query .= ' where u.user_id in ('.reports_getVisibilitySQL().') ';
	$query .= ' group by u.user_id ';
	$query .= ' order by u.user_id ';
	$queries[] = $query;

	return $queries;
}

