<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField as ExtraFieldEntity;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use ChamiloSession as Session;
use Doctrine\Common\Collections\Criteria;
use Knp\Component\Pager\Event\Subscriber\Paginate\PaginationSubscriber;
use Knp\Component\Pager\Event\Subscriber\Sortable\SortableSubscriber;
use Knp\Component\Pager\Paginator;
use Symfony\Component\EventDispatcher\EventDispatcher;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

$user = api_get_current_user();

if (!api_is_platform_admin() && (!$user || !$user->hasRole('ROLE_QUESTION_MANAGER'))) {
    api_not_allowed(true);
}

api_block_inactive_user();

Session::erase('objExercise');
Session::erase('objQuestion');
Session::erase('objAnswer');

$deleteTokenPrefix = 'admin_questions_delete';
$deleteTokenVar = $deleteTokenPrefix.'_sec_token';
$deleteToken = Security::get_existing_token($deleteTokenPrefix);

// Ensure a CSRF token exists for destructive actions (delete, etc).
$token = Security::get_token();

$interbreadcrumb[] = [
    'url' => Container::getRouter()->generate('admin'),
    'name' => get_lang('Administration'),
];

$action = $_REQUEST['action'] ?? '';
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$description = $_REQUEST['description'] ?? '';
$title = $_REQUEST['title'] ?? '';
$page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;

// Base URL used for redirects (keeps the current filters when possible)
$url = api_get_self();

// -----------------------------
// Helpers (kept local on purpose)
// -----------------------------

/**
 * Resolve a DBAL SchemaManager compatible with DBAL 2/3.
 */
$resolveSchemaManager = static function ($connection) {
    if (method_exists($connection, 'createSchemaManager')) {
        return $connection->createSchemaManager();
    }

    return $connection->getSchemaManager();
};

/**
 * Fetch all rows as associative arrays (DBAL 2/3 compatible).
 */
$fetchAllAssociative = static function ($connection, string $sql, array $params = []): array {
    try {
        if (method_exists($connection, 'fetchAllAssociative')) {
            return $connection->fetchAllAssociative($sql, $params);
        }

        if (method_exists($connection, 'fetchAll')) {
            return (array) $connection->fetchAll($sql, $params);
        }
    } catch (\Throwable $e) {
        return [];
    }

    return [];
};

/**
 * Build question type icon HTML without requiring a course context.
 */
function build_question_type_icon_html(int $type): string
{
    $instance = Question::getInstance($type);
    if (!$instance instanceof Question) {
        return '';
    }

    $typeImg = $instance->getTypePicture();
    $typeExpl = $instance->getExplanation();

    if (empty($typeImg)) {
        return '';
    }

    return Display::tag(
        'div',
        Display::return_icon($typeImg, $typeExpl, [], 32),
        []
    );
}

/**
 * Find the best relation table and column names to map question -> exercises across courses.
 * This is defensive to reduce breakage across schema variants.
 *
 * Returns:
 *  - table: string
 *  - question_col: string
 *  - exercise_col: string
 *  - course_col: string|null
 */
$resolveQuestionExerciseRelation = static function ($connection) use ($resolveSchemaManager): array {
    static $cached = null;
    if (null !== $cached) {
        return $cached;
    }

    $sm = $resolveSchemaManager($connection);
    $tableNames = method_exists($sm, 'listTableNames') ? $sm->listTableNames() : [];

    $candidates = [
        'c_quiz_rel_question',
        'quiz_rel_question',
        'c_quiz_question_rel_exercise',
        'quiz_question_rel_exercise',
    ];

    $table = null;
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $tableNames, true)) {
            $table = $candidate;
            break;
        }
    }

    if (empty($table)) {
        $table = 'c_quiz_rel_question';
    }

    $columns = [];
    try {
        $cols = $sm->listTableColumns($table);
        foreach ($cols as $colName => $col) {
            $columns[] = $colName;
        }
    } catch (\Throwable $e) {
        $columns = [];
    }

    // Most common
    $questionCol = 'question_id';

    $exerciseCol = null;
    foreach (['quiz_id', 'exercise_id', 'exercice_id', 'quizid'] as $c) {
        if (in_array($c, $columns, true)) {
            $exerciseCol = $c;
            break;
        }
    }
    if (null === $exerciseCol) {
        $exerciseCol = 'quiz_id';
    }

    $courseCol = null;
    foreach (['c_id', 'course_id', 'cid'] as $c) {
        if (in_array($c, $columns, true)) {
            $courseCol = $c;
            break;
        }
    }

    $cached = [
        'table' => $table,
        'question_col' => $questionCol,
        'exercise_col' => $exerciseCol,
        'course_col' => $courseCol, // can be null
    ];

    return $cached;
};

/**
 * Fetch a map of questionId => list of exercise references.
 * Each ref: ['course_id' => int, 'exercise_id' => int]
 *
 * NOTE: If the relation table doesn't contain course id, we try to join with a quiz table to infer it.
 */
$fetchExerciseRefsForQuestionIds = static function ($connection, array $questionIds) use (
    $resolveSchemaManager,
    $resolveQuestionExerciseRelation,
    $fetchAllAssociative
): array {
    $result = [];
    if (empty($questionIds)) {
        return $result;
    }

    $relation = $resolveQuestionExerciseRelation($connection);
    $relTable = $relation['table'];
    $qCol = $relation['question_col'];
    $eCol = $relation['exercise_col'];
    $cCol = $relation['course_col']; // may be null

    $questionIds = array_values(array_filter(array_map('intval', $questionIds), static fn ($v) => $v > 0));
    if (empty($questionIds)) {
        return $result;
    }

    // If we have course id directly in the relation table, use it.
    if (!empty($cCol)) {
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $sql = "SELECT DISTINCT $qCol AS qid, $cCol AS cid, $eCol AS eid
                FROM $relTable
                WHERE $qCol IN ($placeholders)";

        $rows = $fetchAllAssociative($connection, $sql, $questionIds);
        foreach ($rows as $row) {
            $qid = (int) ($row['qid'] ?? 0);
            $cid = (int) ($row['cid'] ?? 0);
            $eid = (int) ($row['eid'] ?? 0);
            if ($qid <= 0 || $cid <= 0 || $eid <= 0) {
                continue;
            }
            $result[$qid][] = ['course_id' => $cid, 'exercise_id' => $eid];
        }

        return $result;
    }

    // Join-based approach: try to find a quiz table to infer course id.
    $sm = $resolveSchemaManager($connection);
    $tableNames = method_exists($sm, 'listTableNames') ? $sm->listTableNames() : [];
    $quizCandidates = [
        'c_quiz',
        'quiz',
        'c_quiz_test',
        'quiz_test',
    ];

    $quizTable = null;
    foreach ($quizCandidates as $candidate) {
        if (in_array($candidate, $tableNames, true)) {
            $quizTable = $candidate;
            break;
        }
    }

    if (empty($quizTable)) {
        return $result;
    }

    $quizCols = [];
    try {
        $cols = $sm->listTableColumns($quizTable);
        foreach ($cols as $colName => $col) {
            $quizCols[] = $colName;
        }
    } catch (\Throwable $e) {
        $quizCols = [];
    }

    $quizPk = in_array('iid', $quizCols, true) ? 'iid' : (in_array('id', $quizCols, true) ? 'id' : 'iid');
    $quizCourseCol = in_array('c_id', $quizCols, true) ? 'c_id' : (in_array('course_id', $quizCols, true) ? 'course_id' : null);

    if (empty($quizCourseCol)) {
        return $result;
    }

    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
    $sql = "SELECT DISTINCT r.$qCol AS qid, q.$quizCourseCol AS cid, r.$eCol AS eid
            FROM $relTable r
            INNER JOIN $quizTable q ON q.$quizPk = r.$eCol
            WHERE r.$qCol IN ($placeholders)";

    $rows = $fetchAllAssociative($connection, $sql, $questionIds);
    foreach ($rows as $row) {
        $qid = (int) ($row['qid'] ?? 0);
        $cid = (int) ($row['cid'] ?? 0);
        $eid = (int) ($row['eid'] ?? 0);
        if ($qid <= 0 || $cid <= 0 || $eid <= 0) {
            continue;
        }
        $result[$qid][] = ['course_id' => $cid, 'exercise_id' => $eid];
    }

    return $result;
};

/**
 * Extract "filterable" Question ExtraFields values from form values.
 * Mirrors question_pool.php behavior (checkbox/double_select handling).
 *
 * Returns:
 *  - hasFilters: bool (true if at least one extra field filter is set)
 *  - filters: array of ['variable' => string, 'value' => string]
 */
$extractQuestionExtraFieldFilters = static function (array $formValues): array {
    $extraField = new ExtraField('question');

    $fields = $extraField->get_all(
        ['visible_to_self = ? AND filter = ?' => [1, 1]],
        'display_text'
    );

    $filters = [];

    foreach ($fields as $field) {
        $variable = (string) ($field['variable'] ?? '');
        if ($variable === '') {
            continue;
        }

        $key = "extra_$variable";
        if (!array_key_exists($key, $formValues)) {
            continue;
        }

        $value = $formValues[$key];
        switch ((int) ($field['value_type'] ?? 0)) {
            case ExtraField::FIELD_TYPE_CHECKBOX:
                // Some forms wrap checkbox in nested array
                if (is_array($value) && isset($value[$key])) {
                    $value = $value[$key];
                }
                break;

            case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                if (!is_array($value) || !isset($value["extra_{$variable}_second"])) {
                    $value = null;
                    break;
                }
                $first = $value[$key] ?? null;
                $second = $value["extra_{$variable}_second"] ?? null;
                if (empty($first) || empty($second)) {
                    $value = null;
                    break;
                }
                $value = $first.'::'.$second;
                break;

            default:
                break;
        }

        if (is_array($value)) {
            // Avoid unexpected arrays
            $value = null;
        }

        $value = (string) $value;
        if ($value === '' || $value === '0') {
            // Keep '0' if you want it as a valid filter; by default treat it as empty.
            // If your extrafields use '0' meaningfully, remove this condition.
            if ($value === '0') {
                // Treat as empty by default.
                continue;
            }
            continue;
        }

        $filters[] = [
            'variable' => $variable,
            'value' => $value,
        ];
    }

    return [
        'hasFilters' => count($filters) > 0,
        'filters' => $filters,
    ];
};

$getQuestionIdsByExtraFields = static function ($connection, array $formValues) use (
    $extractQuestionExtraFieldFilters,
    $fetchAllAssociative
): array {
    $extracted = $extractQuestionExtraFieldFilters($formValues);
    if (empty($extracted['hasFilters'])) {
        return ['hasFilters' => false, 'ids' => []];
    }

    $filters = $extracted['filters'] ?? [];
    if (empty($filters)) {
        return ['hasFilters' => false, 'ids' => []];
    }

    $orParts = [];
    $params = [
        'itemType' => (int) ExtraFieldEntity::QUESTION_FIELD_TYPE,
        'expected' => (int) count($filters),
    ];

    $i = 0;
    foreach ($filters as $f) {
        $i++;
        $orParts[] = "(ef.variable = :var{$i} AND efv.field_value = :val{$i})";
        $params["var{$i}"] = (string) $f['variable'];
        $params["val{$i}"] = (string) $f['value'];
    }

    $sql = "
        SELECT efv.item_id AS qid
        FROM extra_field_values efv
        INNER JOIN extra_field ef ON ef.id = efv.field_id
        WHERE ef.item_type = :itemType
          AND (".implode(' OR ', $orParts).")
        GROUP BY efv.item_id
        HAVING COUNT(DISTINCT ef.variable) = :expected
    ";

    $rows = $fetchAllAssociative($connection, $sql, $params);

    $ids = [];
    foreach ($rows as $row) {
        $qid = (int) ($row['qid'] ?? 0);
        if ($qid > 0) {
            $ids[] = $qid;
        }
    }

    return ['hasFilters' => true, 'ids' => array_values(array_unique($ids))];
};

// -----------------------------
// Prepare lists for form
// -----------------------------

// Courses list
$selectedCourse = isset($_GET['selected_course']) ? (int) $_GET['selected_course'] : -1;
$courseList = CourseManager::get_courses_list(0, 0, 'title');
$courseSelectionList = ['-1' => get_lang('All')];

foreach ($courseList as $item) {
    $course = api_get_course_entity($item['real_id']);
    if (!$course) {
        continue;
    }

    $courseSelectionList[$course->getId()] = '';

    if ($course->getId() == api_get_course_int_id()) {
        $courseSelectionList[$course->getId()] = '>&nbsp;&nbsp;&nbsp;&nbsp;';
    }

    $courseSelectionList[$course->getId()] .= $course->getTitle();
}

// Difficulty list (only from 0 to 5)
$questionLevel = isset($_REQUEST['question_level']) ? (int) $_REQUEST['question_level'] : -1;
$levels = [
    -1 => get_lang('All'),
    0 => 0,
    1 => 1,
    2 => 2,
    3 => 3,
    4 => 4,
    5 => 5,
];

// Answer type
$answerType = isset($_REQUEST['answer_type']) ? (int) $_REQUEST['answer_type'] : -1;
$questionList = Question::getQuestionTypeList();
$questionTypesList = ['-1' => get_lang('All')];

foreach ($questionList as $key => $item) {
    $instance = Question::getInstance($key);
    if ($instance instanceof Question) {
        $label = $instance->get_question_type_name();
    } else {
        if (is_array($item)) {
            $raw = $item[2] ?? $item[1] ?? reset($item);
        } else {
            $raw = (string) $item;
        }

        $translated = get_lang($raw);
        if ($translated !== $raw) {
            $label = $translated;
        } else {
            $label = preg_replace('/(?<!^)(?=[A-Z])/', ' ', $raw) ?: $raw;
        }
    }

    $questionTypesList[$key] = $label;
}

$form = new FormValidator('admin_questions', 'get');
$form->addHeader(get_lang('Questions'));
$form->addText('id', get_lang('Id'), false);
$form->addText('title', get_lang('Title'), false);
$form->addText('description', get_lang('Description'), false);

$form
    ->addSelect(
        'selected_course',
        [get_lang('Course'), get_lang('Course in which the question was initially created.')],
        $courseSelectionList,
        ['id' => 'selected_course']
    )
    ->setSelected($selectedCourse);

$form
    ->addSelect(
        'question_level',
        get_lang('Difficulty'),
        $levels,
        ['id' => 'question_level']
    )
    ->setSelected($questionLevel);

$form
    ->addSelect(
        'answer_type',
        get_lang('Answer type'),
        $questionTypesList,
        ['id' => 'answer_type']
    )
    ->setSelected($answerType);

// Extra fields filters (Question type)
$extraField = new ExtraField('question');
$jsForExtraFields = $extraField->addElements($form, 0, [], true);
$form->addHidden('form_sent', 1);
$form->addHidden('course_id_changed', '0');
$form->addButtonSearch(get_lang('Search'));

// -----------------------------
// Search + pagination
// -----------------------------

$questions = [];
$pagination = '';
$formSent = isset($_REQUEST['form_sent']) ? (int) $_REQUEST['form_sent'] : 0;
$length = 20;
$questionCount = 0;
$start = 0;
$end = 0;
$pdfContent = '';

$params = [
    'id' => $id ?: '',
    'title' => Security::remove_XSS($title),
    'description' => Security::remove_XSS($description),
    'selected_course' => $selectedCourse,
    'question_level' => $questionLevel,
    'answer_type' => $answerType,
];

$formValues = $form->validate() ? $form->exportValues() : [];
foreach ($formValues as $k => $v) {
    if (is_string($k) && str_starts_with($k, 'extra_')) {
        $params[$k] = $v;
    }
}

if ($formSent) {
    $params['form_sent'] = 1;

    $em = Database::getManager();
    $repo = $em->getRepository(CQuizQuestion::class);
    $criteria = new Criteria();

    // ---------------------------------------------------------
    // 1) Exact filters (AND)
    // ---------------------------------------------------------
    if (!empty($id)) {
        $criteria->andWhere($criteria->expr()->eq('iid', (int) $id));
    }

    if ($selectedCourse > 0) {
        /** @var \Chamilo\CoreBundle\Repository\ResourceNodeRepository $resourceNodeRepo */
        $resourceNodeRepo = $em->getRepository(ResourceNode::class);
        $resourceNodes = $resourceNodeRepo->findByResourceTypeAndCourse(
            'questions',
            api_get_course_entity($selectedCourse)
        );

        $criteria->andWhere(Criteria::expr()->in('resourceNode', $resourceNodes));
    }

    if (-1 !== $questionLevel) {
        $criteria->andWhere($criteria->expr()->eq('level', $questionLevel));
    }

    if (-1 !== $answerType) {
        $criteria->andWhere($criteria->expr()->eq('type', $answerType));
    }

    // Extra fields filters (AND across selected extra fields)
    $connectionForExtra = Database::getConnection();
    $extraFieldResult = $getQuestionIdsByExtraFields($connectionForExtra, $formValues);

    if (!empty($extraFieldResult['hasFilters'])) {
        $ids = $extraFieldResult['ids'] ?? [];

        if (empty($ids)) {
            // If extra field filters exist but no matches, force empty result fast.
            $criteria->andWhere($criteria->expr()->eq('iid', 0));
        } else {
            $criteria->andWhere($criteria->expr()->in('iid', $ids));
        }
    }

    // Text filters (OR grouped) and then ANDed with the rest
    $searchExpressions = [];

    if (!empty($description)) {
        $searchExpressions[] = $criteria->expr()->contains('description', $description."\r");
        $searchExpressions[] = $criteria->expr()->eq('description', $description);
        $searchExpressions[] = $criteria->expr()->eq('description', '<p>'.$description.'</p>');
    }

    if (!empty($title)) {
        $searchExpressions[] = $criteria->expr()->contains('question', $title);
    }

    if (!empty($searchExpressions)) {
        $expr = $criteria->expr();

        // Prefer orX() when available (Doctrine Collections)
        if (method_exists($expr, 'orX')) {
            $criteria->andWhere($expr->orX(...$searchExpressions));
        } else {
            // Fallback: mimic OR without crashing older versions
            $first = array_shift($searchExpressions);
            if ($first) {
                $criteria->andWhere($first);
                foreach ($searchExpressions as $e) {
                    $criteria->orWhere($e);
                }
            }
        }
    }

    $questions = $repo->matching($criteria);

    $url = api_get_self().'?'.http_build_query($params);
    $form->setDefaults($params);

    $questionCount = count($questions);

    if ('export_pdf' === $action) {
        $length = $questionCount;
        $page = 1;
    }

    $dispatcher = new EventDispatcher();
    $dispatcher->addSubscriber(new PaginationSubscriber());
    $dispatcher->addSubscriber(new SortableSubscriber());

    $paginator = new Paginator($dispatcher);
    $pagination = $paginator->paginate($questions, $page, $length);
    $pagination->setItemNumberPerPage($length);
    $pagination->setCurrentPageNumber($page);

    $pagination->renderer = function ($data) use ($url) {
        $pageCount = (int) ($data['pageCount'] ?? 1);
        $current = (int) ($data['current'] ?? 1);

        if ($pageCount <= 1) {
            return '';
        }

        $mk = static function (int $p, ?string $label = null, bool $disabled = false, bool $active = false) use ($url): string {
            $label = $label ?? (string) $p;

            $base = 'inline-flex items-center justify-center min-w-[36px] h-9 px-3 rounded-lg border text-sm font-semibold';
            if ($active) {
                $cls = $base.' bg-primary text-white border-primary cursor-default';
                $href = '#';
            } elseif ($disabled) {
                $cls = $base.' bg-white text-gray-50 border-gray-25 opacity-60 cursor-not-allowed';
                $href = '#';
            } else {
                $cls = $base.' bg-white text-gray-90 border-gray-25 hover:bg-gray-10';
                $href = $url.'&page='.$p;
            }

            return '<li class="list-none">'
                .'<a href="'.$href.'" class="'.$cls.'">'.htmlspecialchars($label, ENT_QUOTES).'</a>'
                .'</li>';
        };

        $render = '<nav aria-label="Pagination" class="w-full">';
        $render .= '<ul class="flex flex-wrap gap-2 justify-center items-center p-0 my-4">';

        $render .= $mk(max(1, $current - 1), '«', $current === 1, false);

        $window = 2;
        $start = max(1, $current - $window);
        $end = min($pageCount, $current + $window);

        if ($start > 1) {
            $render .= $mk(1, '1', false, $current === 1);
            if ($start > 2) {
                $render .= '<li class="list-none px-2 text-gray-50">…</li>';
            }
        }

        for ($p = $start; $p <= $end; $p++) {
            $render .= $mk($p, (string) $p, false, $p === $current);
        }

        if ($end < $pageCount) {
            if ($end < $pageCount - 1) {
                $render .= '<li class="list-none px-2 text-gray-50">…</li>';
            }
            $render .= $mk($pageCount, (string) $pageCount, false, $current === $pageCount);
        }

        $render .= $mk(min($pageCount, $current + 1), '»', $current === $pageCount, false);

        $render .= '</ul></nav>';

        return $render;
    };

    $urlExercise = api_get_path(WEB_CODE_PATH).'exercise/admin.php?';
    $warningText = addslashes(api_htmlentities(get_lang('Please confirm your choice')));

    $items = $pagination->getItems();
    $itemsCount = is_array($items) ? count($items) : 0;

    // Display range (for info, not for indexing)
    if ($itemsCount > 0) {
        $start = (int) (($page - 1) * $length) + 1;
        $end = (int) (($page - 1) * $length) + $itemsCount;
    }

    // Build a global map of exercises where each question is used (across all courses)
    $questionIdsOnPage = [];
    if (is_array($items)) {
        foreach ($items as $q) {
            if ($q instanceof CQuizQuestion) {
                $questionIdsOnPage[] = (int) $q->getIid();
            }
        }
    }

    $connection = Database::getConnection();
    $exerciseRefsByQuestionId = $fetchExerciseRefsForQuestionIds($connection, $questionIdsOnPage);

    $courseInfoCache = [];
    $getCourseInfoCached = static function (int $courseId) use (&$courseInfoCache) {
        if ($courseId <= 0) {
            return [];
        }
        if (!array_key_exists($courseId, $courseInfoCache)) {
            $courseInfoCache[$courseId] = api_get_course_info_by_id($courseId) ?: [];
        }

        return $courseInfoCache[$courseId];
    };

    /** @var CQuizQuestion $question */
    if (is_array($items)) {
        foreach ($items as $question) {
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            $questionId = (int) $question->getIid();
            $exerciseRefs = $exerciseRefsByQuestionId[$questionId] ?? [];

            // ---------------------------------------------------------
            // Resolve course context safely (fixes getCourse() on null)
            // ---------------------------------------------------------
            $resolvedCourseId = 0;

            $firstResourceLink = null;
            try {
                $firstResourceLink = $question->getFirstResourceLink();
            } catch (\Throwable $e) {
                $firstResourceLink = null;
            }

            if ($firstResourceLink && method_exists($firstResourceLink, 'getCourse') && $firstResourceLink->getCourse()) {
                $resolvedCourseId = (int) $firstResourceLink->getCourse()->getId();
            }

            if ($resolvedCourseId <= 0 && $selectedCourse > 0) {
                $resolvedCourseId = $selectedCourse;
            }

            if ($resolvedCourseId <= 0 && !empty($exerciseRefs)) {
                $resolvedCourseId = (int) ($exerciseRefs[0]['course_id'] ?? 0);
            }

            $courseInfo = $getCourseInfoCached($resolvedCourseId);
            $courseCode = !empty($courseInfo['code']) ? $courseInfo['code'] : '-';

            // Metadata for the Twig template
            $question->courseCode = $courseCode;
            $question->typeLabel = $questionTypesList[$question->getType()] ?? (string) $question->getType();
            $question->exerciseRefs = $exerciseRefs;
            $question->resolvedCourseId = $resolvedCourseId;
            $question->typeIconHtml = build_question_type_icon_html((int) $question->getType());

            // ---------------------------------------------------------
            // Preview rendering
            // ---------------------------------------------------------
            $questionObject = null;

            if (!empty($courseInfo) && $resolvedCourseId > 0) {
                $exercise = new Exercise($resolvedCourseId);
                $exercise->course_id = $resolvedCourseId;
                $exercise->course = $courseInfo;

                $questionObject = Question::read($questionId, $courseInfo);
                $question->typeIconHtml = build_question_type_icon_html((int) $question->getType());
                // Pick an exercise for preview rendering when possible
                $previewExerciseId = 0;

                foreach ($exerciseRefs as $ref) {
                    if ((int) ($ref['course_id'] ?? 0) === $resolvedCourseId) {
                        $previewExerciseId = (int) ($ref['exercise_id'] ?? 0);
                        if ($previewExerciseId > 0) {
                            break;
                        }
                    }
                }

                if ($previewExerciseId > 0) {
                    $exercise->read($previewExerciseId);
                }

                ob_start();
                ExerciseLib::showQuestion(
                    $exercise,
                    $questionId,
                    false,
                    null,
                    null,
                    false,
                    true,
                    false,
                    true,
                    true
                );
                $question->questionData = (string) ob_get_clean();
            } else {
                $question->questionData = Display::return_message(
                    get_lang('This question has no course context. Preview is not available.'),
                    'warning'
                );
            }

            // PDF export: just append content and continue
            if ('export_pdf' === $action) {
                $pdfContent .= '<span style="color:#000; font-weight:bold; font-size:x-large;">#'.$questionId.'. '.$question->getQuestion().'</span><br />';
                $pdfContent .= '<span style="color:#444;">('.($questionTypesList[$question->getType()] ?? '-').') ['.get_lang('Source').': '.$courseCode.']</span><br />';
                $pdfContent .= $question->getDescription().'<br />';
                $pdfContent .= $question->questionData;

                continue;
            }

            // ---------------------------------------------------------
            // Delete URL only if we have a usable course context
            // ---------------------------------------------------------
            $deleteUrl = '';
            if ($resolvedCourseId > 0 && !empty($courseInfo)) {
                $deleteUrl = $url.'&'.http_build_query([
                        'courseId' => $resolvedCourseId,
                        'questionId' => $questionId,
                        'action' => 'delete',
                        $deleteTokenVar => $deleteToken,
                    ]);
            }

            // ---------------------------------------------------------
            // Exercises list: GLOBAL across courses (Tailwind-ish markup)
            // ---------------------------------------------------------
            if (!empty($exerciseRefs)) {
                $exerciseData = '<div class="mt-3 rounded-xl border border-gray-25 bg-white p-3">';
                $exerciseData .= '<p class="mb-2 text-body-2 font-semibold text-gray-90">'.get_lang('Tests').'</p>';
                $exerciseData .= '<div class="space-y-2">';

                foreach ($exerciseRefs as $ref) {
                    $refCourseId = (int) ($ref['course_id'] ?? 0);
                    $refExerciseId = (int) ($ref['exercise_id'] ?? 0);

                    if ($refCourseId <= 0 || $refExerciseId <= 0) {
                        continue;
                    }

                    $refCourseInfo = $getCourseInfoCached($refCourseId);
                    $refCourseCode = !empty($refCourseInfo['code']) ? $refCourseInfo['code'] : '-';

                    $ex = new Exercise($refCourseId);
                    $ex->course_id = $refCourseId;

                    $exerciseTitle = get_lang('Unknown test').' #'.$refExerciseId;
                    $sessionId = 0;

                    if ($ex->read($refExerciseId)) {
                        $exerciseTitle = !empty($ex->title) ? $ex->title : $exerciseTitle;
                        $sessionId = (int) ($ex->sessionId ?? 0);
                    }

                    $cid = (int) ($refCourseInfo['real_id'] ?? $refCourseId);

                    $editLink = $urlExercise
                        .api_get_cidreq_params($cid, $sessionId)
                        .'&'.http_build_query([
                            'exerciseId' => $refExerciseId,
                            'type' => $question->getType(),
                            'editQuestion' => $questionId,
                        ]);

                    $exerciseData .= '<div class="flex flex-wrap items-center gap-2">';
                    $exerciseData .= '<span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">['.htmlspecialchars($refCourseCode).']</span>';
                    $exerciseData .= '<span class="text-sm text-slate-900">'.htmlspecialchars($exerciseTitle).'</span>';
                    $exerciseData .= Display::url(
                        Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
                        $editLink,
                        [
                            'target' => '_blank',
                            'class' => 'ml-auto inline-flex items-center rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50',
                        ]
                    );
                    $exerciseData .= '</div>';
                }

                $exerciseData .= '</div></div>';

                $question->questionData .= '<br />'.$exerciseData;
            } else {
                // Fallback to legacy behavior if we have a readable Question object
                if ($questionObject && $questionObject->getCountExercise() > 0) {
                    $exerciseList = $questionObject->getExerciseListWhereQuestionExists();
                    if (!empty($exerciseList)) {
                        $question->questionData .= '<p><strong>'.get_lang('Tests').'</strong></p>';
                        foreach ($exerciseList as $exerciseEntity) {
                            // Avoid hard-calling methods that might not exist (e.g. getActive()).
                            $exerciseTitle = '';
                            $exerciseIid = 0;
                            $activeFlag = null; // Only used if we can reliably read a "deleted" marker (-1)

                            if (is_object($exerciseEntity)) {
                                if (method_exists($exerciseEntity, 'getTitle')) {
                                    $exerciseTitle = (string) $exerciseEntity->getTitle();
                                } elseif (method_exists($exerciseEntity, 'getName')) {
                                    $exerciseTitle = (string) $exerciseEntity->getName();
                                }

                                if (method_exists($exerciseEntity, 'getIid')) {
                                    $exerciseIid = (int) $exerciseEntity->getIid();
                                } elseif (method_exists($exerciseEntity, 'getId')) {
                                    $exerciseIid = (int) $exerciseEntity->getId();
                                }

                                if (method_exists($exerciseEntity, 'getActive')) {
                                    $activeFlag = (int) $exerciseEntity->getActive();
                                } elseif (method_exists($exerciseEntity, 'getStatus')) {
                                    $activeFlag = (int) $exerciseEntity->getStatus();
                                }
                            }

                            if ($exerciseTitle === '') {
                                $exerciseTitle = get_lang('Unknown test');
                                if ($exerciseIid > 0) {
                                    $exerciseTitle .= ' #'.$exerciseIid;
                                }
                            }

                            $question->questionData .= htmlspecialchars($exerciseTitle, ENT_QUOTES);

                            if (null !== $activeFlag && -1 === $activeFlag) {
                                $question->questionData .= ' - ('.get_lang('The test has been deleted');
                                if ($exerciseIid > 0) {
                                    $question->questionData .= ' #'.$exerciseIid;
                                }
                                $question->questionData .= ') ';
                            }

                            $question->questionData .= '<br />';
                        }
                    }
                } else {
                    $question->questionData .= '&nbsp;'.get_lang('Orphan question');
                }
            }

            // ---------------------------------------------------------
            // Main actions (edit/delete)
            // ---------------------------------------------------------
            if (!empty($courseInfo) && $resolvedCourseId > 0) {
                $cid = (int) ($courseInfo['real_id'] ?? $resolvedCourseId);

                $editQuestionUrl = $urlExercise
                    .api_get_cidreq_params($cid)
                    .'&'.http_build_query([
                        'exerciseId' => 0,
                        'type' => $question->getType(),
                        'editQuestion' => $questionId,
                    ]);

                $question->questionData .= '<div class="mt-3 flex flex-wrap justify-end gap-2">';
                $question->questionData .= Display::url(
                    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).' '.get_lang('Edit'),
                    $editQuestionUrl,
                    [
                        'target' => '_blank',
                        'class' => 'inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50',
                    ]
                );

                if (!empty($deleteUrl)) {
                    $question->questionData .= Display::url(
                        get_lang('Delete'),
                        $deleteUrl,
                        [
                            'class' => 'inline-flex items-center rounded-lg bg-danger px-3 py-1.5 text-sm font-semibold text-white hover:bg-danger/90',
                            'onclick' => 'javascript: if(!confirm(\''.$warningText.'\')) return false',
                        ]
                    );
                } else {
                    $question->questionData .= '<span class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-400" title="'.api_htmlentities(get_lang('Deletion is not allowed without a course context.')).'">'
                        .get_lang('Delete').'</span>';
                }

                $question->questionData .= '</div>';
            }
        }
    }
}

$formContent = $form->returnForm();
if (!empty($jsForExtraFields['jquery_ready_content'])) {
    $safeJs = (string) $jsForExtraFields['jquery_ready_content'];
    $formContent .= "\n".'<script>
(function () {
  function run() {
    try { '.$safeJs.' } catch (e) {}
  }
  if (window.jQuery) {
    window.jQuery(function(){ run(); });
  } else {
    document.addEventListener("DOMContentLoaded", function(){ run(); });
  }
})();
</script>'."\n";
}

switch ($action) {
    case 'export_pdf':
        $pdfContent = Security::remove_XSS($pdfContent);
        $pdfParams = [
            'filename' => 'questions-export-'.api_get_local_time(),
            'pdf_date' => api_get_local_time(),
            'orientation' => 'P',
        ];
        $pdf = new PDF('A4', $pdfParams['orientation'], $pdfParams);
        $pdf->html_to_pdf_with_template($pdfContent, false, false, true);
        exit;

    case 'delete':
        $deleteTokenPrefix = 'admin_questions_delete';

        // CSRF protection for delete action (GET-based link).
        if (!Security::check_token('get', null, $deleteTokenPrefix)) {
            api_not_allowed(true);
        }

        $questionId = isset($_REQUEST['questionId']) ? (int) $_REQUEST['questionId'] : 0;
        $courseId = isset($_REQUEST['courseId']) ? (int) $_REQUEST['courseId'] : 0;
        $courseInfo = $courseId > 0 ? api_get_course_info_by_id($courseId) : [];

        if (empty($courseInfo) || $questionId <= 0) {
            Display::addFlash(Display::return_message(get_lang('Delete failed: missing course context or invalid question id.'), 'warning'));
            Security::clear_token($deleteTokenPrefix);
            header('Location: '.$url);
            exit;
        }

        $em = Database::getManager();
        $conn = Database::getConnection();

        try {
            /** @var CQuizQuestion|null $questionEntity */
            $questionEntity = $em->getRepository(CQuizQuestion::class)->find($questionId);

            if (!$questionEntity) {
                Display::addFlash(Display::return_message(get_lang('Delete failed: question not found.'), 'warning'));
                Security::clear_token($deleteTokenPrefix);
                header('Location: '.$url);
                exit;
            }

            $conn->beginTransaction();

            // Unlink from quizzes and keep orders to reindex later
            $toReindex = [];
            foreach ($questionEntity->getRelQuizzes() as $rel) {
                $quizId = (int) $rel->getQuiz()->getIid();
                $order = (int) $rel->getQuestionOrder();

                if ($quizId > 0 && $order > 0) {
                    $toReindex[$quizId][] = $order;
                }

                $em->remove($rel);
            }
            $em->flush();

            // Reindex question_order per quiz to avoid gaps (legacy behavior)
            foreach ($toReindex as $quizId => $orders) {
                rsort($orders);

                foreach ($orders as $order) {
                    $conn->executeStatement(
                        'UPDATE c_quiz_rel_question
                         SET question_order = question_order - 1
                         WHERE quiz_id = :quizId AND question_order > :order',
                        ['quizId' => $quizId, 'order' => $order]
                    );
                }
            }

            // Remove answers/options if any (prevents FK blocks)
            foreach ($questionEntity->getAnswers() as $answer) {
                $em->remove($answer);
            }
            foreach ($questionEntity->getOptions() as $option) {
                $em->remove($option);
            }

            // Detach categories (join table rows)
            foreach ($questionEntity->getCategories() as $category) {
                $questionEntity->removeCategory($category);
            }

            // Reset parent_media_id references (legacy behavior)
            $conn->executeStatement(
                'UPDATE c_quiz_question SET parent_media_id = NULL WHERE parent_media_id = :qid',
                ['qid' => $questionId]
            );

            // Finally remove the question
            $em->remove($questionEntity);
            $em->flush();

            $conn->commit();

            Display::addFlash(Display::return_message(get_lang('Deleted').' #'.$questionId, 'confirmation'));
        } catch (\Throwable $e) {
            try {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
            } catch (\Throwable $rollbackError) {
                // Ignore rollback errors
            }

            error_log('Admin questions delete failed: '.$e->getMessage());

            $debug = 'test' === api_get_setting('server_type');
            $msg = $debug
                ? ('Delete failed: '.$e->getMessage())
                : get_lang('Delete failed: this question is still referenced by other resources.');

            Display::addFlash(Display::return_message($msg, 'warning'));
        }

        Security::clear_token($deleteTokenPrefix);
        header('Location: ' . $url);
        exit;
}

$backUrl = Container::getRouter()->generate('admin');
if ($user->hasRole('ROLE_QUESTION_MANAGER')) {
    $backUrl = Container::getRouter()->generate('index');
}

$actionsLeft = Display::url(
    Display::getMdiIcon('arrow-left-bold-box', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Administration')),
    $backUrl
);

$exportUrl = '/main/admin/questions.php?' . http_build_query(['action' => 'export_pdf', ...$params]);

$actionsRight = Display::url(
    Display::getMdiIcon('file-pdf-box', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Export to PDF')),
    $exportUrl
);

$toolbar = Display::toolbarAction(
    'toolbar-admin-questions',
    [$actionsLeft, $actionsRight]
);

$tpl = new Template(get_lang('Questions'));
$tpl->assign('form', $formContent);
$tpl->assign('toolbar', $toolbar);
$tpl->assign('pagination', $pagination);
$tpl->assign('pagination_html', is_object($pagination) ? (string) $pagination : (string) $pagination);
$tpl->assign('pagination_length', $length);
$tpl->assign('start', $start);
$tpl->assign('end', $end);
$tpl->assign('question_count', $questionCount);

$layout = $tpl->get_template('admin/questions.html.twig');
$tpl->display($layout);
