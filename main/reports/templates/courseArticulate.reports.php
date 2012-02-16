<?php

$reports_template['CourseArticulate'] = array(
	'description' => 'CourseArticulate',
	'getSQL' => 'reports_template_CourseArticulate_getSQL',
	'wizard' => 
'
<span id="CourseArticulate" class="step">
	<span class="font_normal_07em_black">This report does not need any particular settings</span><br />
	<input type="hidden" class="link" value="format" />
</span>
');

function reports_template_CourseArticulate_getSQL() {
	// settings


	// Nom, prenom
	$query = 'select u.lastname as "Last name", u.firstname as "First name" ';
	$query .= 'from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
	$query .= ' where u.user_id in ('.reports_getVisibilitySQL().') ';
	$query .= ' order by u.user_id ';
	$queries[0] = $query;

	// Custom Field
	foreach (array("tags" => "tags") as $k => $v) { // FIXME
		$query = 'select ufv.field_value  as "'.$v.'" ';
		$query .= 'from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
		$query .= 'left join'.Database::get_main_table(TABLE_MAIN_USER_FIELD).' uf ';
		$query .= ' on uf.field_variable="'.$k.'" ';
		$query .= 'left outer join '.Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES).' ufv ';
		$query .= ' on ufv.user_id = u.user_id and ufv.field_id = uf.id ';
		$query .= 'where u.user_id in ('.reports_getVisibilitySQL().') ';
		$query .= ' order by u.user_id ';
		$queries[] = $query;
	}


	// Stored Value
	$sv = array();
	foreach ($sv as $k => $v) {
                if (!isset($v['sql']))
                        $v['sql'] = 'FIELD';
                $sqlField = str_replace('FIELD', 'sv.sv_value', $v['sql']);
                $query = 'select '.$sqlField.' as "'.$v['title'].'" ';
//		$query = 'select sec_to_time(sv.sv_value) as "'.$v.'" ';
		$query .= 'from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
		$query .= ' left outer join '.Database::get_main_database().'.stored_values sv ';
		$query .= 'on sv.user_id = u.user_id and sv_key = "'.$k.'" ';
		$query .= ' where u.user_id in ('.reports_getVisibilitySQL().') ';
		$query .= ' group by u.user_id ';
		$query .= ' order by u.user_id ';
		$queries[] = $query;
	}

	// first and last connection
	$query = 'select min(tel.login_date) as "First connection", max(tel.logout_date) as "Latest connection"  ';
	$query .= 'from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
	$query .= 'left outer join '.Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN).' tel ';
	$query .= ' on tel.login_user_id = u.user_id ';
	$query .= ' where u.user_id in ('.reports_getVisibilitySQL().') ';
	$query .= ' group by u.user_id ';
	$query .= ' order by u.user_id ';
	$queries[] = $query;

	// SCORM Data
	$scormData = array();
	foreach (CourseManager::get_real_course_list() as $code => $details) {
		$list = Database::query('select l.id as lid, l.name as lname, li.id as liid, li.title as lititle '.
					' from '.Database::get_course_table(TABLE_LP_MAIN, $details['db_name']).' l, '.
					' '.Database::get_course_table(TABLE_LP_ITEM, $details['db_name']).' li '.
					' where l.id = li.lp_id');
		while ($lpItem = Database::fetch_assoc($list)) {
			$scormData[] = array('coursedb' => $details['db_name'],
						'lid' => $lpItem['lid'],
						'liid' => $lpItem['liid'],
						'target_view_count' => 1,
						'target_indicator' => 'score',
						'title' => $details['title'].'/'.$lpItem['lname'].'/'.$lpItem['lititle'].'/1/score',
						'sql' => 'FIELD');
			$scormData[] = array('coursedb' => $details['db_name'],
						'lid' => $lpItem['lid'],
						'liid' => $lpItem['liid'],
						'target_view_count' => 2,
						'target_indicator' => 'score',
						'title' => $details['title'].'/'.$lpItem['lname'].'/'.$lpItem['lititle'].'/2/score',
						'sql' => 'FIELD');
			$scormData[] = array('coursedb' => $details['db_name'],
						'lid' => $lpItem['lid'],
						'liid' => $lpItem['liid'],
						'target_view_count' => null,
						'target_indicator' => 'score',
						'title' => $details['title'].'/'.$lpItem['lname'].'/'.$lpItem['lititle'].'/all/score',
						'sql' => 'avg(FIELD)');
		}
	}

	foreach($scormData as $v) {
                if (!isset($v['sql']))
                        $v['sql'] = 'FIELD';
                $sqlField = str_replace('FIELD', $v['target_indicator'], $v['sql']);
                $query = 'select '.$sqlField.' as "'.$v['title'].'" ';
		$query .= 'from '.Database::get_main_table(TABLE_MAIN_USER).' u ';
		$query .= 'left outer join '.Database::get_course_table(TABLE_LP_VIEW, $details['db_name']).' lv ';
		$query .= ' on u.user_id = lv.user_id and lv.lp_id = '.$v['lid'];
		$query .= ' left outer join '.Database::get_course_table(TABLE_LP_ITEM_VIEW, $details['db_name']).' liv ';
		$query .= ' on lv.id = liv.lp_view_id ';
		if ($v['target_view_count'])
			$query .= ' and liv.view_count = '.$v['target_view_count'];
		$query .= ' and liv.lp_item_id = '.$v['liid'].' ';
		$query .= ' where u.user_id in ('.reports_getVisibilitySQL().') ';
		$query .= ' group by u.user_id ';
		$query .= ' order by u.user_id ';
		$queries[] = $query;
	}		

	return $queries;
}

