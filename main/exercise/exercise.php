<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * 	Exercise list: This script shows the list of exercises for administrators and students.
 * 	@package chamilo.exercise
 * 	@author Olivier Brouckaert, original author
 * 	@author Denes Nagy, HotPotatoes integration
 * 	@author Wolfgang Schneider, code/html cleanup
 * 	@author Julio Montoya <gugli100@gmail.com>, lots of cleanup + several improvements
 * Modified by hubert.borderiou (question category)
 */

// including the global library
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;

// Setting the tabs
$this_section = SECTION_COURSES;

$htmlHeadXtra[] = api_get_asset('qtip2/jquery.qtip.min.js');
$htmlHeadXtra[] = api_get_css_asset('qtip2/jquery.qtip.min.css');

// Access control
api_protect_course_script(true);
require_once 'hotpotatoes.lib.php';

/* 	Constants and variables */
$is_allowedToEdit = api_is_allowed_to_edit(null, true);
$is_tutor = api_is_allowed_to_edit(true);
$is_tutor_course = api_is_course_tutor();
$courseInfo = api_get_course_info();
$courseId = $courseInfo['real_id'];
$userInfo = api_get_user_info();
$userId = $userInfo['id'];
$sessionId = api_get_session_id();
$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    $userId,
    $courseInfo
);

$TBL_DOCUMENT = Database::get_course_table(TABLE_DOCUMENT);
$TBL_ITEM_PROPERTY = Database::get_course_table(TABLE_ITEM_PROPERTY);
$TBL_EXERCISE_QUESTION = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
$TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

// document path
$documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path']."/document";
// picture path
$picturePath = $documentPath.'/images';
// audio path
$audioPath = $documentPath.'/audio';

// hot potatoes
$uploadPath = DIR_HOTPOTATOES; //defined in main_api
$exercisePath = api_get_self();
$exfile = explode('/', $exercisePath);
$exfile = strtolower($exfile[sizeof($exfile) - 1]);
$exercisePath = substr($exercisePath, 0, strpos($exercisePath, $exfile));
$exercisePath = $exercisePath."exercise.php";

// Clear the exercise session

Session::erase('objExercise');
Session::erase('objQuestion');
Session::erase('objAnswer');
Session::erase('questionList');
Session::erase('exerciseResult');
Session::erase('firstTime');

//General POST/GET/SESSION/COOKIES parameters recovery
$origin = isset($_REQUEST['origin']) ? Security::remove_XSS($_REQUEST['origin']) : null;
$choice = isset($_REQUEST['choice']) ? Security::remove_XSS($_REQUEST['choice']) : null;

$hpchoice = isset($_REQUEST['hpchoice']) ? Security::remove_XSS($_REQUEST['hpchoice']) : null;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : null;
$file = isset($_REQUEST['file']) ? Database::escape_string($_REQUEST['file']) : null;

$learnpath_id = isset($_REQUEST['learnpath_id']) ? intval($_REQUEST['learnpath_id']) : null;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? intval($_REQUEST['learnpath_item_id']) : null;
$page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : null;

if ($page < 0) {
    $page = 1;
}

if (!empty($_GET['gradebook']) && $_GET['gradebook'] == 'view') {
    $_SESSION['gradebook'] = Security::remove_XSS($_GET['gradebook']);
    $gradebook = $_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
    unset($_SESSION['gradebook']);
    $gradebook = '';
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

$nameTools = get_lang('Exercises');
$errorXmlExport = null;
if ($is_allowedToEdit && !empty($choice) && $choice == 'exportqti2') {
    require_once api_get_path(SYS_CODE_PATH).'exercise/export/qti2/qti2_export.php';

    $export = export_exercise_to_qti($exerciseId, true);
    $archive_path = api_get_path(SYS_ARCHIVE_PATH);
    $temp_dir_short = api_get_unique_id();
    $temp_zip_dir = $archive_path.$temp_dir_short;
    if (!is_dir($temp_zip_dir)) {
        mkdir($temp_zip_dir, api_get_permissions_for_new_directories());
    }
    $temp_zip_file = $temp_zip_dir."/".api_get_unique_id().".zip";
    $temp_xml_file = $temp_zip_dir."/qti2export_".$exerciseId.'.xml';
    file_put_contents($temp_xml_file, $export);

    $xmlReader = new XMLReader();
    $xmlReader->open($temp_xml_file);
    $xmlReader->setParserProperty(XMLReader::VALIDATE, true);
    $isValid = $xmlReader->isValid();

    if ($isValid) {
        $zip_folder = new PclZip($temp_zip_file);
        $zip_folder->add($temp_xml_file, PCLZIP_OPT_REMOVE_ALL_PATH);
        $name = 'qti2_export_'.$exerciseId.'.zip';
        DocumentManager::file_send_for_download($temp_zip_file, true, $name);
        unlink($temp_zip_file);
        unlink($temp_xml_file);
        rmdir($temp_zip_dir);
        exit; //otherwise following clicks may become buggy
    } else {
        $errorXmlExport = Display :: return_message(get_lang('ErrorWritingXMLFile'), 'error');
    }
}

if ($origin != 'learnpath') {
    //so we are not in learnpath tool
    Display :: display_header($nameTools, get_lang('Exercise'));
    if (isset($_GET['message'])) {
        if (in_array($_GET['message'], array('ExerciseEdited'))) {
            echo Display::return_message(get_lang($_GET['message']), 'confirmation');
        }
    }
} else {
    Display :: display_reduced_header();
}

Event::event_access_tool(TOOL_QUIZ);

// Tool introduction
Display :: display_introduction_section(TOOL_QUIZ);

if (!empty($errorXmlExport)) {
    echo $errorXmlExport;
}

HotPotGCt($documentPath, 1, $userId);

// Only for administrator
if ($is_allowedToEdit) {
    if (!empty($choice)) {
        // All test choice, clean all test's results
        if ($choice === 'clean_all_test') {
            $check = Security::check_token('get');
            if ($check) {
                // list of exercises in a course/session
                // we got variable $courseId $courseInfo session api_get_session_id()
                $exerciseList = ExerciseLib::get_all_exercises_for_course_id(
                    $courseInfo,
                    $sessionId,
                    $courseId,
                    false
                );
                $quantity_results_deleted = 0;
                foreach ($exerciseList as $exeItem) {
                    // delete result for test, if not in a gradebook
                    $exercise_action_locked = api_resource_is_locked_by_gradebook($exeItem['id'], LINK_EXERCISE);
                    if ($exercise_action_locked == false) {
                        $objExerciseTmp = new Exercise();
                        if ($objExerciseTmp->read($exeItem['id'])) {
                            $quantity_results_deleted += $objExerciseTmp->clean_results(true);
                        }
                    }
                }
                echo Display::return_message(
                    sprintf(
                        get_lang('XResultsCleaned'),
                        $quantity_results_deleted
                    ),
                    'confirm'
                );
            }
        }

        // single exercise choice
        // construction of Exercise
        $objExerciseTmp = new Exercise();
        $check = Security::check_token('get');
        $exercise_action_locked = api_resource_is_locked_by_gradebook(
            $exerciseId,
            LINK_EXERCISE
        );

        if ($objExerciseTmp->read($exerciseId)) {
            if ($check) {
                switch ($choice) {
                    case 'delete':
                        // deletes an exercise
                        if ($exercise_action_locked == false) {
                            $objExerciseTmp->delete();
                            $link_info = GradebookUtils::isResourceInCourseGradebook(
                                api_get_course_id(),
                                1,
                                $exerciseId,
                                api_get_session_id()
                            );
                            if ($link_info !== false) {
                                GradebookUtils::remove_resource_from_course_gradebook($link_info['id']);
                            }
                            echo Display::return_message(get_lang('ExerciseDeleted'), 'confirmation');
                        }
                        break;
                    case 'enable':
                        // enables an exercise
                        if (empty($sessionId)) {
                            $objExerciseTmp->enable();
                            $objExerciseTmp->save();
                        } else {
                            if (!empty($objExerciseTmp->sessionId)) {
                                $objExerciseTmp->enable();
                                $objExerciseTmp->save();
                            }
                        }

                        api_item_property_update(
                            $courseInfo,
                            TOOL_QUIZ,
                            $objExerciseTmp->id,
                            'visible',
                            $userId
                        );
                        // "WHAT'S NEW" notification: update table item_property (previously last_tooledit)
                        echo Display::return_message(get_lang('VisibilityChanged'), 'confirmation');
                        break;
                    case 'disable':
                        // disables an exercise
                        if (empty($sessionId)) {
                            $objExerciseTmp->disable();
                            $objExerciseTmp->save();
                        } else {
                            // Only change active if it belongs to a session
                            if (!empty($objExerciseTmp->sessionId)) {
                                $objExerciseTmp->disable();
                                $objExerciseTmp->save();
                            }
                        }

                        api_item_property_update(
                            $courseInfo,
                            TOOL_QUIZ,
                            $objExerciseTmp->id,
                            'invisible',
                            $userId
                        );
                        echo Display::return_message(get_lang('VisibilityChanged'), 'confirmation');
                        break;
                    case 'disable_results':
                        //disable the results for the learners
                        $objExerciseTmp->disable_results();
                        $objExerciseTmp->save();
                        echo Display::return_message(get_lang('ResultsDisabled'), 'confirmation');
                        break;
                    case 'enable_results':
                        //disable the results for the learners
                        $objExerciseTmp->enable_results();
                        $objExerciseTmp->save();
                        echo Display::return_message(get_lang('ResultsEnabled'), 'confirmation');
                        break;
                    case 'clean_results':
                        //clean student results
                        if ($exercise_action_locked == false) {
                            $quantity_results_deleted = $objExerciseTmp->clean_results(true);
                            $title = $objExerciseTmp->selectTitle();
                            echo Display::return_message($title.': '.sprintf(get_lang('XResultsCleaned'), $quantity_results_deleted), 'confirmation');
                        }
                        break;
                    case 'copy_exercise': //copy an exercise
                        $objExerciseTmp->copy_exercise();
                        echo Display::return_message(get_lang('ExerciseCopied'), 'confirmation');
                        break;
                }
            }
        }
        // destruction of Exercise
        unset($objExerciseTmp);
        Security::clear_token();
    }

    if (!empty($hpchoice)) {
        switch ($hpchoice) {
            case 'delete':
                // deletes an exercise
                $imgparams = array();
                $imgcount = 0;
                GetImgParams($file, $documentPath, $imgparams, $imgcount);
                $fld = GetFolderName($file);

                for ($i = 0; $i < $imgcount; $i++) {
                    my_delete($documentPath.$uploadPath."/".$fld."/".$imgparams[$i]);
                    DocumentManager::updateDbInfo("delete", $uploadPath."/".$fld."/".$imgparams[$i]);
                }

                if (!is_dir($documentPath.$uploadPath."/".$fld."/")) {
                    my_delete($documentPath.$file);
                    DocumentManager::updateDbInfo("delete", $file);
                } else {
                    if (my_delete($documentPath.$file)) {
                        DocumentManager::updateDbInfo("delete", $file);
                    }
                }

                /* hotpotatoes folder may contains several tests so
                   don't delete folder if not empty :
                    http://support.chamilo.org/issues/2165
                */

                if (!(strstr($uploadPath, DIR_HOTPOTATOES) && !folder_is_empty($documentPath.$uploadPath."/".$fld."/"))) {
                    my_delete($documentPath.$uploadPath."/".$fld."/");
                }
                break;
            case 'enable': // enables an exercise
                $newVisibilityStatus = "1"; //"visible"
                $query = "SELECT id FROM $TBL_DOCUMENT
                          WHERE c_id = $courseId AND path='".Database :: escape_string($file)."'";
                $res = Database::query($query);
                $row = Database :: fetch_array($res, 'ASSOC');
                api_item_property_update(
                    $courseInfo,
                    TOOL_DOCUMENT,
                    $row['id'],
                    'visible',
                    $userId
                );
                //$dialogBox = get_lang('ViMod');

                break;
            case 'disable': // disables an exercise
                $newVisibilityStatus = "0"; //"invisible"
                $query = "SELECT id FROM $TBL_DOCUMENT
                          WHERE c_id = $courseId AND path='".Database :: escape_string($file)."'";
                $res = Database::query($query);
                $row = Database :: fetch_array($res, 'ASSOC');
                api_item_property_update(
                    $courseInfo,
                    TOOL_DOCUMENT,
                    $row['id'],
                    'invisible',
                    $userId
                );
                break;
            default:
                break;
        }
    }
}


// Actions div bar
if ($is_allowedToEdit) {
    echo '<div class="actions">';
}

// Selects $limit exercises at the same time
// maximum number of exercises on a same page
$limit = 50;

// Display the next and previous link if needed
$from = $page * $limit;
HotPotGCt($documentPath, 1, $userId);

//condition for the session
$course_code = api_get_course_id();
$session_id = api_get_session_id();
$condition_session = api_get_session_condition($session_id, true, true);

// Only for administrators
if ($is_allowedToEdit) {
    $total_sql = "SELECT count(iid) as count 
                  FROM $TBL_EXERCISES
                  WHERE c_id = $courseId AND active<>'-1' $condition_session ";
    $sql = "SELECT * FROM $TBL_EXERCISES
            WHERE c_id = $courseId AND active<>'-1' $condition_session
            ORDER BY title
            LIMIT ".$from.",".$limit;
} else {
    // Only for students
    $total_sql = "SELECT count(iid) as count 
                  FROM $TBL_EXERCISES
                  WHERE c_id = $courseId AND active = '1' $condition_session ";
    $sql = "SELECT * FROM $TBL_EXERCISES
            WHERE c_id = $courseId AND
                  active='1' $condition_session
            ORDER BY title LIMIT ".$from.",".$limit;
}
$result = Database::query($sql);
$result_total = Database::query($total_sql);

$total_exercises = 0;

if (Database :: num_rows($result_total)) {
    $result_total = Database::fetch_array($result_total);
    $total_exercises = $result_total['count'];
}

//get HotPotatoes files (active and inactive)
if ($is_allowedToEdit) {
    $sql = "SELECT * FROM $TBL_DOCUMENT
            WHERE
                c_id = $courseId AND
                path LIKE '".Database :: escape_string($uploadPath.'/%/%')."'";
    $res = Database::query($sql);
    $hp_count = Database :: num_rows($res);
} else {
    $sql = "SELECT * FROM $TBL_DOCUMENT d, $TBL_ITEM_PROPERTY ip
            WHERE
                d.id = ip.ref AND
                ip.tool = '".TOOL_DOCUMENT."' AND
                d.path LIKE '".Database :: escape_string($uploadPath.'/%/%')."' AND
                ip.visibility ='1' AND
                d.c_id      = ".$courseId." AND
                ip.c_id     = ".$courseId;
    $res = Database::query($sql);
    $hp_count = Database :: num_rows($res);
}

$total = $total_exercises + $hp_count;

$token = Security::get_token();
if ($is_allowedToEdit && $origin != 'learnpath') {
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise_admin.php?'.api_get_cidreq().'">'.
        Display::return_icon('new_exercice.png', get_lang('NewEx'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/question_create.php?'.api_get_cidreq().'">'.
        Display::return_icon('new_question.png', get_lang('AddQ'), '', ICON_SIZE_MEDIUM).'</a>';
    // Question category
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/tests_category.php?'.api_get_cidreq().'">';
    echo Display::return_icon('green_open.png', get_lang('QuestionCategory'), '', ICON_SIZE_MEDIUM);
    echo '</a>';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/question_pool.php?'.api_get_cidreq().'">';
    echo Display::return_icon('database.png', get_lang('QuestionPool'), '', ICON_SIZE_MEDIUM);
    echo '</a>';

    //echo Display::url(Display::return_icon('looknfeel.png', get_lang('Media')), 'media.php?' . api_get_cidreq());
    // end question category
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/hotpotatoes.php?'.api_get_cidreq().'">'.Display::return_icon('import_hotpotatoes.png', get_lang('ImportHotPotatoesQuiz'), '', ICON_SIZE_MEDIUM).'</a>';
    // link to import qti2 ...
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/qti2.php?'.api_get_cidreq().'">'.Display::return_icon('import_qti2.png', get_lang('ImportQtiQuiz'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/aiken.php?'.api_get_cidreq().'">'.Display::return_icon('import_aiken.png', get_lang('ImportAikenQuiz'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/upload_exercise.php?'.api_get_cidreq().'">'.Display::return_icon('import_excel.png', get_lang('ImportExcelQuiz'), '', ICON_SIZE_MEDIUM).'</a>';
    echo Display::url(
        Display::return_icon(
            'clean_all.png',
            get_lang('CleanAllStudentsResultsForAllTests'),
            '',
            ICON_SIZE_MEDIUM
        ),
        '',
        array(
            'onclick' => "javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToEmptyAllTestResults'), ENT_QUOTES, $charset))."')) return false;",
            'href' => api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq().'&choice=clean_all_test&sec_token='.$token
        )
    );
}

if ($is_allowedToEdit) {
    echo '</div>'; // closing the actions div
}

if ($total > $limit) {
    echo '<div style="float:right;height:20px;">';
    //show pages navigation link for previous page
    if ($page) {
        echo "<a href=\"".api_get_self()."?".api_get_cidreq()."&page=".($page - 1)."\">".Display::return_icon('action_prev.png', get_lang('PreviousPage'))."</a>";
    } elseif ($total_exercises + $hp_count > $limit) {
        echo Display::return_icon('action_prev_na.png', get_lang('PreviousPage'));
    }

    //show pages navigation link for previous page
    if ($total_exercises > $from + $limit || $hp_count > $from + $limit) {
        echo ' '."<a href=\"".api_get_self()."?".api_get_cidreq()."&page=".($page + 1)."\">".Display::return_icon('action_next.png', get_lang('NextPage'))."</a>";
    } elseif ($page) {
        echo ' '.Display::return_icon('action_next_na.png', get_lang('NextPage'));
    }
    echo '</div>';
}

$i = 1;

$online_icon = Display::return_icon('online.png', get_lang('Visible'), array('width' => '12px'));
$offline_icon = Display::return_icon('offline.png', get_lang('Invisible'), array('width' => '12px'));

$exerciseList = array();
$list_ordered = null;

while ($row = Database :: fetch_array($result, 'ASSOC')) {
    $exerciseList[$row['iid']] = $row;
}

if (!empty($exerciseList) &&
    api_get_setting('exercise_invisible_in_session') === 'true'
) {
    if (!empty($sessionId)) {
        $changeDefaultVisibility = true;
        if (api_get_setting('configure_exercise_visibility_in_course') === 'true') {
            $changeDefaultVisibility = false;
            if (api_get_course_setting('exercise_invisible_in_session') == 1) {
                $changeDefaultVisibility = true;
            }
        }

        if ($changeDefaultVisibility) {
            // Check exercise
            foreach ($exerciseList as $exercise) {
                if ($exercise['session_id'] == 0) {
                    $visibilityInfo = api_get_item_property_info(
                        $courseId,
                        TOOL_QUIZ,
                        $exercise['iid'],
                        $sessionId
                    );

                    if (empty($visibilityInfo)) {
                        // Create a record for this
                        api_item_property_update(
                            $courseInfo,
                            TOOL_QUIZ,
                            $exercise['iid'],
                            'invisible',
                            api_get_user_id(),
                            0,
                            null,
                            '',
                            '',
                            $sessionId
                        );
                    }
                }
            }
        }
    }
}


if (isset($list_ordered) && !empty($list_ordered)) {
    $new_question_list = array();
    foreach ($list_ordered as $exercise_id) {
        if (isset($exerciseList[$exercise_id])) {
            $new_question_list[] = $exerciseList[$exercise_id];
        }
    }
    $exerciseList = $new_question_list;
}

$tableRows = [];
/*  Listing exercises  */
if (!empty($exerciseList)) {
    if ($origin != 'learnpath') {
        //avoid sending empty parameters
        $myorigin = (empty($origin) ? '' : '&origin='.$origin);
        $mylpid = (empty($learnpath_id) ? '' : '&learnpath_id='.$learnpath_id);
        $mylpitemid = (empty($learnpath_item_id) ? '' : '&learnpath_item_id='.$learnpath_item_id);
        $i = 1;
        foreach ($exerciseList as $row) {
            $my_exercise_id = $row['id'];

            $exercise = new Exercise();
            $exercise->read($my_exercise_id, false);

            if (empty($exercise->id)) {
                continue;
            }

            $locked = $exercise->is_gradebook_locked;
            $i++;
            // Validation when belongs to a session
            $session_img = api_get_session_image($row['session_id'], $userInfo['status']);

            $time_limits = false;
            if (!empty($row['start_time']) || !empty($row['end_time'])) {
                $time_limits = true;
            }

            $is_actived_time = false;
            if ($time_limits) {
                // check if start time
                $start_time = false;
                if (!empty($row['start_time'])) {
                    $start_time = api_strtotime($row['start_time'], 'UTC');
                }
                $end_time = false;
                if (!empty($row['end_time'])) {
                    $end_time = api_strtotime($row['end_time'], 'UTC');
                }
                $now = time();

                //If both "clocks" are enable
                if ($start_time && $end_time) {
                    if ($now > $start_time && $end_time > $now) {
                        $is_actived_time = true;
                    }
                } else {
                    //we check the start and end
                    if ($start_time) {
                        if ($now > $start_time) {
                            $is_actived_time = true;
                        }
                    }
                    if ($end_time) {
                        if ($end_time > $now) {
                            $is_actived_time = true;
                        }
                    }
                }
            }

            // Blocking empty start times see BT#2800
            global $_custom;
            if (isset($_custom['exercises_hidden_when_no_start_date']) &&
                $_custom['exercises_hidden_when_no_start_date']
            ) {
                if (empty($row['start_time'])) {
                    $time_limits = true;
                    $is_actived_time = false;
                }
            }

            $cut_title = $exercise->getCutTitle();
            $alt_title = '';
            if ($cut_title != $row['title']) {
                $alt_title = ' title = "'.$row['title'].'" ';
            }

            // Teacher only
            if ($is_allowedToEdit) {
                $lp_blocked = null;
                if ($exercise->exercise_was_added_in_lp == true) {
                    $lp_blocked = Display::div(
                        get_lang('AddedToLPCannotBeAccessed'),
                        array('class' => 'lp_content_type_label')
                    );
                }

                $visibility = api_get_item_visibility(
                    $courseInfo,
                    TOOL_QUIZ,
                    $my_exercise_id,
                    0
                );

                if (!empty($sessionId)) {
                    $setting = api_get_configuration_value('show_hidden_exercise_added_to_lp');
                    if ($setting) {
                        if ($exercise->exercise_was_added_in_lp == false) {
                            if ($visibility == 0) {
                                continue;
                            }
                        }
                    } else {
                        if ($visibility == 0) {
                            continue;
                        }
                    }

                    $visibility = api_get_item_visibility(
                        $courseInfo,
                        TOOL_QUIZ,
                        $my_exercise_id,
                        $sessionId
                    );
                }

                if ($row['active'] == 0 || $visibility == 0) {
                    $title = Display::tag('font', $cut_title, array('style' => 'color:grey'));
                } else {
                    $title = $cut_title;
                }

                $count_exercise_not_validated = (int) Event::count_exercise_result_not_validated(
                    $my_exercise_id,
                    $courseId,
                    $session_id
                );

                $move = Display::return_icon(
                    'all_directions.png',
                    get_lang('Move'),
                    array('class'=>'moved', 'style'=>'margin-bottom:-0.5em;')
                );
                $move = null;
                $class_tip = '';

                if (!empty($count_exercise_not_validated)) {
                    $results_text = $count_exercise_not_validated == 1 ? get_lang('ResultNotRevised') : get_lang('ResultsNotRevised');
                    $title .= '<span class="exercise_tooltip" style="display: none;">'.$count_exercise_not_validated.' '.$results_text.' </span>';
                    $class_tip = 'link_tooltip';
                }

                $url = $move.'<a '.$alt_title.' class="'.$class_tip.'" id="tooltip_'.$row['id'].'" href="overview.php?'.api_get_cidreq().$myorigin.$mylpid.$mylpitemid.'&exerciseId='.$row['id'].'">
                             '.Display::return_icon('quiz.png', $row['title']).'
                 '.$title.' </a>';

                $item = Display::tag('td', $url.' '.$session_img.$lp_blocked);

                // Count number exercise - teacher
                $sql = "SELECT count(*) count FROM $TBL_EXERCISE_QUESTION
                        WHERE c_id = $courseId AND exercice_id = $my_exercise_id";
                $sqlresult = Database::query($sql);
                $rowi = Database :: result($sqlresult, 0, 0);

                if ($session_id == $row['session_id']) {
                    // Questions list
                    $actions = Display::url(
                        Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL),
                        'admin.php?'.api_get_cidreq().'&exerciseId='.$row['id']
                    );

                    // Test settings
                    $actions .= Display::url(
                        Display::return_icon('settings.png', get_lang('Configure'), '', ICON_SIZE_SMALL),
                        'exercise_admin.php?'.api_get_cidreq().'&exerciseId='.$row['id']
                    );

                    // Exercise results
                    $actions .= '<a href="exercise_report.php?'.api_get_cidreq().'&exerciseId='.$row['id'].'">'.
                        Display::return_icon('test_results.png', get_lang('Results'), '', ICON_SIZE_SMALL).'</a>';

                    // Export
                    $actions .= Display::url(
                        Display::return_icon('cd.png', get_lang('CopyExercise')),
                        '',
                        array(
                            'onclick' => "javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToCopy'), ENT_QUOTES, $charset))." ".addslashes($row['title'])."?"."')) return false;",
                            'href' => 'exercise.php?'.api_get_cidreq().'&choice=copy_exercise&sec_token='.$token.'&exerciseId='.$row['id']
                        )
                    );

                    // Clean exercise
                    if ($locked == false) {
                        $actions .= Display::url(
                            Display::return_icon('clean.png', get_lang('CleanStudentResults'), '', ICON_SIZE_SMALL),
                            '',
                            array(
                                'onclick' => "javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToDeleteResults'), ENT_QUOTES, $charset))." ".addslashes($row['title'])."?"."')) return false;",
                                'href' => 'exercise.php?'.api_get_cidreq().'&choice=clean_results&sec_token='.$token.'&exerciseId='.$row['id']
                            )
                        );
                    } else {
                        $actions .= Display::return_icon('clean_na.png', get_lang('ResourceLockedByGradebook'), '', ICON_SIZE_SMALL);
                    }

                    // Visible / invisible
                    // Check if this exercise was added in a LP
                    if ($exercise->exercise_was_added_in_lp == true) {
                        $actions .= Display::return_icon('invisible.png', get_lang('AddedToLPCannotBeAccessed'), '', ICON_SIZE_SMALL);
                    } else {
                        if ($row['active'] == 0 || $visibility == 0) {
                            $actions .= Display::url(
                                Display::return_icon(
                                    'invisible.png',
                                    get_lang('Activate'),
                                    '',
                                    ICON_SIZE_SMALL
                                ),
                                'exercise.php?'.api_get_cidreq(
                                ).'&choice=enable&sec_token='.$token.'&page='.$page.'&exerciseId='.$row['id']
                            );
                        } else {
                            // else if not active
                            $actions .= Display::url(
                                Display::return_icon(
                                    'visible.png',
                                    get_lang('Deactivate'),
                                    '',
                                    ICON_SIZE_SMALL
                                ),
                                'exercise.php?'.api_get_cidreq(
                                ).'&choice=disable&sec_token='.$token.'&page='.$page.'&exerciseId='.$row['id']
                            );
                        }
                    }
                    // Export qti ...
                    $actions .= Display::url(Display::return_icon('export_qti2.png', 'IMS/QTI', '', ICON_SIZE_SMALL), 'exercise.php?choice=exportqti2&exerciseId='.$row['id'].'&'.api_get_cidreq());
                } else {
                    // not session
                    $actions = Display::return_icon('edit_na.png', get_lang('ExerciseEditionNotAvailableInSession'));

                    // Check if this exercise was added in a LP
                    if ($exercise->exercise_was_added_in_lp == true) {
                        $actions .= Display::return_icon('invisible.png', get_lang('AddedToLPCannotBeAccessed'), '', ICON_SIZE_SMALL);
                    } else {

                        if ($row['active'] == 0 || $visibility == 0) {
                            $actions .= Display::url(
                                Display::return_icon('invisible.png', get_lang('Activate'), '', ICON_SIZE_SMALL),
                                'exercise.php?'.api_get_cidreq().'&choice=enable&sec_token='.$token.'&page='.$page.'&exerciseId='.$row['id']
                            );
                        } else {
                            // else if not active
                            $actions .= Display::url(
                                Display::return_icon('visible.png', get_lang('Deactivate'), '', ICON_SIZE_SMALL),
                                'exercise.php?'.api_get_cidreq().'&choice=disable&sec_token='.$token.'&page='.$page.'&exerciseId='.$row['id']
                            );
                        }
                    }

                    $actions .= '<a href="exercise_report.php?'.api_get_cidreq().'&exerciseId='.$row['id'].'">'.
                        Display::return_icon('test_results.png', get_lang('Results'), '', ICON_SIZE_SMALL).'</a>';
                    $actions .= Display::url(Display::return_icon('cd.gif', get_lang('CopyExercise')), '', array('onclick' => "javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToCopy'), ENT_QUOTES, $charset))." ".addslashes($row['title'])."?"."')) return false;", 'href' => 'exercise.php?'.api_get_cidreq().'&choice=copy_exercise&sec_token='.$token.'&exerciseId='.$row['id']));
                }

                // Delete
                if ($session_id == $row['session_id']) {
                    if ($locked == false) {
                        $actions .= Display::url(
                            Display::return_icon(
                                'delete.png',
                                get_lang('Delete'),
                                '',
                                ICON_SIZE_SMALL
                            ),
                            '',
                            array('onclick' => "javascript:if(!confirm('".addslashes(api_htmlentities(get_lang('AreYouSureToDeleteJS'), ENT_QUOTES, $charset))." ".addslashes($row['title'])."?"."')) return false;", 'href' => 'exercise.php?'.api_get_cidreq().'&choice=delete&sec_token='.$token.'&exerciseId='.$row['id'])
                        );
                    } else {
                        $actions .= Display::return_icon('delete_na.png', get_lang('ResourceLockedByGradebook'), '', ICON_SIZE_SMALL);
                    }
                }

                // Number of questions
                $random_label = null;
                if ($row['random'] > 0 || $row['random'] == -1) {
                    // if random == -1 means use random questions with all questions
                    $random_number_of_question = $row['random'];
                    if ($random_number_of_question == -1) {
                        $random_number_of_question = $rowi;
                    }
                    if ($row['random_by_category'] > 0) {
                        $nbQuestionsTotal = TestCategory::getNumberOfQuestionRandomByCategory(
                            $my_exercise_id,
                            $random_number_of_question
                        );
                        $number_of_questions = $nbQuestionsTotal." ";
                        $number_of_questions .= ($nbQuestionsTotal > 1) ? get_lang("QuestionsLowerCase") : get_lang("QuestionLowerCase");
                        $number_of_questions .= " - ";
                        $number_of_questions .= min(TestCategory::getNumberMaxQuestionByCat($my_exercise_id), $random_number_of_question).' '.get_lang('QuestionByCategory');
                    } else {
                        $random_label = ' ('.get_lang('Random').') ';
                        $number_of_questions = $random_number_of_question.' '.$random_label;
                        //Bug if we set a random value bigger than the real number of questions
                        if ($random_number_of_question > $rowi) {
                            $number_of_questions = $rowi.' '.$random_label;
                        }
                    }
                } else {
                    $number_of_questions = $rowi;
                }

                //Attempts
                //$attempts = ExerciseLib::get_count_exam_results($row['id']).' '.get_lang('Attempts');
                //$item .=  Display::tag('td',$attempts);
                $item .= Display::tag('td', $number_of_questions);
            } else {
                // Student only.
                $visibility = api_get_item_visibility(
                    $courseInfo,
                    TOOL_QUIZ,
                    $my_exercise_id,
                    $sessionId
                );

                if ($visibility == 0) {
                    continue;
                }

                $url = '<a '.$alt_title.'  href="overview.php?'.api_get_cidreq().$myorigin.$mylpid.$mylpitemid.'&exerciseId='.$row['id'].'">'.
                        $cut_title.'</a>';

                // Link of the exercise.
                $item = Display::tag('td', $url.' '.$session_img);

                // Count number exercise questions.
                /*$sql = "SELECT count(*) FROM $TBL_EXERCISE_QUESTION
                        WHERE c_id = $courseId AND exercice_id = ".$row['id'];
                $sqlresult = Database::query($sql);
                $rowi = Database::result($sqlresult, 0);

                if ($row['random'] > 0) {
                    $row['random'].' '.api_strtolower(get_lang(($row['random'] > 1 ? 'Questions' : 'Question')));
                } else {
                    //show results student
                    $rowi.' '.api_strtolower(get_lang(($rowi > 1 ? 'Questions' : 'Question')));
                }*/

                // This query might be improved later on by ordering by the new "tms" field rather than by exe_id
                // Don't remove this marker: note-query-exe-results
                $sql = "SELECT * FROM $TBL_TRACK_EXERCISES
                        WHERE
                            exe_exo_id = ".$row['id']." AND
                            exe_user_id = $userId AND
                            c_id = ".api_get_course_int_id()." AND
                            status <> 'incomplete' AND
                            orig_lp_id = 0 AND
                            orig_lp_item_id = 0 AND
                            session_id =  '".api_get_session_id()."'
                        ORDER BY exe_id DESC";

                $qryres = Database::query($sql);
                $num = Database :: num_rows($qryres);

                // Hide the results.
                $my_result_disabled = $row['results_disabled'];

                // Time limits are on
                if ($time_limits) {
                    // Exam is ready to be taken
                    if ($is_actived_time) {
                        // Show results 	697 	$attempt_text = get_lang('LatestAttempt').' : ';
                        if ($my_result_disabled == 0 || $my_result_disabled == 2) {
                            //More than one attempt
                            if ($num > 0) {
                                $row_track = Database :: fetch_array($qryres);
                                $attempt_text = get_lang('LatestAttempt').' : ';
                                $attempt_text .= ExerciseLib::show_score($row_track['exe_result'], $row_track['exe_weighting']);
                            } else {
                                //No attempts
                                $attempt_text = get_lang('NotAttempted');
                            }
                        } else {
                            //$attempt_text = get_lang('CantShowResults');
                            $attempt_text = '-';
                        }
                    } else {
                        //Quiz not ready due to time limits 	700 	$attempt_text = get_lang('NotAttempted');
                        //@todo use the is_visible function
                        if (!empty($row['start_time']) && !empty($row['end_time'])) {
                            $today = time();
                            $start_time = api_strtotime($row['start_time'], 'UTC');
                            $end_time = api_strtotime($row['end_time'], 'UTC');
                            if ($today < $start_time) {
                                $attempt_text = sprintf(get_lang('ExerciseWillBeActivatedFromXToY'), api_convert_and_format_date($row['start_time']), api_convert_and_format_date($row['end_time']));
                            } else {
                                if ($today > $end_time) {
                                    $attempt_text = sprintf(get_lang('ExerciseWasActivatedFromXToY'), api_convert_and_format_date($row['start_time']), api_convert_and_format_date($row['end_time']));
                                }
                            }

                        } else {
                            //$attempt_text = get_lang('ExamNotAvailableAtThisTime');
                            if (!empty($row['start_time'])) {
                                $attempt_text = sprintf(
                                    get_lang('ExerciseAvailableFromX'),
                                    api_convert_and_format_date($row['start_time'])
                                );
                            }
                            if (!empty($row['end_time'])) {
                                $attempt_text = sprintf(
                                    get_lang('ExerciseAvailableUntilX'),
                                    api_convert_and_format_date($row['end_time'])
                                );
                            }
                        }
                    }
                } else {
                    // Normal behaviour.
                    // Show results.
                    if ($my_result_disabled == 0 || $my_result_disabled == 2) {
                        if ($num > 0) {
                            $row_track = Database :: fetch_array($qryres);
                            $attempt_text = get_lang('LatestAttempt').' : ';
                            $attempt_text .= ExerciseLib::show_score(
                                $row_track['exe_result'],
                                $row_track['exe_weighting']
                            );
                        } else {
                            $attempt_text = get_lang('NotAttempted');
                        }
                    } else {
                        //$attempt_text = get_lang('CantShowResults');
                        $attempt_text = '-';
                    }
                }

                $class_tip = '';

                if (empty($num)) {
                    $num = '';
                } else {
                    $class_tip = 'link_tooltip';
                    //@todo use sprintf and show the results validated by the teacher
                    if ($num == 1) {
                        $num = $num.' '.get_lang('Result');
                    } else {
                        $num = $num.' '.get_lang('Results');
                    }
                    $num = '<span class="tooltip" style="display: none;">'.$num.'</span>';
                }

                $item .= Display::tag('td', $attempt_text);
            }

            if ($is_allowedToEdit) {
                $item .= Display::tag('td', $actions, array('class' => 'td_actions'));
            } else {
                if ($isDrhOfCourse) {
                    $actions = '<a href="exercise_report.php?'.api_get_cidreq().'&exerciseId='.$row['id'].'">'.
                        Display::return_icon('test_results.png', get_lang('Results'), '', ICON_SIZE_SMALL).'</a>';
                    $item .= Display::tag('td', $actions, array('class' => 'td_actions'));
                }
            }

            $tableRows[] = Display::tag(
                'tr',
                $item,
                array(
                    'id' => 'exercise_list_'.$my_exercise_id,
                )
            );
        }
    }
}

// end exercise list
// Hotpotatoes results
$hotpotatoes_exist = false;

if ($is_allowedToEdit) {
    $sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
            FROM $TBL_DOCUMENT d 
            INNER JOIN $TBL_ITEM_PROPERTY ip
            ON (d.id = ip.ref AND d.c_id = ip.c_id)
            WHERE
                d.c_id = $courseId AND
                ip.c_id = $courseId AND                
                ip.tool = '".TOOL_DOCUMENT."' AND
                (d.path LIKE '%htm%') AND
                d.path  LIKE '".Database :: escape_string($uploadPath.'/%/%')."'
            LIMIT ".$from.",".$limit; // only .htm or .html files listed
} else {
    $sql = "SELECT d.path as path, d.comment as comment, ip.visibility as visibility
            FROM $TBL_DOCUMENT d 
            INNER JOIN $TBL_ITEM_PROPERTY ip
            ON (d.id = ip.ref AND d.c_id = ip.c_id)
            WHERE
                d.c_id = $courseId AND
                ip.c_id = $courseId AND                
                ip.tool = '".TOOL_DOCUMENT."' AND
                (d.path LIKE '%htm%') AND
                d.path  LIKE '".Database :: escape_string($uploadPath.'/%/%')."' AND
                ip.visibility='1'
            LIMIT ".$from.",".$limit;
}

$result = Database::query($sql);

while ($row = Database :: fetch_array($result, 'ASSOC')) {
    $attribute['path'][] = $row['path'];
    $attribute['visibility'][] = $row['visibility'];
    $attribute['comment'][] = $row['comment'];
}

$nbrActiveTests = 0;
if (isset($attribute['path']) && is_array($attribute['path'])) {
    $hotpotatoes_exist = true;
    while (list($key, $path) = each($attribute['path'])) {
        $item = '';
        list ($a, $vis) = each($attribute['visibility']);
        $active = !empty($vis);
        $title = GetQuizName($path, $documentPath);
        if ($title == '') {
            $title = basename($path);
        }

        // prof only
        if ($is_allowedToEdit) {
            $item = Display::tag(
                'td',
                implode(PHP_EOL, [
                    Display::return_icon('hotpotatoes_s.png', "HotPotatoes"),
                    Display::url(
                        $title,
                        'showinframes.php?'.api_get_cidreq().'&'.http_build_query([
                            'file' => $path,
                            'uid' => $userId
                        ]),
                        ['class' => !$active ? 'text-muted' : null]
                    )
                ])
            );
            $item .= Display::tag('td', '-');

            $actions = Display::url(
                Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL),
                'adminhp.php?'.api_get_cidreq().'&hotpotatoesName='.$path
            );

            $actions .= '<a href="hotpotatoes_exercise_report.php?'.api_get_cidreq().'&path='.$path.'">'.
                Display::return_icon('test_results.png', get_lang('Results'), '', ICON_SIZE_SMALL).'</a>';

            // if active
            if ($active) {
                $nbrActiveTests = $nbrActiveTests + 1;
                $actions .= '      <a href="'.$exercisePath.'?'.api_get_cidreq().'&hpchoice=disable&page='.$page.'&file='.$path.'">'.
                    Display::return_icon('visible.png', get_lang('Deactivate'), '', ICON_SIZE_SMALL).'</a>';
            } else { // else if not active
                $actions .= '    <a href="'.$exercisePath.'?'.api_get_cidreq().'&hpchoice=enable&page='.$page.'&file='.$path.'">'.
                    Display::return_icon('invisible.png', get_lang('Activate'), '', ICON_SIZE_SMALL).'</a>';
            }
            $actions .= '<a href="'.$exercisePath.'?'.api_get_cidreq().'&hpchoice=delete&file='.$path.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('AreYouSureToDeleteJS'), ENT_QUOTES, $charset).' '.$title."?").'\')) return false;">'.
                Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>';
            $item .= Display::tag('td', $actions);
            $tableRows[] = Display::tag('tr', $item);
        } else {
            // Student only
            if ($active) {
                $attempt = ExerciseLib::getLatestHotPotatoResult(
                    $path,
                    $userId,
                    api_get_course_int_id(),
                    api_get_session_id()
                );

                $nbrActiveTests = $nbrActiveTests + 1;
                $item .= Display::tag(
                    'td',
                    Display::url(
                        $title,
                        'showinframes.php?'.api_get_cidreq().'&'.http_build_query([
                            'file' => $path,
                            'cid' => api_get_course_id(),
                            'uid' => $userId
                        ])
                    )
                );

                if (!empty($attempt)) {
                    $actions = '<a href="hotpotatoes_exercise_report.php?'.api_get_cidreq().'&path='.$path.'&filter_by_user='.$userId.'">'.Display::return_icon('test_results.png', get_lang('Results'), '', ICON_SIZE_SMALL).'</a>';
                    $attemptText = get_lang('LatestAttempt').' : ';
                    $attemptText .= ExerciseLib::show_score($attempt['exe_result'], $attempt['exe_weighting']).' ';
                    $attemptText .= $actions;
                } else {
                    // No attempts.
                    $attemptText = get_lang('NotAttempted').' ';
                }

                $item .= Display::tag('td', $attemptText);

                if ($isDrhOfCourse) {
                    $actions = '<a href="hotpotatoes_exercise_report.php?'.api_get_cidreq().'&path='.$path.'">'.
                        Display::return_icon('test_results.png', get_lang('Results'), '', ICON_SIZE_SMALL).'</a>';

                    $item .= Display::tag('td', $actions, array('class' => 'td_actions'));
                }
                $tableRows[] = Display::tag('tr', $item);
            }
        }
    }
}

if (empty($exerciseList) && $hotpotatoes_exist == false) {
    if ($is_allowedToEdit && $origin != 'learnpath') {
        echo '<div id="no-data-view">';
        echo '<h3>'.get_lang('Quiz').'</h3>';
        echo Display::return_icon('quiz.png', '', array(), 64);
        echo '<div class="controls">';
        echo Display::url('<em class="fa fa-plus"></em> '.get_lang('NewEx'), 'exercise_admin.php?'.api_get_cidreq(), array('class' => 'btn btn-primary'));
        echo '</div>';
        echo '</div>';
    }
} else {
    if ($is_allowedToEdit) {
        $headers = [
            get_lang('ExerciseName'),
            get_lang('QuantityQuestions'),
            get_lang('Actions')
        ];
    } else {
        $headers = [
            get_lang('ExerciseName'),
            get_lang('Status')
        ];

        if ($isDrhOfCourse) {
            $headers[] = get_lang('Actions');
        }
    }

    $headerList = '';

    foreach ($headers as $header) {
        $headerList .= Display::tag('th', $header);
    }

    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover">';
    echo Display::tag(
        'thead',
        Display::tag('tr', $headerList)
    );
    echo '<tbody>';

    foreach ($tableRows as $row) {
        echo $row;
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

if ($origin != 'learnpath') { //so we are not in learnpath tool
    Display :: display_footer();
}
