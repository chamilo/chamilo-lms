<?php
/* For licensing terms, see /license.txt */

ob_start();

// names of the language file that needs to be included
$language_file = array ('registration', 'index', 'trad4all', 'tracking', 'admin');
$cidReset = true;
require_once '../inc/global.inc.php';
require_once 'myspace.lib.php';

$this_section = SECTION_TRACKING;

$nameTools = get_lang('Teachers');

api_block_anonymous_users();
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
Display :: display_header($nameTools);

$formateurs = array();
if (api_is_drh() || api_is_platform_admin()) {

	// followed teachers by drh
	$formateurs = UserManager::get_users_followed_by_drh($_user['user_id'], COURSEMANAGER);
    $menu_items = array();
	$menu_items[] = Display::url(Display::return_icon('stats.png', get_lang('MyStats'),'',ICON_SIZE_MEDIUM),api_get_path(WEB_CODE_PATH)."auth/my_progress.php" );	 
	$menu_items[] = Display::url(Display::return_icon('user.png', get_lang('Students'), array(), 32), "index.php?view=drh_students&amp;display=yourstudents");
	$menu_items[] = Display::return_icon('teacher_na.png', get_lang('Trainers'), array(), 32);
	$menu_items[] = Display::url(Display::return_icon('course.png', get_lang('Courses'), array(), 32), 'course.php');
	$menu_items[] = Display::url(Display::return_icon('session.png', get_lang('Sessions'), array(), 32), 'session.php');	
		
	echo '<div class="actions">';
	$nb_menu_items = count($menu_items);
	if ($nb_menu_items > 1) {
		foreach ($menu_items as $key => $item) {
			echo $item;		
		}
	}	
	if (count($formateurs) > 0) {
		echo '<span style="float:right">';
		echo Display::url(Display::return_icon('printer.png', get_lang('Print'), array(), 32), 'javascript: void(0);', array('onclick'=>'javascript: window.print();'));
		echo Display::url(Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), array(), 32), api_get_self().'?export=xls');
		echo '</span>';			
	}
	echo '</div>';
	echo Display::page_subheader(get_lang('YourTeachers'));	
}

if (!api_is_drh()) {
	api_display_tool_title($nameTools);
}

/**
 * MAIN PART
 */

if (isset($_POST['export'])) {
	$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
} else {
	$is_western_name_order = api_is_western_name_order();
}
$sort_by_first_name = api_sort_by_first_name();

if (!api_is_drh() && !api_is_platform_admin()) {
	$order_clause = $sort_by_first_name ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
	if (isset($_GET["teacher_id"]) && $_GET["teacher_id"] != 0) {
		$teacher_id = intval($_GET["teacher_id"]);
		$sql_formateurs = "SELECT user_id,lastname,firstname,email
			FROM $tbl_user
			WHERE user_id='$teacher_id'".$order_clause;
	} else {
		$sql_formateurs = "SELECT user_id,lastname,firstname,email
			FROM $tbl_user
			WHERE status = 1".$order_clause;
	}
	
	$result_formateurs = Database::query($sql_formateurs);
	if (Database::num_rows($result_formateurs) > 0) {
		while ($row_formateurs = Database::fetch_array($result_formateurs)) {
			$formateurs[] = $row_formateurs;	
		}
	}
}

$a_last_week = get_last_week();
$last_week 	 = date('Y-m-d',$a_last_week[0]).' '.get_lang('To').' '.date('Y-m-d', $a_last_week[6]);

$time_filter = 'last_week';
$time_label = get_lang('TimeSpentLastWeek').'<br />'.$last_week;
        
if (isset($_GET['time_filter'])) {
    if ($_GET['time_filter'] == 'last_month') {
        $time_filter = $_GET['time_filter'];        
        $time_label = get_lang('TimeSpentLastMonth');
    }
}

//echo Display::url(get_lang('LastMonth'), api_get_self().'?time_filter=last_month', array('class' => 'btn'));
//echo ' '.Display::url(get_lang('LastWeek'), api_get_self().'?time_filter=last_week', array('class' => 'btn'));

if ($is_western_name_order) {
	echo '<table class="data_table"><tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.$time_label.'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
} else {
	echo '<table class="data_table"><tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.$time_label.'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
}

if ($is_western_name_order) {
	$header[] = get_lang('FirstName');
	$header[] = get_lang('LastName');
} else {
	$header[] = get_lang('LastName');
	$header[] = get_lang('FirstName');
}

$header[] = get_lang('TimeSpentLastWeek');
$header[] = get_lang('Email');

$data = array();

if (count($formateurs) > 0) {

	$i = 1;
	foreach ($formateurs as $formateur) {
		$user_id = $formateur["user_id"];
		$lastname = $formateur["lastname"];
		$firstname = $formateur["firstname"];
		$email = $formateur["email"];

		if ($i % 2 == 0) {
			$css_class = "row_odd";

			if ($i % 20 == 0 && $i != 0) {
				if ($is_western_name_order) {
					echo '<tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
				} else {
					echo '<tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.get_lang('Email').'</th><th>'.get_lang('AdminCourses').'</th><th>'.get_lang('Students').'</th></tr>';
				}
			}
		} else {
			$css_class = "row_even";
		}

		$i++;

		if ($is_western_name_order) {
			$data[$user_id]["firstname"] = $firstname;
			$data[$user_id]["lastname"] = $lastname;
		} else {
			$data[$user_id]["lastname"] = $lastname;
			$data[$user_id]["firstname"] = $firstname;
		}
		
		$time_on_platform = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($user_id, $time_filter));
		$data[$user_id]["timespentlastweek"] = $time_on_platform;
		$data[$user_id]["email"] = $email;

		if ($is_western_name_order) {
			echo '<tr class="'.$css_class.'"><td>'.$firstname.'</td><td>'.$lastname.'</td><td align="right">'.$time_on_platform.'</td><td align="right"><a href="mailto:'.$email.'">'.$email.'</a></td><td align="right"><a href="course.php?user_id='.$user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td><td align="right"><a href="student.php?user_id='.$user_id.'&amp;display=yourstudents"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td></tr>';
		} else {
			echo '<tr class="'.$css_class.'"><td>'.$lastname.'</td><td>'.$firstname.'</td><td align="right">'.$time_on_platform.'</td><td align="right"><a href="mailto:'.$email.'">'.$email.'</a></td><td align="right"><a href="course.php?user_id='.$user_id.'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td><td align="right"><a href="student.php?user_id='.$user_id.'&amp;display=yourstudents"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td></tr>';
		}
	}
} else {
	// No results
	echo '<tr><td colspan="6" "align=center">'.get_lang("NoResults").'</td></tr>';
}
echo '</table>';

if (isset($_POST['export']) || (api_is_drh() && isset($_GET['export']))) {
	MySpace::export_csv($header, $data, 'teachers.csv');
}

if (!api_is_drh()) {
	echo "<form method='post' action='teachers.php'><input type='submit' name='export' value='".get_lang('exportExcel')."'/><form>";
}
Display::display_footer();