<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingContextResolver;
use Chamilo\CoreBundle\Service\GlobalReporting\GlobalReportingSectionQueryService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/api/global-reporting/export/{section}.{format}',
    name: 'global_reporting_export',
    requirements: [
        'section' => 'my-progress|learners|learner-detail|teachers|users|courses|sessions|admin-users|admin-courses|admin-sessions|admin-coaches|access-overview|exams|current-courses|certificates|company|company-summary|learning-results|session-results|exercise-categories|surveys|student-bosses|tutor-planning|question-stats|question-stats-detail|organization|learning-path-authors|learning-path-item-authors|works-in-session',
        'format' => 'csv|xlsx',
    ],
    methods: ['GET']
)]
final readonly class GlobalReportingExportController
{
    public function __construct(
        private GlobalReportingContextResolver $contextResolver,
        private GlobalReportingSectionQueryService $queryService,
    ) {}

    public function __invoke(Request $request, string $section, string $format): BinaryFileResponse|StreamedResponse
    {
        $data = $this->queryService->getSection(
            $this->contextResolver->resolve(),
            $section,
            $request->query->all(),
            true,
        );
        $meta = \is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $canExport = 'csv' === $format
            ? (bool) ($meta['canExportCsv'] ?? false)
            : (bool) ($meta['canExportXlsx'] ?? false);
        if (!$canExport) {
            throw new BadRequestHttpException('This report does not support the requested export format.');
        }
        if ('admin-courses' === $section) {
            $exportData = $this->createAdminCourseExportData($data);
        } elseif ('admin-users' === $section) {
            $exportData = $this->createAdminUserExportData($data);
        } elseif ('learner-detail' === $section && (int) $request->query->get('courseId', '0') > 0) {
            $exportData = $this->createLearnerCourseExportData($data);
        } else {
            $columns = array_values(array_filter(
                $data['columns'],
                static fn (array $column): bool => !\in_array(
                    (string) ($column['type'] ?? ''),
                    ['learner-detail', 'certificate', 'session-actions'],
                    true,
                ),
            ));
            $exportData = ['columns' => $columns, 'items' => $data['items']];
        }
        $filename = 'global-reporting-'.$section.'.'.$format;

        if ('csv' === $format) {
            return $this->createCsvResponse($exportData, $filename);
        }

        return $this->createXlsxResponse($exportData, $filename);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{columns: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>}
     */
    private function createAdminCourseExportData(array $data): array
    {
        return [
            'columns' => [
                ['key' => 'title', 'label' => 'Course', 'type' => 'text'],
                ['key' => 'timeSeconds', 'label' => 'Time', 'type' => 'duration'],
                ['key' => 'progress', 'label' => 'Progress', 'type' => 'percent'],
                [
                    'key' => 'averageLearningPathScore',
                    'label' => 'Average score in learning paths',
                    'type' => 'percent',
                ],
                ['key' => 'messages', 'label' => 'Total number of messages', 'type' => 'number'],
                ['key' => 'assignments', 'label' => 'Total number of assignments', 'type' => 'number'],
                ['key' => 'scoreObtained', 'label' => 'Total score obtained for tests', 'type' => 'number'],
                ['key' => 'scorePossible', 'label' => 'Total possible score for tests', 'type' => 'number'],
                ['key' => 'questionsAnswered', 'label' => 'Number of tests answered', 'type' => 'number'],
                [
                    'key' => 'scorePercentage',
                    'label' => 'Total score percentage for tests',
                    'type' => 'percent',
                ],
                ['key' => 'lastAccess', 'label' => 'Latest login', 'type' => 'datetime'],
            ],
            'items' => \is_array($data['items'] ?? null) ? $data['items'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{columns: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>}
     */
    private function createAdminUserExportData(array $data): array
    {
        $columns = [
            ['key' => 'fullName', 'label' => 'User', 'type' => 'text'],
            ['key' => 'username', 'label' => 'Username', 'type' => 'text'],
            ['key' => 'course', 'label' => 'Course', 'type' => 'text'],
            ['key' => 'timeSeconds', 'label' => 'Time spent in the course', 'type' => 'duration'],
            ['key' => 'progress', 'label' => 'Progress', 'type' => 'percent'],
            [
                'key' => 'averageLearningPathScore',
                'label' => 'Average score in learning paths',
                'type' => 'percent',
            ],
            ['key' => 'testScore', 'label' => 'Total score obtained for tests', 'type' => 'percent'],
            ['key' => 'messages', 'label' => 'Total number of messages', 'type' => 'number'],
            ['key' => 'assignments', 'label' => 'Total number of assignments', 'type' => 'number'],
            ['key' => 'testsAnswered', 'label' => 'Number of tests answered', 'type' => 'number'],
            ['key' => 'lastAccess', 'label' => 'Latest login', 'type' => 'datetime'],
        ];
        $items = [];
        foreach ($data['items'] ?? [] as $user) {
            if (!\is_array($user)) {
                continue;
            }
            $courses = \is_array($user['courses'] ?? null) ? $user['courses'] : [];
            if ([] === $courses) {
                $items[] = [
                    'fullName' => $user['fullName'] ?? '',
                    'username' => $user['username'] ?? '',
                    'course' => '',
                    'timeSeconds' => 0,
                    'progress' => 0,
                    'averageLearningPathScore' => null,
                    'testScore' => 0,
                    'messages' => 0,
                    'assignments' => 0,
                    'testsAnswered' => 0,
                    'lastAccess' => '',
                ];

                continue;
            }

            foreach ($courses as $course) {
                if (!\is_array($course)) {
                    continue;
                }
                $items[] = [
                    'fullName' => $user['fullName'] ?? '',
                    'username' => $user['username'] ?? '',
                    'course' => $course['title'] ?? '',
                    'timeSeconds' => $course['timeSeconds'] ?? 0,
                    'progress' => $course['progress'] ?? 0,
                    'averageLearningPathScore' => $course['averageLearningPathScore'] ?? null,
                    'testScore' => $course['testScore'] ?? 0,
                    'messages' => $course['messages'] ?? 0,
                    'assignments' => $course['assignments'] ?? 0,
                    'testsAnswered' => $course['testsAnswered'] ?? 0,
                    'lastAccess' => $course['lastAccess'] ?? '',
                ];
            }
        }

        return ['columns' => $columns, 'items' => $items];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{columns: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>}
     */
    private function createLearnerCourseExportData(array $data): array
    {
        $columns = [
            ['key' => 'section', 'label' => 'Section', 'type' => 'text'],
            ['key' => 'item', 'label' => 'Item', 'type' => 'text'],
            ['key' => 'field', 'label' => 'Field', 'type' => 'text'],
            ['key' => 'value', 'label' => 'Value', 'type' => 'text'],
        ];
        $items = [];
        $meta = \is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $user = \is_array($meta['user'] ?? null) ? $meta['user'] : [];
        $course = \is_array($meta['course'] ?? null) ? $meta['course'] : [];
        $tools = \is_array($course['tools'] ?? null) ? $course['tools'] : [];
        foreach ([
            'Learner' => [
                'Full name' => $user['fullName'] ?? '',
                'Username' => $user['username'] ?? '',
                'Official code' => $user['officialCode'] ?? '',
                'Status' => $user['status'] ?? '',
                'First login in platform' => $user['firstLogin'] ?? '',
                'Latest login in platform' => $user['lastLogin'] ?? '',
            ],
            'Course' => [
                'Title' => $course['title'] ?? '',
                'Time spent in the course' => $this->formatValue($course['timeSeconds'] ?? 0, 'duration'),
                'Progress' => $this->formatValue($course['progress'] ?? 0, 'percent'),
                'Score' => $this->formatValue($course['score'] ?? 0, 'percent'),
                'First access' => $course['firstAccess'] ?? '',
                'Latest access' => $course['lastAccess'] ?? '',
            ],
            'Course tools' => [
                'Links accessed' => $tools['links'] ?? 0,
                'Documents downloaded' => $tools['documents'] ?? 0,
                'Assignments' => $tools['assignments'] ?? 0,
                'Number of posts for this user' => $tools['forumPosts'] ?? 0,
                'Uploaded documents' => $tools['uploadedDocuments'] ?? 0,
                'Latest chat connection' => $tools['chatLastConnection'] ?? '',
            ],
        ] as $section => $values) {
            foreach ($values as $field => $value) {
                $items[] = [
                    'section' => $section,
                    'item' => '',
                    'field' => $field,
                    'value' => $value,
                ];
            }
        }

        foreach ($data['sections'] ?? [] as $section) {
            if (!\is_array($section)) {
                continue;
            }
            $sectionTitle = (string) ($section['title'] ?? '');
            $sectionColumns = \is_array($section['columns'] ?? null) ? $section['columns'] : [];
            foreach ($section['items'] ?? [] as $item) {
                if (!\is_array($item)) {
                    continue;
                }
                $itemTitle = (string) ($item['title'] ?? $item['name'] ?? $item['id'] ?? '');
                foreach ($sectionColumns as $column) {
                    if (!\is_array($column) || 'action' === ($column['type'] ?? '')) {
                        continue;
                    }
                    $key = (string) ($column['key'] ?? '');
                    if ('' === $key || 'title' === $key) {
                        continue;
                    }
                    $items[] = [
                        'section' => $sectionTitle,
                        'item' => $itemTitle,
                        'field' => (string) ($column['label'] ?? $key),
                        'value' => $this->formatValue($item[$key] ?? '', (string) ($column['type'] ?? '')),
                    ];
                }
            }
        }

        return ['columns' => $columns, 'items' => $items];
    }

    /**
     * @param array{columns: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>} $data
     */
    private function createCsvResponse(array $data, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($data): void {
            $handle = fopen('php://output', 'wb');
            if (!\is_resource($handle)) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_map(
                static fn (array $column): string => (string) ($column['label'] ?? $column['key'] ?? ''),
                $data['columns'],
            ));

            foreach ($data['items'] as $item) {
                fputcsv($handle, $this->formatRow($data['columns'], $item));
            }

            fclose($handle);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename),
        );
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * @param array{columns: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>} $data
     */
    private function createXlsxResponse(array $data, string $filename): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $columnIndex = 1;
        foreach ($data['columns'] as $column) {
            $sheet->setCellValue([$columnIndex, 1], (string) ($column['label'] ?? $column['key'] ?? ''));
            ++$columnIndex;
        }

        $rowIndex = 2;
        foreach ($data['items'] as $item) {
            $columnIndex = 1;
            foreach ($this->formatRow($data['columns'], $item) as $value) {
                $sheet->setCellValue([$columnIndex, $rowIndex], $value);
                ++$columnIndex;
            }
            ++$rowIndex;
        }

        foreach (range(1, max(1, \count($data['columns']))) as $index) {
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'global-reporting-');
        if (false === $temporaryFile) {
            throw new RuntimeException('Could not create the reporting export file.');
        }
        (new Xlsx($spreadsheet))->save($temporaryFile);
        $spreadsheet->disconnectWorksheets();

        $response = new BinaryFileResponse(new File($temporaryFile));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * @param array<int, array<string, mixed>> $columns
     * @param array<string, mixed>             $item
     *
     * @return array<int, int|float|string>
     */
    private function formatRow(array $columns, array $item): array
    {
        $row = [];
        foreach ($columns as $column) {
            $key = (string) ($column['key'] ?? '');
            $type = (string) ($column['type'] ?? '');
            $value = $this->formatValue($item[$key] ?? '', $type);
            if (!\is_int($value) && !\is_float($value)) {
                $value = $this->neutralizeSpreadsheetFormula((string) $value);
            }
            $row[] = $value;
        }

        return $row;
    }

    private function formatValue(mixed $value, string $type): float|int|string
    {
        if (null === $value) {
            return '';
        }
        if (\is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (\is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }
        if ('duration' === $type) {
            $seconds = max(0, (int) $value);

            return \sprintf(
                '%02d:%02d:%02d',
                intdiv($seconds, 3600),
                intdiv($seconds % 3600, 60),
                $seconds % 60,
            );
        }
        if ('percent' === $type) {
            return round((float) $value, 2).'%';
        }
        if (\is_int($value) || \is_float($value)) {
            return $value;
        }

        return strip_tags((string) $value);
    }

    private function neutralizeSpreadsheetFormula(string $value): string
    {
        if ('' !== $value && \in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
