<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContext;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContextResolver;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingSectionQueryService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/api/course-reporting/{section}.{format}',
    name: 'course_reporting_section_export',
    requirements: [
        'section' => 'activity|groups|resources|tools|exams|audit|learning-paths|total-time|session|messages',
        'format' => 'csv|xlsx',
    ],
    methods: ['GET']
)]
final readonly class CourseReportingSectionExportController
{
    public function __construct(
        private CourseReportingContextResolver $contextResolver,
        private CourseReportingSectionQueryService $queryService,
    ) {}

    public function __invoke(Request $request, string $section, string $format): BinaryFileResponse|StreamedResponse
    {
        $mode = (string) $request->query->get('mode', 'paths');
        if (!$this->isExportAllowed($section, $format, $mode)) {
            throw new BadRequestHttpException('This export format is not available for the selected report.');
        }

        $context = $this->contextResolver->resolve();
        $data = $this->loadExportData($context, $section, $request);
        $filename = $this->buildFilename($context, $section, $format);

        if ('csv' === $format) {
            return $this->createCsvResponse($data, $filename);
        }

        return $this->createXlsxResponse($data, $filename);
    }

    /**
     * @return array{columns: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>}
     */
    private function loadExportData(
        CourseReportingContext $context,
        string $section,
        Request $request
    ): array {
        $filters = $request->query->all();
        $filters['page'] = 1;
        $filters['itemsPerPage'] = 200;
        $firstPage = $this->queryService->getSection($context, $section, $filters);
        $columns = array_values(array_filter(
            $firstPage['columns'],
            static fn (array $column): bool => !\in_array(
                (string) ($column['type'] ?? ''),
                ['group-detail', 'learner-detail'],
                true
            )
        ));
        $items = $firstPage['items'];
        $total = (int) $firstPage['total'];
        $pageCount = min(50, (int) ceil($total / 200));

        for ($page = 2; $page <= $pageCount; ++$page) {
            $filters['page'] = $page;
            $pageData = $this->queryService->getSection($context, $section, $filters);
            $items = array_merge($items, $pageData['items']);
        }

        return [
            'columns' => $columns,
            'items' => $items,
        ];
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
                $data['columns']
            ));

            foreach ($data['items'] as $item) {
                fputcsv($handle, $this->formatRow($data['columns'], $item));
            }

            fclose($handle);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
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
        $sheet->setTitle('Course reporting');
        $headers = array_map(
            static fn (array $column): string => (string) ($column['label'] ?? $column['key'] ?? ''),
            $data['columns']
        );
        $sheet->fromArray($headers, null, 'A1');

        $rowNumber = 2;
        foreach ($data['items'] as $item) {
            $sheet->fromArray($this->formatRow($data['columns'], $item), null, 'A'.$rowNumber);
            ++$rowNumber;
        }

        for ($column = 1; $column <= \count($headers); ++$column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'course-reporting-');
        if (false === $temporaryFile) {
            throw new BadRequestHttpException('The export file could not be created.');
        }

        $xlsxPath = $temporaryFile.'.xlsx';
        rename($temporaryFile, $xlsxPath);
        (new Xlsx($spreadsheet))->save($xlsxPath);
        $spreadsheet->disconnectWorksheets();

        $response = new BinaryFileResponse(new File($xlsxPath));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);

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
            $value = $this->formatExportValue($item[$key] ?? '', $type);
            if (!\is_int($value) && !\is_float($value)) {
                $value = $this->neutralizeSpreadsheetFormula((string) $value);
            }
            $row[] = $value;
        }

        return $row;
    }

    private function formatExportValue(mixed $value, string $type): float|int|string
    {
        if (\is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }
        if (null === $value) {
            return '';
        }
        if (\is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if ('duration' === $type) {
            $seconds = max(0, (int) $value);
            $hours = (int) floor($seconds / 3600);
            $minutes = (int) floor(($seconds % 3600) / 60);
            $remainingSeconds = $seconds % 60;

            return \sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }
        if ('percent' === $type) {
            return round((float) $value, 2).'%';
        }
        if (\is_int($value) || \is_float($value)) {
            return $value;
        }

        return (string) $value;
    }

    private function neutralizeSpreadsheetFormula(string $value): string
    {
        if ('' !== $value && \in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function isExportAllowed(string $section, string $format, string $mode): bool
    {
        return match ($section) {
            'resources' => \in_array($format, ['csv', 'xlsx'], true),
            'tools', 'total-time' => 'csv' === $format,
            'exams' => 'xlsx' === $format,
            'learning-paths' => match ($mode) {
                'paths' => 'xlsx' === $format,
                'users' => \in_array($format, ['csv', 'xlsx'], true),
                default => false,
            },
            default => false,
        };
    }

    private function buildFilename(
        CourseReportingContext $context,
        string $section,
        string $format
    ): string {
        $course = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($context->course->getCode())) ?: 'course';
        $report = preg_replace('/[^A-Za-z0-9_-]+/', '-', $section) ?: 'report';

        return $course.'-'.$report.'.'.$format;
    }
}
