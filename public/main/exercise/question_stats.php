<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

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
$course  = api_get_course_entity();
$courseId = api_get_course_int_id();

if (empty($result)) {
    api_not_allowed(true);
}

$nameTools = get_lang('Question statistics');
$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];
$interbreadcrumb[] = [
    'url' => 'admin.php?exerciseId='.$exercise->iId.'&'.api_get_cidreq(),
    'name' => $exercise->selectTitle(true),
];

$interbreadcrumb[] = [
    'url' => 'exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exercise->iId,
    'name' => get_lang('Learner score'),
];

$form = new FormValidator('search_form', 'GET', api_get_self().'?id='.$exerciseId.'&'.api_get_cidreq());
$form->addCourseHiddenParams();
$form->addHidden('id', $exerciseId);

$courseGroups = GroupManager::get_group_list(null, $course);

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

$courseUsers = CourseManager::get_user_list_from_course_code($course->getCode());
if (!empty($courseUsers)) {
    array_walk(
        $courseUsers,
        function (&$data, $key) {
            $data = api_get_person_name($data['firstname'], $data['lastname']);
        }
    );
}

$form->addSelect('users', get_lang('Users'), $courseUsers, [
    'multiple' => true,
    'id' => 'users-select',
    'class' => 'form-control select2',
    'style' => 'width:100%',
]);

$form->addButtonSearch(get_lang('Search'));

$formToString = $form->toHtml();

$scoreDisplay = new ScoreDisplay();

/* -----------------------------------------------------------------------------
 * Helpers (new)
 * -----------------------------------------------------------------------------
 * We move the "wrong stats" from item-level to attempt-level:
 *   - total attempts per question = COUNT(DISTINCT exe_id) answering that question
 *   - incorrect attempts          = COUNT(DISTINCT exe_id) where marks < max_score
 *
 * IMPORTANT: adjust column names if needed
 * - track_e_attempt:  marks, max_score     (per question, per attempt)
 * - track_e_exercises: exe_id, exe_exo_id, exe_user_id, c_id, status, session_id
 * ---------------------------------------------------------------------------*/

/** Build user filter list from users and (optionally) groups. */
function qp_build_user_filter(array $users, array $groups, $course): array
{
    $ids = array_map('intval', $users ?? []);

    // Best-effort group → users expansion. If GroupManager API differs, adapt here.
    if (!empty($groups)) {
        foreach ($groups as $gid) {
            $gid = (int) $gid;
            if ($gid <= 0) {
                continue;
            }
            // Try common helper names used in Chamilo code base.
            if (method_exists('GroupManager', 'get_users')) {
                $gUsers = GroupManager::get_users($gid, $course) ?: [];
                foreach ($gUsers as $uid => $_) {
                    $ids[] = (int) $uid;
                }
            } elseif (method_exists('GroupManager', 'get_subscribed_users')) {
                $gUsers = GroupManager::get_subscribed_users($gid) ?: [];
                foreach ($gUsers as $u) {
                    $ids[] = (int) ($u['user_id'] ?? 0);
                }
            }
        }
    }

    $ids = array_values(array_unique(array_filter($ids)));
    return $ids;
}

/** Return "AND te.session_id ..." clause matching current session filter. */
function qp_session_clause(int $sessionId): string
{
    if ($sessionId > 0) {
        return ' AND te.session_id = '.$sessionId.' ';
    }

    // No session → consider base course attempts (NULL or 0)
    return ' AND (te.session_id IS NULL OR te.session_id = 0) ';
}

/** Count DISTINCT exe_id that answered the question (total attempts). */
function qp_total_attempts_for_question(
    int $courseId,
    int $exerciseId,
    int $questionId,
    int $sessionId,
    array $userIds = []
): int {
    $TBL_ATT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $TBL_EXE = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

    $userSql = '';
    if (!empty($userIds)) {
        $userSql = ' AND te.exe_user_id IN ('.implode(',', array_map('intval', $userIds)).') ';
    }

    $sql = "
        SELECT COUNT(DISTINCT te.exe_id) AS total
        FROM $TBL_ATT ta
        INNER JOIN $TBL_EXE te ON te.exe_id = ta.exe_id
        WHERE te.c_id = $courseId
          AND te.exe_exo_id = $exerciseId
          AND ta.question_id = $questionId
          AND te.status <> 'incomplete'
          ".qp_session_clause($sessionId)."
          $userSql
    ";

    $row = Database::fetch_assoc(Database::query($sql));
    return (int) ($row['total'] ?? 0);
}

/** Count DISTINCT exe_id where the question was not fully correct (attempt-level). */
function qp_incorrect_attempts_for_question(
    int $courseId,
    int $exerciseId,
    int $questionId,
    int $sessionId,
    array $userIds = []
): int {
    $TBL_ATT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
    $TBL_EXE = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $TBL_QQQ = Database::get_course_table(TABLE_QUIZ_QUESTION); // c_quiz_question

    $userSql = '';
    if (!empty($userIds)) {
        $userSql = ' AND te.exe_user_id IN ('.implode(',', array_map('intval', $userIds)).') ';
    }

    // Incorrect if question didn't reach its full score
    $sql = "
        SELECT COUNT(DISTINCT te.exe_id) AS wrong
        FROM $TBL_ATT ta
        INNER JOIN $TBL_EXE te ON te.exe_id = ta.exe_id
        INNER JOIN $TBL_QQQ qq ON qq.iid = ta.question_id
        WHERE te.c_id = $courseId
          AND te.exe_exo_id = $exerciseId
          AND ta.question_id = $questionId
          AND te.status <> 'incomplete'
          AND ta.marks < qq.ponderation
          ".qp_session_clause($sessionId)."
          $userSql
    ";

    $row = Database::fetch_assoc(Database::query($sql));
    return (int) ($row['wrong'] ?? 0);
}


/** Fetch candidate questions to list (keeps previous behavior for compatibility). */
function qp_fetch_questions(int $courseId, int $exerciseId, int $sessionId, array $groups = [], array $users = []): array
{
    // Reuse existing helper to get question labels; we will ignore its "count" and recompute
    // with the new attempt-based logic to avoid changing other parts of the code.
    if (!empty($groups) || !empty($users)) {
        return ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, $sessionId, $groups, $users);
    }

    return ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, $sessionId);
}

/* -----------------------------------------------------------------------------
 * Build table rows
 * ---------------------------------------------------------------------------*/
$orderedData = [];
$userFilterIds = qp_build_user_filter($users, $groups, $course);

if ($form->validate()) {
    $questions = qp_fetch_questions($courseId, $exerciseId, $sessionId, $groups, $users);
} else {
    $questions = qp_fetch_questions($courseId, $exerciseId, $sessionId);
}

// If for some reason there are no "wrong" records (all perfect), we still want to
// show the questions of the exercise; we can fall back to the exercise structure.
if (empty($questions)) {
    $exerciseEntity = Container::getQuizRepository()->find($exerciseId);
    if ($exerciseEntity) {
        $seen = [];
        foreach ($exerciseEntity->getQuestions() as $rel) { // CQuizRelQuestion
            $qq = $rel->getQuestion();                      // CQuizQuestion
            if (!$qq) { continue; }
            $qid = (int) $qq->getIid();

            if (isset($seen[$qid])) { continue; }
            $seen[$qid] = true;

            $questions[] = [
                'question_id' => $qid,
                'question'    => $qq->getQuestion(),
                'count'       => 0,
            ];
        }
    }
}

foreach ($questions as $data) {
    $questionId = (int) $data['question_id'];

    $total     = qp_total_attempts_for_question($courseId, $exerciseId, $questionId, $sessionId, $userFilterIds);
    $incorrect = qp_incorrect_attempts_for_question($courseId, $exerciseId, $questionId, $sessionId, $userFilterIds);

    // Keep UI contract: "Wrong / Total" and "%".
    $orderedData[] = [
        $data['question'],
        $incorrect.' / '.$total,
        $total > 0 ? $scoreDisplay->display_score([$incorrect, $total], SCORE_AVERAGE) : '0%',
    ];
}

/* -----------------------------------------------------------------------------
 * Render
 * ---------------------------------------------------------------------------*/
$table = new SortableTableFromArray(
    $orderedData,
    0,
    100,
    'question_tracking'
);

$table->hideNavigation = true;

$headers = [
    get_lang('Question'),
    get_lang('Wrong answer').' / '.get_lang('Total'),
    '%',
];

$table->column = 2;
$col = 0;
foreach ($headers as $header) {
    $table->set_header($col, $header, false);
    $col++;
}

Display::display_header($nameTools, get_lang('Test'));
echo Display::page_header(
    $exercise->selectTitle(true).' — '.get_lang('Question statistics')
);
echo '<div class="panel panel-default mt-8"><div class="panel-body">';
echo $formToString;
echo '</div></div>';
?>
    <script>
        $(function(){
            $("#users-select").select2({
                width: "100%",
                placeholder: "<?php echo addslashes(get_lang('Filter users')) ?>",
                closeOnSelect: false
            });
        });
    </script>

<?php
echo '<div class="table-responsive">';
echo $table->return_table();
echo '</div>';
Display::display_footer();
