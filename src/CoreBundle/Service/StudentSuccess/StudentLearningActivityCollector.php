<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\StudentSuccess;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\State\Gradebook\GradebookScoreCalculator;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;


/**
 * Builds the local, teacher-only learning activity payload used by the
 * Student Success AI Coach. This service does not call an AI provider.
 *
 * Direct profile identifiers are intentionally omitted. Free-text learning
 * data is kept at this stage because the next privacy layer must sanitize it
 * before any external AI request is made.
 */
final readonly class StudentLearningActivityCollector
{
    private const int MAX_LP_VIEWS = 50;
    private const int MAX_LP_ITEM_VIEWS = 600;
    private const int MAX_EXERCISE_ATTEMPTS = 80;
    private const int MAX_QUESTION_ATTEMPTS = 800;
    private const int MAX_FORUM_POSTS = 150;
    private const int MAX_ASSIGNMENTS = 100;
    private const int MAX_SURVEY_ANSWERS = 400;
    private const int MAX_ATTENDANCE_ROWS = 500;
    private const int MAX_TEXT_LENGTH = 6000;

    public function __construct(
        private Connection $connection,
        private EntityManagerInterface $entityManager,
        private GradebookScoreCalculator $gradebookScoreCalculator,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function collect(Course $course, ?Session $session, User $student): array
    {
        $courseId = (int) $course->getId();
        $sessionId = (int) ($session?->getId() ?? 0);
        $userId = (int) $student->getId();

        $sections = [
            'learningPaths' => $this->safeSection(
                'learning_paths',
                fn (): array => $this->collectLearningPaths($courseId, $sessionId, $userId),
            ),
            'exercises' => $this->safeSection(
                'exercises',
                fn (): array => $this->collectExercises($courseId, $sessionId, $userId),
            ),
            'forums' => $this->safeSection(
                'forums',
                fn (): array => $this->collectForums($courseId, $sessionId, $userId),
            ),
            'assignments' => $this->safeSection(
                'assignments',
                fn (): array => $this->collectAssignments($courseId, $sessionId, $userId),
            ),
            'surveys' => $this->safeSection(
                'surveys',
                fn (): array => $this->collectSurveys($courseId, $sessionId, $userId),
            ),
            'attendance' => $this->safeSection(
                'attendance',
                fn (): array => $this->collectAttendance($courseId, $sessionId, $userId),
            ),
            'gradebook' => $this->safeSection(
                'gradebook',
                fn (): array => $this->collectGradebook($course, $session, $student),
            ),
            'chat' => [
                'available' => false,
                'items' => [],
                'reason' => 'Course chat history is stored as rendered HTML without a reliable author user ID per message. It is intentionally excluded until messages can be attributed without guessing from names.',
            ],
        ];

        return [
            'version' => 1,
            'course' => [
                'id' => $courseId,
                'title' => (string) $course->getTitle(),
            ],
            'context' => [
                'sessionId' => $sessionId,
            ],
            'student' => [
                'reference' => 'student',
                'directIdentifiersIncluded' => false,
            ],
            'privacy' => [
                'profileDataIncluded' => false,
                'nameIncluded' => false,
                'usernameIncluded' => false,
                'emailIncluded' => false,
                'ipAddressIncluded' => false,
                'assignmentContentIncluded' => false,
                'freeTextRequiresSanitizationBeforeAi' => true,
            ],
            'sections' => $sections,
            'stats' => $this->buildStats($sections),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectLearningPaths(int $courseId, int $sessionId, int $userId): array
    {
        $views = $this->connection->fetchAllAssociative(
            'SELECT lpv.iid AS view_id,
                    lp.iid AS lp_id,
                    lp.title AS lp_title,
                    lpv.view_count,
                    lpv.last_item,
                    lpv.progress,
                    lpv.completion_date
               FROM c_lp_view lpv
               INNER JOIN c_lp lp ON lp.iid = lpv.lp_id
              WHERE lpv.c_id = :courseId
                AND lpv.user_id = :userId
                AND COALESCE(lpv.session_id, 0) = :sessionId
              ORDER BY lpv.iid DESC
              LIMIT '.self::MAX_LP_VIEWS,
            [
                'courseId' => $courseId,
                'userId' => $userId,
                'sessionId' => $sessionId,
            ],
            [
                'courseId' => Types::INTEGER,
                'userId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
            ],
        );

        if ([] === $views) {
            return ['items' => []];
        }

        $viewIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['view_id'],
            $views,
        )));

        $itemRows = $this->connection->executeQuery(
            'SELECT lpiv.iid AS item_view_id,
                    lpiv.lp_view_id,
                    item.iid AS item_id,
                    item.title AS item_title,
                    item.item_type,
                    lpiv.view_count,
                    lpiv.total_time,
                    lpiv.score,
                    lpiv.max_score,
                    lpiv.progress,
                    lpiv.status
               FROM c_lp_item_view lpiv
               INNER JOIN c_lp_item item ON item.iid = lpiv.lp_item_id
              WHERE lpiv.lp_view_id IN (:viewIds)
              ORDER BY lpiv.lp_view_id ASC, item.display_order ASC, lpiv.iid ASC
              LIMIT '.self::MAX_LP_ITEM_VIEWS,
            ['viewIds' => $viewIds],
            ['viewIds' => ArrayParameterType::INTEGER],
        )->fetchAllAssociative();

        $itemsByView = [];
        foreach ($itemRows as $row) {
            $itemsByView[(int) $row['lp_view_id']][] = [
                'itemViewId' => (int) $row['item_view_id'],
                'itemId' => (int) $row['item_id'],
                'title' => $this->cleanText((string) ($row['item_title'] ?? '')),
                'type' => (string) ($row['item_type'] ?? ''),
                'viewCount' => (int) ($row['view_count'] ?? 0),
                'timeSeconds' => (int) ($row['total_time'] ?? 0),
                'score' => null !== $row['score'] ? (float) $row['score'] : null,
                'maxScore' => $this->nullableFloat($row['max_score'] ?? null),
                'progress' => $this->nullableFloat($row['progress'] ?? null),
                'status' => (string) ($row['status'] ?? ''),
            ];
        }

        $items = [];
        foreach ($views as $row) {
            $viewId = (int) $row['view_id'];
            $items[] = [
                'viewId' => $viewId,
                'learningPathId' => (int) $row['lp_id'],
                'title' => $this->cleanText((string) ($row['lp_title'] ?? '')),
                'viewCount' => (int) ($row['view_count'] ?? 0),
                'lastItemId' => (int) ($row['last_item'] ?? 0),
                'progress' => null !== $row['progress'] ? (int) $row['progress'] : null,
                'completionDate' => null !== $row['completion_date'] ? (string) $row['completion_date'] : null,
                'items' => $itemsByView[$viewId] ?? [],
            ];
        }

        return ['items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectExercises(int $courseId, int $sessionId, int $userId): array
    {
        $attempts = $this->connection->fetchAllAssociative(
            'SELECT exercise.exe_id,
                    exercise.exe_exo_id AS quiz_id,
                    quiz.title AS quiz_title,
                    exercise.score,
                    exercise.max_score,
                    exercise.status,
                    exercise.start_date,
                    exercise.exe_date,
                    exercise.exe_duration,
                    exercise.orig_lp_id,
                    exercise.orig_lp_item_id
               FROM track_e_exercises exercise
               LEFT JOIN c_quiz quiz ON quiz.iid = exercise.exe_exo_id
              WHERE exercise.c_id = :courseId
                AND exercise.exe_user_id = :userId
                AND COALESCE(exercise.session_id, 0) = :sessionId
              ORDER BY exercise.start_date DESC, exercise.exe_id DESC
              LIMIT '.self::MAX_EXERCISE_ATTEMPTS,
            [
                'courseId' => $courseId,
                'userId' => $userId,
                'sessionId' => $sessionId,
            ],
            [
                'courseId' => Types::INTEGER,
                'userId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
            ],
        );

        if ([] === $attempts) {
            return ['items' => []];
        }

        $attemptIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['exe_id'],
            $attempts,
        )));

        $questionAttempts = $this->connection->executeQuery(
            'SELECT attempt.id,
                    attempt.exe_id,
                    attempt.question_id,
                    question.question AS question_title,
                    question.type AS question_type,
                    attempt.answer,
                    attempt.marks,
                    attempt.seconds_spent,
                    attempt.tms
               FROM track_e_attempt attempt
               LEFT JOIN c_quiz_question question ON question.iid = attempt.question_id
              WHERE attempt.exe_id IN (:attemptIds)
                AND attempt.user_id = :userId
              ORDER BY attempt.exe_id ASC, attempt.id ASC
              LIMIT '.self::MAX_QUESTION_ATTEMPTS,
            [
                'attemptIds' => $attemptIds,
                'userId' => $userId,
            ],
            [
                'attemptIds' => ArrayParameterType::INTEGER,
                'userId' => Types::INTEGER,
            ],
        )->fetchAllAssociative();

        $questionIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): int => (int) ($row['question_id'] ?? 0),
            $questionAttempts,
        ))));

        $optionsByQuestion = [];
        if ([] !== $questionIds) {
            $optionRows = $this->connection->executeQuery(
                'SELECT answer.iid,
                        answer.question_id,
                        answer.answer,
                        answer.correct,
                        answer.position
                   FROM c_quiz_answer answer
                  WHERE answer.question_id IN (:questionIds)
                  ORDER BY answer.question_id ASC, answer.position ASC, answer.iid ASC',
                ['questionIds' => $questionIds],
                ['questionIds' => ArrayParameterType::INTEGER],
            )->fetchAllAssociative();

            foreach ($optionRows as $row) {
                $optionsByQuestion[(int) $row['question_id']][] = [
                    'id' => (int) $row['iid'],
                    'title' => $this->cleanText((string) ($row['answer'] ?? '')),
                    'correct' => 1 === (int) ($row['correct'] ?? 0),
                ];
            }
        }

        $questionsByAttempt = [];
        foreach ($questionAttempts as $row) {
            $questionId = (int) ($row['question_id'] ?? 0);
            $questionsByAttempt[(int) $row['exe_id']][] = [
                'attemptAnswerId' => (int) $row['id'],
                'questionId' => $questionId,
                'title' => $this->cleanText((string) ($row['question_title'] ?? '')),
                'type' => null !== $row['question_type'] ? (int) $row['question_type'] : null,
                'studentAnswer' => $this->cleanText((string) ($row['answer'] ?? '')),
                'marks' => null !== $row['marks'] ? (float) $row['marks'] : null,
                'secondsSpent' => (int) ($row['seconds_spent'] ?? 0),
                'answeredAt' => null !== $row['tms'] ? (string) $row['tms'] : null,
                'possibleAnswers' => $optionsByQuestion[$questionId] ?? [],
            ];
        }

        $items = [];
        foreach ($attempts as $row) {
            $attemptId = (int) $row['exe_id'];
            $maxScore = (float) ($row['max_score'] ?? 0);
            $score = (float) ($row['score'] ?? 0);
            $items[] = [
                'attemptId' => $attemptId,
                'exerciseId' => (int) ($row['quiz_id'] ?? 0),
                'title' => $this->cleanText((string) ($row['quiz_title'] ?? '')),
                'score' => $score,
                'maxScore' => $maxScore,
                'percentage' => $maxScore > 0.0 ? round(($score / $maxScore) * 100, 2) : null,
                'status' => (string) ($row['status'] ?? ''),
                'startedAt' => null !== $row['start_date'] ? (string) $row['start_date'] : null,
                'finishedAt' => null !== $row['exe_date'] ? (string) $row['exe_date'] : null,
                'durationSeconds' => (int) ($row['exe_duration'] ?? 0),
                'learningPathId' => (int) ($row['orig_lp_id'] ?? 0),
                'learningPathItemId' => (int) ($row['orig_lp_item_id'] ?? 0),
                'questions' => $questionsByAttempt[$attemptId] ?? [],
            ];
        }

        return ['items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectForums(int $courseId, int $sessionId, int $userId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT post.iid AS post_id,
                    post.title AS post_title,
                    post.post_text,
                    post.post_date,
                    thread.iid AS thread_id,
                    thread.title AS thread_title,
                    thread.thread_qualify_max,
                    forum.iid AS forum_id,
                    forum.title AS forum_title,
                    category.iid AS category_id,
                    category.title AS category_title
               FROM c_forum_post post
               INNER JOIN c_forum_thread thread ON thread.iid = post.thread_id
               INNER JOIN c_forum_forum forum ON forum.iid = post.forum_id
               LEFT JOIN c_forum_category category ON category.iid = forum.forum_category
               INNER JOIN resource_link post_link ON post_link.resource_node_id = post.resource_node_id
              WHERE post.poster_id = :userId
                AND post_link.c_id = :courseId
                AND COALESCE(post_link.session_id, 0) = :sessionId
              ORDER BY post.post_date DESC, post.iid DESC
              LIMIT '.self::MAX_FORUM_POSTS,
            [
                'courseId' => $courseId,
                'sessionId' => $sessionId,
                'userId' => $userId,
            ],
            [
                'courseId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
                'userId' => Types::INTEGER,
            ],
        );

        if ([] === $rows) {
            return ['items' => []];
        }

        $threadIds = array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['thread_id'],
            $rows,
        )));
        $grades = [];
        if ([] !== $threadIds) {
            $gradeRows = $this->connection->executeQuery(
                'SELECT qualification.thread_id,
                        qualification.qualify,
                        qualification.qualify_time
                   FROM c_forum_thread_qualify qualification
                  WHERE qualification.user_id = :userId
                    AND qualification.c_id = :courseId
                    AND qualification.thread_id IN (:threadIds)
                  ORDER BY qualification.qualify_time DESC, qualification.iid DESC',
                [
                    'userId' => $userId,
                    'courseId' => $courseId,
                    'threadIds' => $threadIds,
                ],
                [
                    'userId' => Types::INTEGER,
                    'courseId' => Types::INTEGER,
                    'threadIds' => ArrayParameterType::INTEGER,
                ],
            )->fetchAllAssociative();

            foreach ($gradeRows as $gradeRow) {
                $threadId = (int) $gradeRow['thread_id'];
                if (isset($grades[$threadId])) {
                    continue;
                }
                $grades[$threadId] = [
                    'score' => (float) $gradeRow['qualify'],
                    'gradedAt' => null !== $gradeRow['qualify_time'] ? (string) $gradeRow['qualify_time'] : null,
                ];
            }
        }

        $items = [];
        foreach ($rows as $row) {
            $threadId = (int) $row['thread_id'];
            $threadGrade = $grades[$threadId] ?? null;
            $items[] = [
                'category' => [
                    'id' => null !== $row['category_id'] ? (int) $row['category_id'] : null,
                    'title' => $this->cleanText((string) ($row['category_title'] ?? '')),
                ],
                'forum' => [
                    'id' => (int) $row['forum_id'],
                    'title' => $this->cleanText((string) ($row['forum_title'] ?? '')),
                ],
                'thread' => [
                    'id' => $threadId,
                    'title' => $this->cleanText((string) ($row['thread_title'] ?? '')),
                    'maxScore' => null !== $row['thread_qualify_max'] ? (float) $row['thread_qualify_max'] : null,
                    'grade' => $threadGrade,
                ],
                'post' => [
                    'id' => (int) $row['post_id'],
                    'title' => $this->cleanText((string) ($row['post_title'] ?? '')),
                    'message' => $this->cleanText((string) ($row['post_text'] ?? '')),
                    'postedAt' => null !== $row['post_date'] ? (string) $row['post_date'] : null,
                ],
            ];
        }

        return ['items' => $items];
    }

    /**
     * Only assignment grades/metadata are collected. Submission text and files
     * are deliberately excluded because they may directly identify a learner.
     *
     * @return array<string, mixed>
     */
    private function collectAssignments(int $courseId, int $sessionId, int $userId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT assignment.iid AS assignment_id,
                    assignment.title AS assignment_title,
                    submission.iid AS submission_id,
                    submission.qualification,
                    submission.weight,
                    submission.accepted,
                    submission.sent_date,
                    submission.date_of_qualification
               FROM c_student_publication submission
               INNER JOIN c_student_publication assignment ON assignment.iid = submission.parent_id
               INNER JOIN resource_link submission_link ON submission_link.resource_node_id = submission.resource_node_id
              WHERE submission.user_id = :userId
                AND submission_link.c_id = :courseId
                AND COALESCE(submission_link.session_id, 0) = :sessionId
              ORDER BY submission.sent_date DESC, submission.iid DESC
              LIMIT '.self::MAX_ASSIGNMENTS,
            [
                'courseId' => $courseId,
                'sessionId' => $sessionId,
                'userId' => $userId,
            ],
            [
                'courseId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
                'userId' => Types::INTEGER,
            ],
        );

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'assignmentId' => (int) $row['assignment_id'],
                'title' => $this->cleanText((string) ($row['assignment_title'] ?? '')),
                'submissionId' => (int) $row['submission_id'],
                'grade' => null !== $row['qualification'] ? (float) $row['qualification'] : null,
                'maxScore' => null !== $row['weight'] ? (float) $row['weight'] : null,
                'accepted' => null !== $row['accepted'] ? (bool) $row['accepted'] : null,
                'submittedAt' => null !== $row['sent_date'] ? (string) $row['sent_date'] : null,
                'gradedAt' => null !== $row['date_of_qualification'] ? (string) $row['date_of_qualification'] : null,
                'submissionContentIncluded' => false,
            ];
        }

        return ['items' => $items];
    }

    /**
     * Anonymous survey answers are intentionally not re-identified.
     *
     * @return array<string, mixed>
     */
    private function collectSurveys(int $courseId, int $sessionId, int $userId): array
    {
        $resourceSessionSql = $sessionId > 0
            ? '(resource_link.session_id IS NULL OR resource_link.session_id = 0 OR resource_link.session_id = :sessionId)'
            : '(resource_link.session_id IS NULL OR resource_link.session_id = 0)';

        $params = [
            'courseId' => $courseId,
            'userKey' => (string) $userId,
            'sessionId' => $sessionId,
        ];

        $rows = $this->connection->fetchAllAssociative(
            'SELECT survey.iid AS survey_id,
                    survey.title AS survey_title,
                    survey.anonymous,
                    answer.iid AS answer_id,
                    question.iid AS question_id,
                    question.survey_question AS question_title,
                    question.type AS question_type,
                    answer.option_id,
                    answer.value
               FROM c_survey survey
               INNER JOIN resource_link resource_link ON resource_link.resource_node_id = survey.resource_node_id
               LEFT JOIN c_survey_answer answer
                      ON answer.survey_id = survey.iid
                     AND answer.user = :userKey
                     AND '.($sessionId > 0 ? 'answer.session_id = :sessionId' : 'answer.session_id IS NULL').'
               LEFT JOIN c_survey_question question ON question.iid = answer.question_id
              WHERE resource_link.c_id = :courseId
                AND '.$resourceSessionSql.'
              ORDER BY survey.iid ASC, question.sort ASC, answer.iid ASC
              LIMIT '.self::MAX_SURVEY_ANSWERS,
            $params,
            [
                'courseId' => Types::INTEGER,
                'userKey' => Types::STRING,
                'sessionId' => Types::INTEGER,
            ],
        );

        $surveyItems = [];
        $optionIds = [];
        foreach ($rows as $row) {
            $surveyId = (int) $row['survey_id'];
            $anonymous = '1' === (string) ($row['anonymous'] ?? '');
            $surveyItems[$surveyId] ??= [
                'surveyId' => $surveyId,
                'title' => $this->cleanText((string) ($row['survey_title'] ?? '')),
                'anonymous' => $anonymous,
                'responsesIncluded' => !$anonymous,
                'answers' => [],
            ];

            if ($anonymous || null === $row['answer_id']) {
                continue;
            }

            $rawOptionId = (string) ($row['option_id'] ?? '');
            $optionId = $rawOptionId;
            $otherText = '';
            if (str_contains($rawOptionId, '@:@')) {
                [$optionId, $otherText] = explode('@:@', $rawOptionId, 2);
            }
            if (ctype_digit($optionId) && (int) $optionId > 0) {
                $optionIds[(int) $optionId] = (int) $optionId;
            }

            $surveyItems[$surveyId]['answers'][] = [
                'answerId' => (int) $row['answer_id'],
                'questionId' => (int) ($row['question_id'] ?? 0),
                'question' => $this->cleanText((string) ($row['question_title'] ?? '')),
                'type' => (string) ($row['question_type'] ?? ''),
                'rawOptionId' => $optionId,
                'otherText' => $this->cleanText($otherText),
                'value' => (int) ($row['value'] ?? 0),
                'selectedOption' => null,
            ];
        }

        $optionMap = [];
        if ([] !== $optionIds) {
            $optionRows = $this->connection->executeQuery(
                'SELECT iid, option_text FROM c_survey_question_option WHERE iid IN (:optionIds)',
                ['optionIds' => array_values($optionIds)],
                ['optionIds' => ArrayParameterType::INTEGER],
            )->fetchAllAssociative();
            foreach ($optionRows as $optionRow) {
                $optionMap[(int) $optionRow['iid']] = $this->cleanText((string) ($optionRow['option_text'] ?? ''));
            }
        }

        foreach ($surveyItems as &$surveyItem) {
            foreach ($surveyItem['answers'] as &$answer) {
                $optionId = (string) $answer['rawOptionId'];
                if (ctype_digit($optionId) && isset($optionMap[(int) $optionId])) {
                    $answer['selectedOption'] = [
                        'id' => (int) $optionId,
                        'title' => $optionMap[(int) $optionId],
                    ];
                } elseif (\in_array((string) $answer['type'], ['open', 'comment'], true)) {
                    $answer['freeText'] = $this->cleanText($optionId);
                }
                unset($answer['rawOptionId']);
            }
            unset($answer);
        }
        unset($surveyItem);

        return ['items' => array_values($surveyItems)];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectAttendance(int $courseId, int $sessionId, int $userId): array
    {
        $resourceSessionSql = $sessionId > 0
            ? '(resource_link.session_id IS NULL OR resource_link.session_id = 0 OR resource_link.session_id = :sessionId)'
            : '(resource_link.session_id IS NULL OR resource_link.session_id = 0)';

        $rows = $this->connection->fetchAllAssociative(
            'SELECT attendance.iid AS attendance_id,
                    attendance.title AS attendance_title,
                    attendance.attendance_qualify_max,
                    calendar.iid AS calendar_id,
                    calendar.date_time,
                    calendar.done_attendance,
                    sheet.presence,
                    result.score AS aggregate_score
               FROM c_attendance attendance
               INNER JOIN resource_link resource_link ON resource_link.resource_node_id = attendance.resource_node_id
               LEFT JOIN c_attendance_calendar calendar ON calendar.attendance_id = attendance.iid
               LEFT JOIN c_attendance_sheet sheet
                      ON sheet.attendance_calendar_id = calendar.iid
                     AND sheet.user_id = :userId
               LEFT JOIN c_attendance_result result
                      ON result.attendance_id = attendance.iid
                     AND result.user_id = :userId
              WHERE resource_link.c_id = :courseId
                AND '.$resourceSessionSql.'
              ORDER BY attendance.iid ASC, calendar.date_time ASC, calendar.iid ASC
              LIMIT '.self::MAX_ATTENDANCE_ROWS,
            [
                'courseId' => $courseId,
                'sessionId' => $sessionId,
                'userId' => $userId,
            ],
            [
                'courseId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
                'userId' => Types::INTEGER,
            ],
        );

        $attendanceItems = [];
        foreach ($rows as $row) {
            $attendanceId = (int) $row['attendance_id'];
            $attendanceItems[$attendanceId] ??= [
                'attendanceId' => $attendanceId,
                'title' => $this->cleanText((string) ($row['attendance_title'] ?? '')),
                'maxScore' => null !== $row['attendance_qualify_max'] ? (int) $row['attendance_qualify_max'] : null,
                'aggregateScore' => null !== $row['aggregate_score'] ? (int) $row['aggregate_score'] : null,
                'records' => [],
            ];

            if (null === $row['calendar_id']) {
                continue;
            }

            $attendanceItems[$attendanceId]['records'][] = [
                'calendarId' => (int) $row['calendar_id'],
                'date' => null !== $row['date_time'] ? (string) $row['date_time'] : null,
                'closed' => (bool) ($row['done_attendance'] ?? false),
                'presenceCode' => null !== $row['presence'] ? (int) $row['presence'] : null,
            ];
        }

        return ['items' => array_values($attendanceItems)];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectGradebook(Course $course, ?Session $session, User $student): array
    {
        /** @var GradebookCategory[] $rootCategories */
        $rootCategories = $this->entityManager->getRepository(GradebookCategory::class)->findBy(
            [
                'course' => $course,
                'session' => $session,
                'parent' => null,
            ],
            ['id' => 'ASC'],
        );

        $categories = [];
        foreach ($rootCategories as $category) {
            if (!$category instanceof GradebookCategory) {
                continue;
            }
            $categories[] = $this->buildGradebookCategory($category, $student, $course, $session);
        }

        return ['items' => $categories];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGradebookCategory(
        GradebookCategory $category,
        User $student,
        Course $course,
        ?Session $session,
    ): array {
        $categoryResult = $this->gradebookScoreCalculator->calculateCategory(
            $category,
            $student,
            $course,
            $session,
            false,
        );

        $evaluations = [];
        foreach ($category->getEvaluations() as $evaluation) {
            if (!$evaluation instanceof GradebookEvaluation || 1 !== (int) $evaluation->getVisible()) {
                continue;
            }
            $result = $this->gradebookScoreCalculator->calculateEvaluation($evaluation, $student);
            $evaluations[] = [
                'id' => (int) $evaluation->getId(),
                'title' => $this->cleanText((string) $evaluation->getTitle()),
                'score' => $result['score'],
                'maxScore' => $result['maxScore'],
                'percentage' => $result['percentage'],
                'weight' => (float) $evaluation->getWeight(),
                'hasResult' => (bool) $result['hasResult'],
            ];
        }

        $links = [];
        foreach ($category->getLinks() as $link) {
            if (!$link instanceof GradebookLink || 1 !== (int) $link->getVisible()) {
                continue;
            }
            $result = $this->gradebookScoreCalculator->calculateLink($link, $student, $course, $session);
            $links[] = [
                'id' => (int) $link->getId(),
                'type' => (int) $link->getType(),
                'resourceId' => (int) $link->getRefId(),
                'score' => $result['score'],
                'maxScore' => $result['maxScore'],
                'percentage' => $result['percentage'],
                'weight' => (float) $link->getWeight(),
                'hasResult' => (bool) $result['hasResult'],
            ];
        }

        $subcategories = [];
        foreach ($category->getSubCategories() as $subcategory) {
            if (!$subcategory instanceof GradebookCategory || !$subcategory->getVisible()) {
                continue;
            }
            $subcategorySessionId = (int) ($subcategory->getSession()?->getId() ?? 0);
            $sessionId = (int) ($session?->getId() ?? 0);
            if ((int) $subcategory->getCourse()->getId() !== (int) $course->getId()
                || $subcategorySessionId !== $sessionId
            ) {
                continue;
            }
            $subcategories[] = $this->buildGradebookCategory($subcategory, $student, $course, $session);
        }

        return [
            'categoryId' => (int) $category->getId(),
            'title' => $this->cleanText((string) $category->getTitle()),
            'score' => $categoryResult['score'],
            'maxScore' => $categoryResult['maxScore'],
            'percentage' => $categoryResult['percentage'],
            'weight' => (float) $category->getWeight(),
            'hasResult' => (bool) $categoryResult['hasResult'],
            'evaluations' => $evaluations,
            'linkedItems' => $links,
            'subcategories' => $subcategories,
        ];
    }

    /**
     * @param callable(): array<string, mixed> $collector
     *
     * @return array<string, mixed>
     */
    private function safeSection(string $name, callable $collector): array
    {
        try {
            return ['available' => true, ...$collector()];
        } catch (Throwable $exception) {
            $this->logger->warning('Student Success activity source could not be collected.', [
                'source' => $name,
                'exception' => $exception,
            ]);

            return [
                'available' => false,
                'items' => [],
                'reason' => 'source_unavailable',
            ];
        }
    }

    /**
     * @param array<string, array<string, mixed>> $sections
     *
     * @return array<string, int>
     */
    private function buildStats(array $sections): array
    {
        $stats = [];
        foreach ($sections as $name => $section) {
            $items = $section['items'] ?? [];
            $stats[$name] = \is_array($items) ? \count($items) : 0;
        }

        return $stats;
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);

        return mb_substr($text, 0, self::MAX_TEXT_LENGTH);
    }

    private function nullableFloat(mixed $value): ?float
    {
        if (null === $value || '' === $value || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
