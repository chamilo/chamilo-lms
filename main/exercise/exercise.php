<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise list: This script shows the list of exercises for administrators and students.
 *
 * @package chamilo.exercise
 *
 * @author Olivier Brouckaert, original author
 * @author Denes Nagy, HotPotatoes integration
 * @author Wolfgang Schneider, code/html cleanup
 * @author Julio Montoya <gugli100@gmail.com>, lots of cleanup + several improvements
 * Modified by hubert.borderiou (question category)
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;

// Setting the tabs
$this_section = SECTION_COURSES;

//$htmlHeadXtra[] = api_get_asset('qtip2/jquery.qtip.min.js');
//$htmlHeadXtra[] = api_get_css_asset('qtip2/jquery.qtip.min.css');
api_protect_course_script(true);

$limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');

$check = Security::get_existing_token('get');

$currentUrl = api_get_self().'?'.api_get_cidreq();

require_once 'hotpotatoes.lib.php';

/*  Constants and variables */
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
$documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
// picture path
$picturePath = $documentPath.'/images';
// audio path
$audioPath = $documentPath.'/audio';

// hot potatoes
$uploadPath = DIR_HOTPOTATOES; //defined in main_api
$exercisePath = api_get_self();
$exfile = explode('/', $exercisePath);
$exfile = strtolower($exfile[count($exfile) - 1]);
$exercisePath = substr($exercisePath, 0, strpos($exercisePath, $exfile));
$exercisePath = $exercisePath.'exercise.php';

// Clear the exercise session
Exercise::cleanSessionVariables();

//General POST/GET/SESSION/COOKIES parameters recovery
$origin = api_get_origin();
$choice = isset($_REQUEST['choice']) ? Security::remove_XSS($_REQUEST['choice']) : null;
$hpchoice = isset($_REQUEST['hpchoice']) ? Security::remove_XSS($_REQUEST['hpchoice']) : null;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : null;
$file = isset($_REQUEST['file']) ? Database::escape_string($_REQUEST['file']) : null;
$learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : null;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : null;
$categoryId = isset($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : 0;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$keyword = isset($_REQUEST['keyword']) ? Security::remove_XSS($_REQUEST['keyword']) : '';

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$nameTools = get_lang('Tests');

// Simple actions
if ($is_allowedToEdit) {
    switch ($action) {
        case 'clean_all_test':
            if ($check) {
                if ($limitTeacherAccess && !api_is_platform_admin()) {
                    api_not_allowed(true);
                }

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
                            $quantity_results_deleted += $objExerciseTmp->cleanResults(true);
                        }
                    }
                }

                Display::addFlash(Display::return_message(
                    sprintf(
                        get_lang('%d results cleaned'),
                        $quantity_results_deleted
                    ),
                    'confirm'
                ));

                header('Location: '.$currentUrl);
                exit;
            }
            break;
        case 'exportqti2':
            if ($limitTeacherAccess && !api_is_platform_admin()) {
                api_not_allowed(true);
            }
            require_once api_get_path(SYS_CODE_PATH).'exercise/export/qti2/qti2_export.php';
            $export = export_exercise_to_qti($exerciseId, true);

            $xmlReader = new XMLReader();
            $xmlReader->xml($export);
            $xmlReader->setParserProperty(XMLReader::VALIDATE, true);
            $isValid = $xmlReader->isValid();

            if ($isValid) {
                $name = 'qti2_export_'.$exerciseId.'.zip';
                $zip = api_create_zip($name);
                $zip->addFile('qti2export_'.$exerciseId.'.xml', $export);
                $zip->finish();
                exit;
            } else {
                Display::addFlash(Display::return_message(get_lang('There was an error writing the XML file. Please ask the administrator to check the error logs.'), 'error'));
                header('Location: '.$currentUrl);
                exit;
            }
            break;
        case 'up_category':
        case 'down_category':
            $categoryIdFromGet = isset($_REQUEST['category_id_edit']) ? $_REQUEST['category_id_edit'] : 0;
            $em = Database::getManager();
            $repo = $em->getRepository('ChamiloCourseBundle:CExerciseCategory');
            $category = $repo->find($categoryIdFromGet);
            $currentPosition = $category->getPosition();

            if ($action === 'up_category') {
                $currentPosition--;
            } else {
                $currentPosition++;
            }
            $category->setPosition($currentPosition);
            $em->persist($category);
            $em->flush();
            Display::addFlash(Display::return_message(get_lang('Update successful')));

            header('Location: '.$currentUrl);
            exit;

            break;
    }
}

// Mass actions
if (!empty($action) && $is_allowedToEdit) {
    $exerciseListToEdit = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
    if (!empty($exerciseListToEdit)) {
        foreach ($exerciseListToEdit as $exerciseIdToEdit) {
            $objExerciseTmp = new Exercise();
            $result = $objExerciseTmp->read($exerciseIdToEdit);

            if (empty($result)) {
                continue;
            }

            switch ($action) {
                case 'delete':
                    $objExerciseTmp->delete();
                    break;
                case 'visible':
                    if ($limitTeacherAccess && !api_is_platform_admin()) {
                        // Teacher change exercise
                        break;
                    }

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

                    break;
                case 'invisible':
                    if ($limitTeacherAccess && !api_is_platform_admin()) {
                        // Teacher change exercise
                        break;
                    }

                    // enables an exercise
                    if (empty($sessionId)) {
                        $objExerciseTmp->disable();
                        $objExerciseTmp->save();
                    } else {
                        if (!empty($objExerciseTmp->sessionId)) {
                            $objExerciseTmp->disable();
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
                    break;
            }
        }
        Display::addFlash(Display::return_message(get_lang('Update successful')));
        header('Location: '.$currentUrl);
        exit;
    }
}

Event::event_access_tool(TOOL_QUIZ);

$logInfo = [
    'tool' => TOOL_QUIZ,
    'tool_id' => (int) $exerciseId,
    'action' => isset($_REQUEST['learnpath_id']) ? 'learnpath_id' : '',
    'action_details' => isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : '',
];
Event::registerLog($logInfo);

HotPotGCt($documentPath, 1, $userId);

// Only for administrator
if ($is_allowedToEdit) {
    if (!empty($choice)) {
        // single exercise choice
        // construction of Exercise
        $objExerciseTmp = new Exercise();
        $exercise_action_locked = api_resource_is_locked_by_gradebook(
            $exerciseId,
            LINK_EXERCISE
        );

        if ($objExerciseTmp->read($exerciseId)) {
            if ($check) {
                switch ($choice) {
                    case 'enable_launch':
                        $objExerciseTmp->cleanCourseLaunchSettings();
                        $objExerciseTmp->enableAutoLaunch();
                        Display::addFlash(Display::return_message(get_lang('Updated')));
                        break;
                    case 'disable_launch':
                        $objExerciseTmp->cleanCourseLaunchSettings();
                        break;
                    case 'delete':
                        // deletes an exercise
                        $result = $objExerciseTmp->delete();
                        if ($result) {
                            Display::addFlash(Display::return_message(get_lang('ExerciseDeleted'), 'confirmation'));
                        }
                        break;
                    case 'enable':
                        if ($limitTeacherAccess && !api_is_platform_admin()) {
                            // Teacher change exercise
                            break;
                        }

                        // Enables an exercise
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
                        Display::addFlash(Display::return_message(get_lang('VisibilityChanged'), 'confirmation'));
                        break;
                    case 'disable':
                        if ($limitTeacherAccess && !api_is_platform_admin()) {
                            // Teacher change exercise
                            break;
                        }
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
                        Display::addFlash(Display::return_message(get_lang('The visibility has been changed.'), 'confirmation'));
                        break;
                    case 'disable_results':
                        //disable the results for the learners
                        $objExerciseTmp->disable_results();
                        $objExerciseTmp->save();
                        Display::addFlash(Display::return_message(get_lang('Results disabled for learners'), 'confirmation'));

                        break;
                    case 'enable_results':
                        //disable the results for the learners
                        $objExerciseTmp->enable_results();
                        $objExerciseTmp->save();
                        Display::addFlash(Display::return_message(get_lang('Results enabled for learners'), 'confirmation'));

                        break;
                    case 'clean_results':
                        if ($limitTeacherAccess && !api_is_platform_admin()) {
                            // Teacher change exercise
                            break;
                        }

                        // Clean student results
                        if ($exercise_action_locked == false) {
                            $quantity_results_deleted = $objExerciseTmp->cleanResults(true);
                            $title = $objExerciseTmp->selectTitle();

                            Display::addFlash(
                                Display::return_message(
                                    $title.': '.sprintf(
                                        get_lang('%d results cleaned'),
                                        $quantity_results_deleted
                                    ),
                                    'confirmation'
                                )
                            );
                        }
                        break;
                    case 'copy_exercise': //copy an exercise
                        api_set_more_memory_and_time_limits();
                        $objExerciseTmp->copyExercise();
                        Display::addFlash(Display::return_message(
                            get_lang('Test copied'),
                            'confirmation'
                        ));
                        break;
                }
                header('Location: '.$currentUrl);
                exit;
            }
        }
        // destruction of Exercise
        unset($objExerciseTmp);
        Security::clear_token();
    }

    if (!empty($hpchoice)) {
        switch ($hpchoice) {
            case 'delete':
                if ($limitTeacherAccess && !api_is_platform_admin()) {
                    // Teacher change exercise
                    break;
                }
                // deletes an exercise
                $imgparams = [];
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
                if (!(strstr($uploadPath, DIR_HOTPOTATOES) &&
                    !folder_is_empty($documentPath.$uploadPath."/".$fld."/"))
                ) {
                    my_delete($documentPath.$uploadPath."/".$fld."/");
                }
                break;
            case 'enable': // enables an exercise
                if ($limitTeacherAccess && !api_is_platform_admin()) {
                    // Teacher change exercise
                    break;
                }

                $newVisibilityStatus = '1'; //"visible"
                $query = "SELECT id FROM $TBL_DOCUMENT
                          WHERE c_id = $courseId AND path='".Database::escape_string($file)."'";
                $res = Database::query($query);
                $row = Database :: fetch_array($res, 'ASSOC');
                api_item_property_update(
                    $courseInfo,
                    TOOL_DOCUMENT,
                    $row['id'],
                    'visible',
                    $userId
                );

                Display::addFlash(Display::return_message(get_lang('Update successful')));

                break;
            case 'disable': // disables an exercise
                if ($limitTeacherAccess && !api_is_platform_admin()) {
                    // Teacher change exercise
                    break;
                }
                $newVisibilityStatus = '0'; //"invisible"
                $query = "SELECT id FROM $TBL_DOCUMENT
                          WHERE c_id = $courseId AND path='".Database::escape_string($file)."'";
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
        header('Location: '.$currentUrl);
        exit;
    }
}

if ($origin !== 'learnpath') {
    //so we are not in learnpath tool
    Display::display_header($nameTools, get_lang('Test'));
    if (isset($_GET['message']) && in_array($_GET['message'], ['ExerciseEdited'])) {
        echo Display::return_message(get_lang('TestEdited'), 'confirmation');
    }
} else {
    Display::display_reduced_header();
}
Display::display_introduction_section(TOOL_QUIZ);

// Selects $limit exercises at the same time
// maximum number of exercises on a same page
$limit = Exercise::PAGINATION_ITEMS_PER_PAGE;

HotPotGCt($documentPath, 1, $userId);

$token = Security::get_token();
if ($is_allowedToEdit && $origin !== 'learnpath') {
    $actionsLeft = '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise_admin.php?'.api_get_cidreq().'">'.
        Display::return_icon('new_exercice.png', get_lang('Create a new test'), '', ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/question_create.php?'.api_get_cidreq().'">'.
        Display::return_icon('new_question.png', get_lang('Add a question'), '', ICON_SIZE_MEDIUM).'</a>';

    if (api_get_configuration_value('allow_exercise_categories')) {
        $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/category.php?'.api_get_cidreq().'">';
        $actionsLeft .= Display::return_icon('folder.png', get_lang('Category'), '', ICON_SIZE_MEDIUM);
        $actionsLeft .= '</a>';
    }

    // Question category
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/tests_category.php?'.api_get_cidreq().'">';
    $actionsLeft .= Display::return_icon('green_open.png', get_lang('Questions category'), '', ICON_SIZE_MEDIUM);
    $actionsLeft .= '</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/question_pool.php?'.api_get_cidreq().'">';
    $actionsLeft .= Display::return_icon('database.png', get_lang('Recycle existing questions'), '', ICON_SIZE_MEDIUM);
    $actionsLeft .= '</a>';

    //echo Display::url(Display::return_icon('looknfeel.png', get_lang('Media')), 'media.php?' . api_get_cidreq());
    // end question category
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/hotpotatoes.php?'.api_get_cidreq().'">'.
        Display::return_icon('import_hotpotatoes.png', get_lang('Import Hotpotatoes'), '', ICON_SIZE_MEDIUM).'</a>';
    // link to import qti2 ...
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/qti2.php?'.api_get_cidreq().'">'.
        Display::return_icon('import_qti2.png', get_lang('Import exercises Qti2'), '', ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/aiken.php?'.api_get_cidreq().'">'.
        Display::return_icon('import_aiken.png', get_lang('Import Aiken quiz'), '', ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/upload_exercise.php?'.api_get_cidreq().'">'.
        Display::return_icon('import_excel.png', get_lang('Import quiz from Excel'), '', ICON_SIZE_MEDIUM).'</a>';

    $cleanAll = Display::url(
        Display::return_icon(
            'clean_all.png',
            get_lang('Are you sure to delete all test\'s results ?'),
            '',
            ICON_SIZE_MEDIUM
        ),
        '#',
        [
            'data-item-question' => addslashes(get_lang('Clear all learners results for every exercises ?')),
            'data-href' => api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq().'&action=clean_all_test&sec_token='.$token,
            'data-toggle' => 'modal',
            'data-target' => '#confirm-delete',
        ]
    );

    if ($limitTeacherAccess) {
        if (api_is_platform_admin()) {
            $actionsLeft .= $cleanAll;
        }
    } else {
        $actionsLeft .= $cleanAll;
    }

    // Create a search-box
    $form = new FormValidator('search_simple', 'get', $currentUrl, null, null, FormValidator::LAYOUT_INLINE);
    $form->addCourseHiddenParams();

    if (api_get_configuration_value('allow_exercise_categories')) {
        $manager = new ExerciseCategoryManager();
        $options = $manager->getCategoriesForSelect(api_get_course_int_id());
        if (!empty($options)) {
            $form->addSelect(
                'category_id',
                get_lang('Category'),
                $options,
                ['placeholder' => get_lang('Please select an option'), 'disable_js' => true]
            );
        }
    }

    $form->addText(
        'keyword',
        get_lang('Search'),
        false,
        [
            'aria-label' => get_lang('Search'),
        ]
    );
    $form->addButtonSearch(get_lang('Search'));
    $actionsRight = $form->returnForm();
}

if ($is_allowedToEdit) {
    echo Display::toolbarAction(
        'toolbarUser',
        [$actionsLeft, '', $actionsRight],
        [6, 1, 5]
    );
}
if (api_get_configuration_value('allow_exercise_categories') === false) {
    echo Exercise::exerciseGrid(0, $keyword);
} else {
    if (empty($categoryId)) {
        echo Display::page_subheader(get_lang('General'));
        echo Exercise::exerciseGrid(0, $keyword);
        $counter = 0;
        $manager = new ExerciseCategoryManager();
        $categories = $manager->getCategories($courseId);
        $modifyUrl = api_get_self().'?'.api_get_cidreq();
        $total = count($categories);
        $upIcon = Display::return_icon('up.png', get_lang('Move up'));
        $downIcon = Display::return_icon('down.png', get_lang('Move down'));
        /** @var \Chamilo\CourseBundle\Entity\CExerciseCategory $category */
        foreach ($categories as $category) {
            $categoryIdItem = $category->getId();
            $up = '';
            $down = '';
            if ($is_allowedToEdit) {
                $up = Display::url($upIcon, $modifyUrl.'&action=up_category&category_id_edit='.$categoryIdItem);
                if ($counter === 0) {
                    $up = Display::url(Display::return_icon('up_na.png'), '#');
                }
                $down = Display::url($downIcon, $modifyUrl.'&action=down_category&category_id_edit='.$categoryIdItem);
                $counter++;

                if ($total === $counter) {
                    $down = Display::url(Display::return_icon('down_na.png'), '#');
                }
            }
            echo Display::page_subheader($category->getName().$up.$down);
            echo Exercise::exerciseGrid($category->getId(), $keyword);
        }
    } else {
        $manager = new ExerciseCategoryManager();
        $category = $manager->get($categoryId);
        echo Display::page_subheader($category['name']);
        echo Exercise::exerciseGrid($category['id'], $keyword);
    }
}

if ($origin !== 'learnpath') {
    // We are not in learnpath tool
    Display::display_footer();
}
