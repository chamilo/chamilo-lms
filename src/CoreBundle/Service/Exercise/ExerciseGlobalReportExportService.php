<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * CSV export for the migrated exercise global report.
 *
 * This intentionally mirrors public/main/exercise/exercise_global_report.php:
 * it is course-scoped, platform-admin only, and uses the legacy exercise
 * scoring helper to keep the category totals aligned with existing reports.
 */
final readonly class ExerciseGlobalReportExportService
{
    public function __construct(
        private Connection $connection,
        private Security $security,
    ) {}

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->assertAccessAllowed();
        $this->requireLegacyExerciseClasses();

        $courseId = $this->resolveCourseId($request);
        if (0 >= $courseId) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->getCourse($courseId);
        if (null === $course) {
            throw new NotFoundHttpException('The requested course could not be found.');
        }

        $rows = $this->buildRows($courseId, (string) $course['code']);
        $filename = $this->safeFilename('exercise_global_report_'.$course['code']).'.csv';

        $response = new StreamedResponse(static function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if (!\is_resource($handle)) {
                return;
            }

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );

        return $response;
    }

    private function assertAccessAllowed(): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (\function_exists('api_is_platform_admin') && api_is_platform_admin()) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to export the exercise global report.');
    }

    private function resolveCourseId(Request $request): int
    {
        $courseId = $request->query->getInt('cid', $request->query->getInt('courseId'));

        if (0 >= $courseId && \function_exists('api_get_course_int_id')) {
            $courseId = (int) api_get_course_int_id();
        }

        return $courseId;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCourse(int $courseId): ?array
    {
        $course = $this->connection->executeQuery(
            'SELECT id, code, title FROM course WHERE id = :courseId',
            ['courseId' => $courseId],
            ['courseId' => ParameterType::INTEGER]
        )->fetchAssociative();

        return \is_array($course) ? $course : null;
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    private function buildRows(int $courseId, string $courseCode): array
    {
        $students = $this->getStudents($courseCode);
        $categories = $this->getCategories($courseId);
        $exercises = $this->getExercises($courseId);

        $rows = [];
        $header = [
            $this->trans('Username'),
            $this->trans('First name'),
            $this->trans('Last name'),
            $this->trans('E-mail'),
            $this->trans('Official code'),
        ];

        foreach ($categories as $category) {
            $title = (string) ($category['title'] ?? '');
            $header[] = 'Aciertos: '.$title;
            $header[] = 'Errores: '.$title;
            $header[] = 'Omisiones: '.$title;
            $header[] = 'Puntos: '.$title;
        }

        foreach ($exercises as $exercise) {
            $header[] = (string) ($exercise['title'] ?? '');
        }

        $rows[] = $header;

        foreach ($students as $student) {
            if (!\is_array($student)) {
                continue;
            }

            $studentId = (int) ($student['user_id'] ?? $student['id'] ?? 0);
            if (0 >= $studentId) {
                continue;
            }

            $row = [
                (string) ($student['username'] ?? ''),
                (string) ($student['firstname'] ?? ''),
                (string) ($student['lastname'] ?? ''),
                (string) ($student['email'] ?? ''),
                (string) ($student['official_code'] ?? ''),
            ];

            $userExerciseData = [];
            $categoryData = [];

            foreach ($exercises as $exercise) {
                $exerciseId = (int) ($exercise['iid'] ?? 0);
                if (0 >= $exerciseId) {
                    continue;
                }

                $attempt = $this->getFirstCompletedAttempt($courseId, $studentId, $exerciseId);
                if (null === $attempt) {
                    $userExerciseData[$exerciseId] = null;
                    continue;
                }

                $stats = $this->getAttemptStats($courseId, $exerciseId, (int) $attempt['exe_id']);
                foreach ($categories as $category) {
                    $categoryId = (int) ($category['id'] ?? 0);
                    if (0 >= $categoryId) {
                        continue;
                    }

                    $categoryStats = $stats['category_list'][$categoryId] ?? null;
                    if (!\is_array($categoryStats)) {
                        continue;
                    }

                    if (!isset($categoryData[$categoryId])) {
                        $categoryData[$categoryId] = [
                            'passed' => 0,
                            'wrong' => 0,
                            'no_answer' => 0,
                            'score' => 0,
                        ];
                    }

                    $categoryData[$categoryId]['passed'] += (int) ($categoryStats['passed'] ?? 0);
                    $categoryData[$categoryId]['wrong'] += (int) ($categoryStats['wrong'] ?? 0);
                    $categoryData[$categoryId]['no_answer'] += (int) ($categoryStats['no_answer'] ?? 0);
                    $categoryData[$categoryId]['score'] += (float) ($categoryStats['score'] ?? 0);
                }

                $userExerciseData[$exerciseId] = $stats['total_score'] ?? null;
            }

            foreach ($categories as $category) {
                $categoryId = (int) ($category['id'] ?? 0);
                if (isset($categoryData[$categoryId])) {
                    $row[] = $categoryData[$categoryId]['passed'];
                    $row[] = $categoryData[$categoryId]['wrong'];
                    $row[] = $categoryData[$categoryId]['no_answer'];
                    $row[] = $this->formatNumber((float) $categoryData[$categoryId]['score']);
                } else {
                    $row[] = null;
                    $row[] = null;
                    $row[] = null;
                    $row[] = null;
                }
            }

            foreach ($exercises as $exercise) {
                $exerciseId = (int) ($exercise['iid'] ?? 0);
                $row[] = \array_key_exists($exerciseId, $userExerciseData) ? $userExerciseData[$exerciseId] : null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getStudents(string $courseCode): array
    {
        if (\class_exists('CourseManager')) {
            $students = \CourseManager::get_student_list_from_course_code($courseCode);

            return \is_array($students) ? $students : [];
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(int $courseId): array
    {
        return $this->connection->executeQuery(
            'SELECT id, title FROM c_quiz_category WHERE c_id = :courseId ORDER BY position ASC, id ASC',
            ['courseId' => $courseId],
            ['courseId' => ParameterType::INTEGER]
        )->fetchAllAssociative();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getExercises(int $courseId): array
    {
        $activeFilter = $this->tableHasColumn('c_quiz', 'active') ? 'AND q.active <> -1' : '';

        $sql = <<<SQL
SELECT DISTINCT q.iid, q.title
FROM c_quiz q
INNER JOIN resource_node rn
    ON rn.id = q.resource_node_id
INNER JOIN resource_link rl
    ON rl.resource_node_id = rn.id
WHERE rl.c_id = :courseId
    AND rl.deleted_at IS NULL
    {$activeFilter}
ORDER BY q.iid ASC
SQL;

        return $this->connection->executeQuery(
            $sql,
            ['courseId' => $courseId],
            ['courseId' => ParameterType::INTEGER]
        )->fetchAllAssociative();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getFirstCompletedAttempt(int $courseId, int $studentId, int $exerciseId): ?array
    {
        $row = $this->connection->executeQuery(
            "SELECT exe_id, data_tracking
             FROM track_e_exercises
             WHERE c_id = :courseId
               AND exe_user_id = :studentId
               AND exe_exo_id = :exerciseId
               AND status = ''
             ORDER BY exe_id ASC
             LIMIT 1",
            [
                'courseId' => $courseId,
                'studentId' => $studentId,
                'exerciseId' => $exerciseId,
            ],
            [
                'courseId' => ParameterType::INTEGER,
                'studentId' => ParameterType::INTEGER,
                'exerciseId' => ParameterType::INTEGER,
            ]
        )->fetchAssociative();

        return \is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getAttemptStats(int $courseId, int $exerciseId, int $attemptId): array
    {
        if (!\class_exists('Exercise') || !\class_exists('ExerciseLib')) {
            return ['category_list' => [], 'total_score' => null];
        }

        $exercise = new \Exercise($courseId);
        if (!$exercise->read($exerciseId)) {
            return ['category_list' => [], 'total_score' => null];
        }

        ob_start();
        try {
            $stats = \ExerciseLib::displayQuestionListByAttempt(
                $exercise,
                $attemptId,
                false,
                '',
                false,
                true,
                true
            );
        } finally {
            ob_end_clean();
        }

        return \is_array($stats) ? $stats : ['category_list' => [], 'total_score' => null];
    }

    private function requireLegacyExerciseClasses(): void
    {
        $legacyBase = \function_exists('api_get_path') && \defined('SYS_CODE_PATH')
            ? rtrim((string) api_get_path(SYS_CODE_PATH), '/').'/'
            : dirname(__DIR__, 4).'/public/main/';

        $files = [
            $legacyBase.'exercise/exercise.class.php',
            $legacyBase.'exercise/exercise/TestCategory.php',
            $legacyBase.'inc/lib/exercise.lib.php',
        ];

        foreach ($files as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }
    }

    private function tableHasColumn(string $table, string $column): bool
    {
        $rows = $this->connection
            ->executeQuery(sprintf('SHOW COLUMNS FROM %s LIKE :column', $table), ['column' => $column])
            ->fetchAllAssociative()
        ;

        return [] !== $rows;
    }

    private function trans(string $key): string
    {
        return \function_exists('get_lang') ? (string) get_lang($key) : $key;
    }

    private function formatNumber(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    private function safeFilename(string $filename): string
    {
        $filename = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $filename) ?: 'exercise_global_report';

        return trim($filename, '._') ?: 'exercise_global_report';
    }
}
