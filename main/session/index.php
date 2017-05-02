<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
*   Session view
*   @package chamilo.session
*   @author Julio Montoya <gugli100@gmail.com>  Beeznest
*/

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

if (empty($_GET['session_id'])) {
    api_not_allowed();
}

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']): null;
$sessionField = new ExtraFieldValue('session');
$valueAllowVisitors = $sessionField->get_values_by_handler_and_field_variable(
    $session_id,
    'allow_visitors'
);
$allowVisitors = $valueAllowVisitors != false;

if (!$allowVisitors) {
    // Only users who are logged in can proceed.
    api_block_anonymous_users();
}

$this_section = SECTION_COURSES;
$htmlHeadXtra[] = api_get_jqgrid_js();
$course_id  = isset($_GET['course_id'])  ? intval($_GET['course_id']) : null;
Session::write('id_session', $session_id);

// Clear the exercise session just in case
Session::erase('objExercise');

$userId = api_get_user_id();
$session_info = SessionManager::fetch($session_id);
$session_list = SessionManager::get_sessions_by_coach(api_get_user_id());
$course_list = SessionManager::get_course_list_by_session_id($session_id);

$userIsGeneralCoach = SessionManager::user_is_general_coach($userId, $session_id);

$user_course_list = array();
foreach ($course_list as $course) {
    $status = SessionManager::get_user_status_in_course_session(
        $userId,
        $course['real_id'],
        $session_id
    );
    if ($status !== false || api_is_platform_admin() || $userIsGeneralCoach) {
        $user_course_list[] = $course['real_id'];
    }
}

if (empty($user_course_list)) {
    api_not_allowed(true);
}

$my_session_list = array();
$final_array = array();
$new_course_list = array();

if (!empty($course_list)) {
    foreach ($course_list as $course_data) {
        if (api_is_platform_admin()) {
            $course_data['title'] = Display::url(
                $course_data['title'],
                api_get_course_url($course_data['code'], $session_id)
            );
        } else {
            if (in_array($course_data['real_id'], $user_course_list) || api_is_anonymous()) {
                $course_data['title'] = Display::url(
                    $course_data['title'],
                    api_get_course_url($course_data['code'], $session_id)
                );
            } else {
                continue;
            }
        }

        $list = new LearnpathList(
            api_get_user_id(),
            $course_data['code'],
            $session_id,
            'lp.publicatedOn ASC',
            true,
            null,
            true
        );

        $lp_list = $list->get_flat_list();
        $lp_count = 0;

        if (!empty($lp_list)) {
            foreach ($lp_list as $valLp) {
                if ($valLp['lp_visibility']) {
                    $lp_count++;
                }
            }
        }

        $course_info = api_get_course_info($course_data['code']);
        $exercise_count = count(
            ExerciseLib::get_all_exercises(
                $course_info,
                $session_id,
                true,
                null,
                false,
                1
            )
        );

        $max_mutation_date = '';
        $last_date = Tracking::get_last_connection_date_on_the_course(
            api_get_user_id(),
            $course_info,
            $session_id,
            false
        );

        $icons = '';
        foreach ($lp_list as $item) {
            if (empty($item['modified_on'])) {
                $lp_date_original = $item['created_on'];
                $image = 'new.gif';
                $label = get_lang('LearnpathAdded');
            } else {
                $lp_date_original = $item['modified_on'];
                $image = 'moderator_star.png';
                $label = get_lang('LearnpathUpdated');
            }

            $mutation_date = api_strtotime($item['publicated_on']) > api_strtotime($lp_date_original) ? $item['publicated_on'] : $lp_date_original;

            if (api_strtotime($mutation_date) > api_strtotime($max_mutation_date)) {
                $max_mutation_date = $mutation_date;
            }

            if (strtotime($last_date) < strtotime($lp_date_original)) {
                if (empty($icons)) {
                    $icons .= ' '.Display::return_icon(
                        $image,
                        get_lang('TitleNotification').': '.$label.' - '.$lp_date_original
                    ).' ';
                }
            }
        }

        $new_course_list[] = array(
            'title' => $course_data['title'].$icons,
            //  'recent_lps' => $icons,
            //'max_mutation_date' => substr(api_get_local_time($max_mutation_date),0,10),
            'exercise_count' => $exercise_count,
            'lp_count' => $lp_count
        );
    }
}

//If session is not active we stop de script
if (api_is_coach_of_course_in_session($session_id) == false) {
    //If session is not active we stop de script
    if (!api_is_allowed_to_session_edit()) {
        api_not_allowed(true);
    }
}

$entityManager = Database::getManager();
$session = $entityManager->find('ChamiloCoreBundle:Session', $session_id);
$sessionTitleLink = api_get_configuration_value('courses_list_session_title_link');

if ($sessionTitleLink == 2 && $session->getNbrCourses() === 1) {
    $sessionCourses = $session->getCourses();
    $sessionCourse = $sessionCourses[0]->getCourse();
    $courseUrl = $sessionCourse->getDirectory() . '/index.php?';
    $courseUrl .= http_build_query([
        'id_session' => $session->getId()
    ]);

    header('Location: ' . api_get_path(WEB_COURSE_PATH) . $courseUrl);
    exit;
}

Display::display_header(get_lang('Session'));

$session_select = array();
foreach ($session_list as $item) {
    $session_select[$item['id']] =  $item['name'];
}

// Session list form
if (count($session_select) > 1) {
    $form = new FormValidator('exercise_admin', 'get', api_get_self().'?session_id='.$session_id);
    $form->addElement(
        'select',
        'session_id',
        get_lang('SessionList'),
        $session_select,
        'onchange="javascript:change_session()"'
    );
    $defaults['session_id'] = $session_id;
    $form->setDefaults($defaults);
    $form->display();
}

if (empty($session_id)) {
    $user_list = UserManager::get_user_list();
} else {
    $user_list = SessionManager::get_users_by_session($session_id);
}

//Final data to be show
$my_real_array = $new_exercises = array();
$now = time();
$courseList = SessionManager::get_course_list_by_session_id($session_id);

if (!empty($courseList)) {
    foreach ($courseList as $courseInfo) {
        $courseCode = $courseInfo['code'];
        $courseId = $courseInfo['real_id'];

        $isSubscribed = CourseManager::is_user_subscribed_in_course(
            api_get_user_id(),
            $courseCode,
            true,
            $session_id
        );

        $exerciseList = ExerciseLib::get_all_exercises_for_course_id(
            $courseInfo,
            $session_id,
            $courseId,
            true
        );

        if (!empty($exerciseList)) {
            // Exercises
            foreach ($exerciseList as $exerciseInfo) {
                $exerciseId = $exerciseInfo['id'];

                if ($exerciseInfo['start_time'] == '0000-00-00 00:00:00') {
                    $start_date  = '-';
                } else {
                    $start_date = $exerciseInfo['start_time'];
                }

                $best_score_data = ExerciseLib::get_best_attempt_in_course(
                    $exerciseInfo['id'],
                    $courseInfo['real_id'],
                    $session_id
                );

                $best_score = '';
                if (!empty($best_score_data)) {
                    $best_score = ExerciseLib::show_score(
                        $best_score_data['exe_result'],
                        $best_score_data['exe_weighting']
                    );
                }

                $exerciseResultInfo = Event::getExerciseResultsByUser(
                    $userId,
                    $exerciseId,
                    $courseId,
                    $session_id
                );

                if (empty($exerciseResultInfo)) {
                    // We check the date validation of the exercise if the user can make it
                    if ($exerciseInfo['start_time'] != '0000-00-00 00:00:00') {
                        $allowed_time = api_strtotime($exerciseInfo['start_time'], 'UTC');
                        if ($now < $allowed_time) {
                            continue;
                        }
                    }

                    $name = Display::url(
                        $exerciseInfo['title'],
                        api_get_path(WEB_CODE_PATH) . "exercise/overview.php?cidReq=$courseCode&exerciseId={$exerciseId}&id_session=$session_id",
                        array('target' => SESSION_LINK_TARGET)
                    );

                    $new_exercises[] = array(
                        'status' => Display::return_icon(
                            'star.png',
                            get_lang('New'),
                            array('width' => ICON_SIZE_SMALL)
                        ),
                        'date' => $exerciseInfo['start_time'],
                        'course' => $courseInfo['title'],
                        'exercise' => $name,
                        'attempt' => '-',
                        'result' => '-',
                        'best_result' => '-',
                        'position' => '-'
                    );
                    continue;
                }

                // Exercise results
                $counter = 1;
                foreach ($exerciseResultInfo as $result) {
                    $platform_score = ExerciseLib::show_score(
                        $result['exe_result'],
                        $result['exe_weighting']
                    );
                    $my_score = 0;
                    if (!empty($result['exe_weighting']) &&
                        intval($result['exe_weighting']) != 0
                    ) {
                        $my_score = $result['exe_result']/$result['exe_weighting'];
                    }
                    $position = ExerciseLib::get_exercise_result_ranking(
                        $my_score,
                        $result['exe_id'],
                        $exerciseId,
                        $courseCode,
                        $session_id,
                        $user_list
                    );

                    $name = Display::url(
                        $exerciseInfo['title'],
                        api_get_path(WEB_CODE_PATH) . "exercise/result.php?cidReq=$courseCode&id={$result['exe_id']}&id_session=$session_id&show_headers=1",
                        array('target' => SESSION_LINK_TARGET, 'class'=>'exercise-result-link')
                    );

                    $my_real_array[] = array(
                        'status' => Display::return_icon(
                            'quiz.png',
                            get_lang('Attempted'),
                            '',
                            ICON_SIZE_SMALL
                        ),
                        'date' => $start_date,
                        'course' => $courseInfo['title'],
                        'exercise' => $name,
                        'attempt' => $counter,
                        'result' => $platform_score,
                        'best_result' => $best_score,
                        'position' => $position,
                    );
                    $counter++;
                }
            }
        }
    }
}


$my_real_array = msort($my_real_array, 'date', 'asc');

if (!empty($new_exercises)) {
    $my_real_array = array_merge($new_exercises, $my_real_array);
}

$start = $end = $start_only = $end_only ='';

if (!empty($session_info['access_start_date'])) {
    $start = api_convert_and_format_date($session_info['access_start_date'], DATE_FORMAT_SHORT);
    $start_only = get_lang('From').' '.$session_info['access_start_date'];
}
if (!empty($session_info['access_start_date'])) {
    $end = api_convert_and_format_date($session_info['access_end_date'], DATE_FORMAT_SHORT);
    $end_only = get_lang('Until').' '.$session_info['access_end_date'];
}

if (!empty($start) && !empty($end)) {
    $dates = Display::tag('i', sprintf(get_lang('FromDateXToDateY'), $start, $end));
} else {
    $dates = Display::tag('i', $start_only.' '.$end_only);
}

echo Display::tag('h1', $session_info['name']);
echo $dates.'<br />';

if ($session_info['show_description'] == 1) {
?>
    <div class="home-course-intro">
        <div class="page-course">
            <div class="page-course-intro">
                <p><?php echo $session_info['description']; ?></p>
            </div>
        </div>
    </div>
<?php
}

// All Learnpaths grid settings (First tab, first subtab)
$columns_courses = array(
    get_lang('Title'),
    get_lang('NumberOfPublishedExercises'),
    get_lang('NumberOfPublishedLps'),
);
$column_model_courses = array(
    array('name'=>'title',              'index'=>'title',               'width'=>'400px',  'align'=>'left',  'sortable'=>'true'),
    //array('name'=>'recent_lps',         'index'=>'recent_lps',          'width'=>'10px',  'align'=>'left',  'sortable'=>'false'),
//    array('name'=>'max_mutation_date',  'index'=>'max_mutation_date',   'width'=>'120px',  'align'=>'left',  'sortable'=>'true'),
    array('name'=>'exercise_count',     'index'=>'exercise_count',      'width'=>'180px',  'align'=>'left',  'sortable'=>'true'),
    array('name'=>'lp_count',           'index'=>'lp_count',            'width'=>'180px',  'align'=>'left',  'sortable'=>'true')
);

$extra_params_courses['height'] = '100%';
$extra_params_courses['autowidth'] = 'true'; //use the width of the parent

//$extra_params_courses['gridview'] = "false";
/*$extra_params_courses['rowNum'] = 9000;

$extra_params_courses['height'] = "100%";
$extra_params_courses['autowidth'] = 'false'; //use the width of the parent
$extra_params_courses['recordtext'] = '';
$extra_params_courses['pgtext'] = '';
$extra_params_courses['pgbuttons'] = false;*/
//$extra_params_courses['width'] = '50%';
//$extra_params_courses['autowidth'] = 'true';

$url = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_default&session_id='.$session_id.'&course_id='.$course_id;
$columns = array(
    get_lang('PublicationDate'),
    get_lang('Course'),
    get_lang('LearningPaths')
);

$column_model   = array(
    array('name'=>'date',   'index'=>'date',   'width'=>'120', 'align'=>'left', 'sortable'=>'true'),
    array('name'=>'course', 'index'=>'course', 'width'=>'300', 'align'=>'left', 'sortable'=>'true', 'wrap_cell' => 'true'),
    array('name'=>'lp',     'index'=>'lp',     'width'=>'440', 'align'=>'left', 'sortable'=>'true')
);

$extra_params = array();
$extra_params['sortname'] = 'date';

/*

$extra_params['sortorder'] = 'asc';
$extra_params['pgbuttons'] = false;
$extra_params['recordtext'] = '';
$extra_params['pgtext'] = '';
$extra_params['height'] = "100%";
*/
//$extra_params['autowidth'] = 'true'; //use the width of the parent
//$extra_params['width'] = '90%';

//$extra_params['autowidth'] = 'true'; //use the width of the parent
//$extra_params['forceFit'] = 'true'; //use the width of the parent
//$extra_params['altRows'] = 'true'; //zebra style

$extra_params['height'] = '100%';
$extra_params['autowidth'] = 'true'; //use the width of the parent

//Per course grid settings
$url_by_course = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_course&session_id='.$session_id.'&course_id='.$course_id;
$extra_params_course = array();
$extra_params_course['grouping'] = 'true';
$extra_params_course['groupingView'] = array(
    'groupCollapse'    => false,
    'groupField'       => array('course'),
    'groupColumnShow'  => array('false'),
    'groupText'        => array('<b>'.get_lang('Course').' {0}</b>')
);
$extra_params_course['autowidth'] = 'true'; //use the width of the parent
$extra_params_course['height'] = "100%";

//Per Week grid
$url_week = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_week&session_id='.$session_id.'&course_id='.$course_id;
$column_week = array(
    get_lang('PeriodWeek'),
    get_lang('PublicationDate'),
    get_lang('Course'),
    get_lang('LearningPaths')
);

$column_week_model  = array(
    array('name'=>'week',     'index'=>'week',    'width'=>'40',  'align'=>'left', 'sortable'=>'false'),
    array('name'=>'date',     'index'=>'date',    'width'=>'120', 'align'=>'left', 'sortable'=>'false'),
    array('name'=>'course',   'index'=>'course',  'width'=>'300', 'align'=>'left', 'sortable'=>'true', 'wrap_cell' => 'true'),
    array('name'=>'lp',       'index'=>'lp',      'width'=>'440', 'align'=>'left', 'sortable'=>'true')
);

$extra_params_week = array();
$extra_params_week['grouping'] = 'true';
//For more details see http://www.trirand.com/jqgridwiki/doku.php?id=wiki:grouping
$extra_params_week['groupingView'] = array(
    'groupCollapse' => false,
    'groupDataSorted' => false,
    'groupField' => array('week'),
    'groupOrder' => array('desc'),
    'groupColumnShow' => 'false',
    'groupText' => array('<b>' . get_lang('PeriodWeek') . ' {0}</b>')
);
$extra_params_week['autowidth'] = 'true'; //use the width of the parent
$extra_params_week['height'] = '100%';

// MyQCM grid
if (!api_is_anonymous()) {
    $column_exercise = array(
        get_lang('Status'),
        get_lang('ExerciseStartDate'),
        get_lang('Course'),
        get_lang('Exercise'),
        get_lang('Attempts'),
        get_lang('Result'),
        get_lang('BestResultInCourse'),
        get_lang('Ranking')
    );
    $column_exercise_model  = array(
        array('name'=>'status',     'index'=>'status',     'width'=>'40', 'align'=>'left',   'sortable'=>'false'),
        array('name'=>'date',       'index'=>'date',       'width'=>'130','align'=>'left',   'sortable'=>'true'),
        array('name'=>'course',     'index'=>'course',     'width'=>'200','align'=>'left',   'sortable'=>'true', 'wrap_cell' => 'true'),
        array('name'=>'exercise',   'index'=>'exercise',   'width'=>'200','align'=>'left',   'sortable'=>'false'),
        array('name'=>'attempt',    'index'=>'attempt',    'width'=>'60', 'align'=>'center', 'sortable'=>'true'),
        array('name'=>'result',     'index'=>'result',     'width'=>'120','align'=>'center', 'sortable'=>'true'),
        array('name'=>'best_result','index'=>'best_result','width'=>'140','align'=>'center', 'sortable'=>'true'),
        array('name'=>'position',   'index'=>'position',   'width'=>'55', 'align'=>'center', 'sortable'=>'true')
    );
    $extra_params_exercise['height'] = '100%';
    $extra_params_exercise['autowidth'] = 'true';
    //$extra_params_exercise['sortname'] = 'status';
    //$extra_params_exercise['sortorder'] = 'desc';
    //$extra_params_exercise['grouping'] = 'true';
    //$extra_params_exercise['groupingView'] = array('groupField'=>array('course'),'groupColumnShow'=>'false','groupText' => array('<b>'.get_lang('Course').' {0}</b>'));
    //$extra_params_exercise['groupingView'] = array('groupField'=>array('course'),'groupColumnShow'=>'false','groupText' => array('<b>'.get_lang('Course').' {0} - {1} Item(s)</b>'));
}
?>
<br />
<script>
function change_session() {
    document.exercise_admin.submit();
}

$(function() {
	//js used when generating images on the fly see function Tracking::show_course_detail()
    $(".dialog").dialog("destroy");
    $(".dialog").dialog({
        autoOpen: false,
        show: "blind",
        resizable: false,
        height:300,
        width:550,
        modal: true
     });

    $(".opener").click(function() {
        var my_id = $(this).attr('id');
        var big_image = '#main_graph_' + my_id;
        $( big_image ).dialog("open");
        return false;
    });

    /* Binds a tab id in the url */
    $("#tabs").bind('tabsselect', function(event, ui) {
		window.location.href=ui.tab;
    });
<?php
     //Displays js code to use a jqgrid
    echo Display::grid_js(
        'courses',
        '',
        $columns_courses,
        $column_model_courses,
        $extra_params_courses,
        $new_course_list
    );
    echo Display::grid_js(
        'list_default',
        $url,
        $columns,
        $column_model,
        $extra_params,
        array(),
        ''
    );
    echo Display::grid_js(
        'list_course',
        $url_by_course,
        $columns,
        $column_model,
        $extra_params_course,
        array(),
        ''
    );
    echo Display::grid_js(
        'list_week',
        $url_week,
        $column_week,
        $column_week_model,
        $extra_params_week,
        array(),
        ''
    );

    if (!api_is_anonymous()) {
        echo Display::grid_js(
            'exercises',
            '',
            $column_exercise,
            $column_exercise_model,
            $extra_params_exercise,
            $my_real_array
        );
    }
?>
});
</script>

<?php

$courseCode = isset($_GET['course']) ? $_GET['course'] : null;
$reportingTab = '';
if (!api_is_anonymous()) {
    $reportingTab = Tracking::show_user_progress(
        api_get_user_id(),
        $session_id,
        '#tabs-4',
        false,
        false
    );
    if (!empty($reportingTab)) {
        $reportingTab .= '<br />';
        $reportingTab .= Tracking::show_course_detail(
            api_get_user_id(),
            $courseCode,
            $session_id
        );
    }
    if (empty($reportingTab)) {
        $reportingTab = Display::return_message(get_lang('NoDataAvailable'), 'warning');
    }
}

// Main headers
$headers = array(
    Display::return_icon('moderator_star.png'),
    get_lang('Courses'),
    get_lang('LearningPaths')
);

if (!api_is_anonymous()) {
    $headers[] = get_lang('MyQCM');
    $headers[] = get_lang('MyStatistics');
}

$coursesTab = Display::grid_html('courses');
$starTab = Display::grid_html('list_default');

$tabs = array(
    $starTab,
    $coursesTab,
    Display::grid_html('list_course'),
    Display::grid_html('exercises'),
    $reportingTab
);

$tabToHide = api_get_configuration_value('session_hide_tab_list');

if (!empty($tabToHide)) {
    foreach ($tabToHide as $columnId) {
        unset($headers[$columnId]);
        unset($tabs[$columnId]);
    }
}

// Main headers data
echo Display::tabs(
    $headers,
    $tabs
);

// Deleting the objects
Session::erase('_gid');
Session::erase('oLP');
Session::erase('lpobject');
api_remove_in_gradebook();

Display::display_footer();

