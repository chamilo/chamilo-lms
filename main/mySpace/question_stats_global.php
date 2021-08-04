<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_TRACKING;

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('MySpace')];

$courseIdList = $_REQUEST['courses'] ?? [];
$exercises = $_REQUEST['exercises'] ?? [];
$groups = $_REQUEST['groups'] ?? [];
$users = $_REQUEST['users'] ?? [];

$courseOptions = [];
$exerciseList = [];
$selectedExercises = [];
$groupList = [];
$allGroups = [];
$allUsers = [];
if (!empty($courseIdList)) {
    foreach ($courseIdList as $courseId) {
        $courseId = (int) $courseId;
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseGroups = GroupManager::get_group_list(null, $courseInfo);
        if (!empty($courseGroups)) {
            $courseGroups = array_column($courseGroups, 'name', 'iid');
            array_walk(
                $courseGroups,
                function (&$name, $key) use ($courseInfo) {
                    $name = $name.' - '.$courseInfo['title'];
                }
            );
            $allGroups = $allGroups + $courseGroups;
        }

        $courseUsers = CourseManager::get_user_list_from_course_code($courseInfo['code']);
        if (!empty($courseUsers)) {
            array_walk(
                $courseUsers,
                function (&$data, $key) {
                    $data = api_get_person_name($data['firstname'], $data['lastname']);
                }
            );

            $allUsers = $allUsers + $courseUsers;
        }

        $courseExerciseList = ExerciseLib::get_all_exercises(
            $courseInfo,
            0,
            false,
            null,
            false,
            3
        );

        if (!empty($courseExerciseList)) {
            foreach ($courseExerciseList as $exercise) {
                $exerciseId = $exercise['iid'];
                if (in_array($exerciseId, $exercises)) {
                    $selectedExercises[$courseId][] = $exerciseId;
                }
            }
            $exerciseList += array_column($courseExerciseList, 'title', 'iid');
        }
        $courseOptions[$courseId] = $courseInfo['name'];
    }
}

$exerciseList = array_unique($exerciseList);
if (!empty($exerciseList)) {
    array_walk($exerciseList, function (&$title) {
        $title = Exercise::get_formated_title_variable($title);
    });
}

$form = new FormValidator('search_form', 'GET', api_get_self());
$form->addSelectAjax(
    'courses',
    get_lang('Course'),
    $courseOptions,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
        'multiple' => true,
    ]
);

if (!empty($allGroups)) {
    $form->addSelect(
        'groups',
        get_lang('Groups'),
        $allGroups,
        [
            'multiple' => true,
        ]
    );
}

if (!empty($allUsers)) {
    $form->addSelect(
        'users',
        get_lang('Users'),
        $allUsers,
        [
            'multiple' => true,
        ]
    );
}

if (!empty($courseIdList)) {
    $form->addSelect(
        'exercises',
        get_lang('Exercise'),
        $exerciseList,
        [
            'multiple' => true,
        ]
    );
}

$form->setDefaults(['course_id_list' => array_keys($courseOptions)]);
$form->addButtonSearch(get_lang('Search'));

$tableContent = '';
if ($form->validate()) {
    $headers = [
        get_lang('Course'),
        get_lang('Exercise'),
        get_lang('Question'),
        get_lang('WrongAnswer').' / '.get_lang('Total'),
        '%',
    ];

    $scoreDisplay = new ScoreDisplay();
    $exercises = $form->getSubmitValue('exercises');
    if ($exercises) {
        $orderedData = [];
        foreach ($selectedExercises as $courseId => $selectedExerciseList) {
            foreach ($selectedExerciseList as $exerciseId) {
                $questions = ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, null, $groups, $users);
                foreach ($questions as $data) {
                    $questionId = (int) $data['question_id'];
                    $total = ExerciseLib::getTotalQuestionAnswered(
                        $courseId,
                        $exerciseId,
                        $questionId,
                        null,
                        $groups,
                        $users
                    );
                    $orderedData[] = [
                        $courseOptions[$courseId],
                        $exerciseList[$exerciseId],
                        $data['question'],
                        $data['count'].' / '.$total,
                        $scoreDisplay->display_score([$data['count'], $total], SCORE_AVERAGE),
                    ];
                }
            }
        }

        $table = new SortableTableFromArray(
            $orderedData,
            0,
            100,
            'question_tracking'
        );
        $table->hideNavigation = true;
        $table->column = 4;
        $column = 0;
        foreach ($headers as $header) {
            $table->set_header($column, $header, false);
            $column++;
        }
        $tableContent = $table->return_table();
    }
}

$nameTools = get_lang('ExerciseManagement');
$htmlHeadXtra[] = '<script>
$(function() {
 $("#search_form").submit();
    $("#search_form_courses").on("change", function (e) {
       $("#search_form_exercises").parent().parent().parent().hide();
       $("#search_form_exercises").each(function() {
            $(this).remove();
        });
    });
});
</script>';

Display::display_header($nameTools, get_lang('Exercise'));
$form->display();
echo $tableContent;

Display::display_footer();
