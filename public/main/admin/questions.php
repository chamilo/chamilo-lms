<?php
/* For licensing terms, see /license.txt */

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
$deleteTokenVar = $deleteTokenPrefix . '_sec_token';
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

    // ---------------------------------------------------------
    // 2) Text filters (OR grouped) and then ANDed with the rest
    // ---------------------------------------------------------
    $searchExpressions = [];

    if (!empty($description)) {
        $searchExpressions[] = $criteria->expr()->contains('description', $description . "\r");
        $searchExpressions[] = $criteria->expr()->eq('description', $description);
        $searchExpressions[] = $criteria->expr()->eq('description', '<p>' . $description . '</p>');
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

    $url = api_get_self() . '?' . http_build_query($params);
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

    // Render a pagination bar that looks decent even if the theme doesn't style .pagination.
    $pagination->renderer = function ($data) use ($url) {
        $pageCount = (int) ($data['pageCount'] ?? 1);
        $current = (int) ($data['current'] ?? 1);

        if ($pageCount <= 1) {
            return '';
        }

        $mk = static function (int $p, string $label = null, bool $active = false) use ($url): string {
            $label = $label ?? (string) $p;
            $href = $active ? '#' : ($url . '&page=' . $p);
            $style = $active
                ? 'background:#0f172a;color:#fff;border-color:#0f172a;'
                : 'background:#fff;color:#0f172a;border-color:#e5e7eb;';
            $cursor = $active ? 'default' : 'pointer';

            return '<li style="margin:0;list-style:none;">'
                . '<a href="' . $href . '" style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:34px;padding:0 10px;border:1px solid;border-radius:10px;text-decoration:none;font-weight:600;'
                . $style . 'cursor:' . $cursor . ';">'
                . htmlspecialchars($label, ENT_QUOTES) . '</a></li>';
        };

        $render = '<nav aria-label="Pagination" style="width:100%;">';
        $render .= '<ul style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center;align-items:center;padding:0;margin:10px 0;">';

        // Prev
        $prev = max(1, $current - 1);
        $render .= $mk($prev, '«', $current === 1);

        // Windowed pages
        $window = 2;
        $start = max(1, $current - $window);
        $end = min($pageCount, $current + $window);

        if ($start > 1) {
            $render .= $mk(1, '1', $current === 1);
            if ($start > 2) {
                $render .= '<li style="list-style:none;padding:0 6px;color:#64748b;">…</li>';
            }
        }

        for ($p = $start; $p <= $end; $p++) {
            $render .= $mk($p, (string) $p, $p === $current);
        }

        if ($end < $pageCount) {
            if ($end < $pageCount - 1) {
                $render .= '<li style="list-style:none;padding:0 6px;color:#64748b;">…</li>';
            }
            $render .= $mk($pageCount, (string) $pageCount, $current === $pageCount);
        }

        // Next
        $next = min($pageCount, $current + 1);
        $render .= $mk($next, '»', $current === $pageCount);

        $render .= '</ul></nav>';

        return $render;
    };

    $urlExercise = api_get_path(WEB_CODE_PATH) . 'exercise/admin.php?';
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

            // ---------------------------------------------------------
            // Preview rendering
            // ---------------------------------------------------------
            $questionObject = null;

            if (!empty($courseInfo) && $resolvedCourseId > 0) {
                $exercise = new Exercise($resolvedCourseId);
                $exercise->course_id = $resolvedCourseId;
                $exercise->course = $courseInfo;

                $questionObject = Question::read($questionId, $courseInfo);

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
                $pdfContent .= '<span style="color:#000; font-weight:bold; font-size:x-large;">#' . $questionId . '. ' . $question->getQuestion() . '</span><br />';
                $pdfContent .= '<span style="color:#444;">(' . ($questionTypesList[$question->getType()] ?? '-') . ') [' . get_lang('Source') . ': ' . $courseCode . ']</span><br />';
                $pdfContent .= $question->getDescription() . '<br />';
                $pdfContent .= $question->questionData;

                continue;
            }

            // ---------------------------------------------------------
            // Delete URL only if we have a usable course context
            // ---------------------------------------------------------
            $deleteUrl = '';
            if ($resolvedCourseId > 0 && !empty($courseInfo)) {
                $deleteUrl = $url . '&' . http_build_query([
                        'courseId' => $resolvedCourseId,
                        'questionId' => $questionId,
                        'action' => 'delete',
                        $deleteTokenVar => $deleteToken,
                    ]);
            }

            // ---------------------------------------------------------
            // Exercises list: GLOBAL across courses
            // ---------------------------------------------------------
            $exerciseData = '';
            if (!empty($exerciseRefs)) {
                $exerciseData .= '<div class="aq-exercises">';
                $exerciseData .= '<p><strong>' . get_lang('Tests') . '</strong></p>';

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

                    $exerciseTitle = get_lang('Unknown test') . ' #' . $refExerciseId;
                    $sessionId = 0;

                    if ($ex->read($refExerciseId)) {
                        $exerciseTitle = !empty($ex->title) ? $ex->title : $exerciseTitle;
                        $sessionId = (int) ($ex->sessionId ?? 0);
                    }

                    $cid = (int) ($refCourseInfo['real_id'] ?? $refCourseId);

                    $editLink = $urlExercise
                        . api_get_cidreq_params($cid, $sessionId)
                        . '&' . http_build_query([
                            'exerciseId' => $refExerciseId,
                            'type' => $question->getType(),
                            'editQuestion' => $questionId,
                        ]);

                    $exerciseData .= '<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:6px 0;">';
                    $exerciseData .= '<span style="display:inline-flex;align-items:center;gap:8px;">';
                    $exerciseData .= '<span style="font-weight:700;color:#0f172a;">[' . htmlspecialchars($refCourseCode) . ']</span>';
                    $exerciseData .= '<span>' . htmlspecialchars($exerciseTitle) . '</span>';
                    $exerciseData .= '</span>';
                    $exerciseData .= Display::url(
                        Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
                        $editLink,
                        ['target' => '_blank']
                    );
                    $exerciseData .= '</div>';
                }

                $exerciseData .= '</div>';

                $question->questionData .= '<br />' . $exerciseData;
            } else {
                // Fallback to legacy behavior if we have a readable Question object
                if ($questionObject && $questionObject->getCountExercise() > 0) {
                    $exerciseList = $questionObject->getExerciseListWhereQuestionExists();
                    if (!empty($exerciseList)) {
                        $question->questionData .= '<p><strong>' . get_lang('Tests') . '</strong></p>';
                        foreach ($exerciseList as $exerciseEntity) {
                            // Some installations return legacy Exercise-like objects, others return Doctrine entities (CQuiz).
                            // Avoid hard-calling methods that might not exist (e.g. getActive()).
                            $exerciseTitle = '';
                            $exerciseIid = 0;
                            $activeFlag = null; // Only used if we can reliably read a "deleted" marker (-1)

                            if (is_object($exerciseEntity)) {
                                // Title
                                if (method_exists($exerciseEntity, 'getTitle')) {
                                    $exerciseTitle = (string) $exerciseEntity->getTitle();
                                } elseif (method_exists($exerciseEntity, 'getName')) {
                                    $exerciseTitle = (string) $exerciseEntity->getName();
                                }

                                // ID (Chamilo often uses iid, but some entities use id)
                                if (method_exists($exerciseEntity, 'getIid')) {
                                    $exerciseIid = (int) $exerciseEntity->getIid();
                                } elseif (method_exists($exerciseEntity, 'getId')) {
                                    $exerciseIid = (int) $exerciseEntity->getId();
                                }

                                // Active/deleted flag (not always available on Doctrine entities)
                                if (method_exists($exerciseEntity, 'getActive')) {
                                    $activeFlag = (int) $exerciseEntity->getActive();
                                } elseif (method_exists($exerciseEntity, 'getStatus')) {
                                    $activeFlag = (int) $exerciseEntity->getStatus();
                                }
                            }

                            if ($exerciseTitle === '') {
                                $exerciseTitle = get_lang('Unknown test');
                                if ($exerciseIid > 0) {
                                    $exerciseTitle .= ' #' . $exerciseIid;
                                }
                            }

                            $question->questionData .= htmlspecialchars($exerciseTitle, ENT_QUOTES);

                            // Keep the old "deleted" UX only when we can reliably detect the legacy marker (-1)
                            if (null !== $activeFlag && -1 === $activeFlag) {
                                $question->questionData .= ' - (' . get_lang('The test has been deleted');
                                if ($exerciseIid > 0) {
                                    $question->questionData .= ' #' . $exerciseIid;
                                }
                                $question->questionData .= ') ';
                            }

                            $question->questionData .= '<br />';
                        }

                    }
                } else {
                    $question->questionData .= '&nbsp;' . get_lang('Orphan question');
                }
            }

            // ---------------------------------------------------------
            // Main actions (edit/delete)
            // ---------------------------------------------------------
            if (!empty($courseInfo) && $resolvedCourseId > 0) {
                $cid = (int) ($courseInfo['real_id'] ?? $resolvedCourseId);

                $editQuestionUrl = $urlExercise
                    . api_get_cidreq_params($cid)
                    . '&' . http_build_query([
                        'exerciseId' => 0,
                        'type' => $question->getType(),
                        'editQuestion' => $questionId,
                    ]);

                $question->questionData .= '<div style="margin-top:10px;display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">';
                $question->questionData .= Display::url(
                    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
                    $editQuestionUrl,
                    ['target' => '_blank', 'class' => 'btn btn--plain']
                );

                if (!empty($deleteUrl)) {
                    $question->questionData .= Display::url(
                        get_lang('Delete'),
                        $deleteUrl,
                        [
                            'class' => 'btn btn--danger',
                            'onclick' => 'javascript: if(!confirm(\'' . $warningText . '\')) return false',
                        ]
                    );
                } else {
                    $question->questionData .= '<span class="btn btn--disabled" title="' . api_htmlentities(get_lang('Delete is not available without a course context.')) . '">'
                        . get_lang('Delete') . '</span>';
                }

                $question->questionData .= '</div>';
            }
        }
    }
}

$formContent = $form->returnForm();

switch ($action) {
    case 'export_pdf':
        $pdfContent = Security::remove_XSS($pdfContent);
        $pdfParams = [
            'filename' => 'questions-export-' . api_get_local_time(),
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
            header('Location: ' . $url);
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
                header('Location: ' . $url);
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
                // If the same question is linked multiple times (shouldn't), reindex from highest order first
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

            Display::addFlash(Display::return_message(get_lang('Deleted') . ' #' . $questionId, 'confirmation'));
        } catch (\Throwable $e) {
            try {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
            } catch (\Throwable $rollbackError) {
                // Ignore rollback errors
            }

            error_log('Admin questions delete failed: ' . $e->getMessage());

            $debug = 'test' === api_get_setting('server_type');
            $msg = $debug
                ? ('Delete failed: ' . $e->getMessage())
                : get_lang('Delete failed: this question is still referenced by other data.');

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
