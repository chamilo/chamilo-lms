<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

api_protect_course_script(true);

$isAllowedToEdit = api_is_allowed_to_edit(null, true);

if (!$isAllowedToEdit) {
    api_not_allowed(true);
}

$exerciseId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$groups = $_REQUEST['groups'] ?? [];
$users = $_REQUEST['users'] ?? [];

if (empty($exerciseId)) {
    api_not_allowed(true);
}

$sessionId = api_get_session_id();

$exercise = new Exercise();
$result = $exercise->read($exerciseId);

if (empty($result)) {
    api_not_allowed(true);
}

$nameTools = get_lang('ExerciseManagement');
$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];
$interbreadcrumb[] = [
    'url' => 'admin.php?exerciseId='.$exercise->iid.'&'.api_get_cidreq(),
    'name' => $exercise->selectTitle(true),
];

$interbreadcrumb[] = [
    'url' => 'exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exercise->iid,
    'name' => get_lang('StudentScore'),
];
$courseId = api_get_course_int_id();
$courseInfo = api_get_course_info();

$form = new FormValidator('search_form', 'GET', api_get_self().'?id='.$exerciseId.'&'.api_get_cidreq());
$form->addCourseHiddenParams();
$form->addHidden('id', $exerciseId);

$courseGroups = GroupManager::get_group_list(null, $courseInfo);

if (!empty($courseGroups)) {
    $courseGroups = array_column($courseGroups, 'name', 'iid');
    $form->addSelect(
        'groups',
        get_lang('Groups'),
        $courseGroups,
        [
            'multiple' => true,
        ]
    );
}

$courseUsers = CourseManager::get_user_list_from_course_code($courseInfo['code']);
if (!empty($courseUsers)) {
    array_walk(
        $courseUsers,
        function (&$data, $key) {
            $data = api_get_person_name($data['firstname'], $data['lastname']);
        }
    );
}

$form->addSelect(
    'users',
    get_lang('Users'),
    $courseUsers,
    [
        'multiple' => true,
    ]
);

$form->addButtonSearch(get_lang('Search'));

$formToString = $form->toHtml();

$table = new HTML_Table(['class' => 'table table-hover table-striped']);
$row = 0;
$column = 0;

$headers = [
    get_lang('Question'),
    get_lang('Group'),
    get_lang('User'),
    get_lang('WrongAnswer').' / '.get_lang('Total'),
    '%',
];

foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}
$row++;
$scoreDisplay = new ScoreDisplay();

if ($form->validate()) {
    if (!empty($groups)) {
        foreach ($groups as $groupId) {
            $group = api_get_group_entity($groupId);

            if (null === $group) {
                continue;
            }

            $questions = ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, $sessionId, $groupId);
            foreach ($questions as $data) {
                $questionId = (int) $data['question_id'];
                $total = ExerciseLib::getTotalQuestionAnswered(
                    $courseId,
                    $exerciseId,
                    $questionId,
                    $sessionId,
                    $groupId
                );
                $table->setCellContents($row, 0, $data['question']);
                $table->setCellContents($row, 1, $group->getName());
                $table->setCellContents($row, 2, '');
                $table->setCellContents($row, 3, $data['count'].' / '.$total);
                $table->setCellContents($row, 4, $scoreDisplay->display_score([$data['count'], $total], SCORE_AVERAGE));
                $row++;
            }
        }
    }

    if (!empty($users)) {
        foreach ($users as $userId) {
            $user = api_get_user_entity($userId);
            if (null === $user) {
                continue;
            }
            $questions = ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, $sessionId, null, $userId);
            foreach ($questions as $data) {
                $questionId = (int) $data['question_id'];
                $total = ExerciseLib::getTotalQuestionAnswered(
                    $courseId,
                    $exerciseId,
                    $questionId,
                    $sessionId,
                    null,
                    $userId
                );
                $table->setCellContents($row, 0, $data['question']);
                $table->setCellContents($row, 1, '');
                $table->setCellContents($row, 2, UserManager::formatUserFullName($user));
                $table->setCellContents($row, 3, $data['count'].' / '.$total);
                $table->setCellContents($row, 4, $scoreDisplay->display_score([$data['count'], $total], SCORE_AVERAGE));
                $row++;
            }
        }
    }
} else {
    $questions = ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, $sessionId);
    foreach ($questions as $data) {
        $questionId = (int) $data['question_id'];
        $total = ExerciseLib::getTotalQuestionAnswered($courseId, $exerciseId, $questionId, $sessionId);
        $table->setCellContents($row, 0, $data['question']);
        $table->setCellContents($row, 1, '');
        $table->setCellContents($row, 2, '');
        $table->setCellContents($row, 3, $data['count'].' / '.$total);
        $table->setCellContents($row, 4, $scoreDisplay->display_score([$data['count'], $total], SCORE_AVERAGE));
        $row++;
    }
}

Display::display_header($nameTools, get_lang('Exercise'));
echo $formToString;
echo $table->toHtml();
Display::display_footer();
