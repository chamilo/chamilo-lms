<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsQueryService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminStatisticsExportController extends AbstractController
{
    private const EXPORT_FORMATS = [
        'session_by_date' => ['xls', 'xlsx'],
        'logins_by_date' => ['xls', 'xlsx'],
        'users_active' => ['xls', 'xlsx'],
        'user_session' => ['xls', 'xlsx'],
        'duplicated_users' => ['csv', 'xls', 'xlsx'],
    ];

    public function __construct(
        private readonly AdminStatisticsQueryService $queryService,
    ) {}

    #[Route(
        '/api/admin/statistics/session-by-date.{format}',
        name: 'chamilo_core_admin_statistics_session_by_date_export',
        requirements: ['format' => 'xls|xlsx'],
        methods: ['GET']
    )]
    public function sessionByDate(string $format, Request $request): Response
    {
        return $this->createExportResponse('session_by_date', $format, $request);
    }

    #[Route(
        '/api/admin/statistics/export/{report}.{format}',
        name: 'chamilo_core_admin_statistics_export',
        requirements: [
            'report' => 'logins_by_date|users_active|user_session|duplicated_users',
            'format' => 'csv|xls|xlsx',
        ],
        methods: ['GET']
    )]
    public function export(string $report, string $format, Request $request): Response
    {
        return $this->createExportResponse($report, $format, $request);
    }

    private function createExportResponse(string $report, string $format, Request $request): Response
    {
        if (!isset(self::EXPORT_FORMATS[$report]) || !\in_array($format, self::EXPORT_FORMATS[$report], true)) {
            throw new BadRequestHttpException('This export format is not available for the selected report.');
        }

        $data = $this->queryService->getExportData($report, $request->query->all());
        if ([] === $data['columns']) {
            throw new BadRequestHttpException('No statistics columns are available for export.');
        }

        $filename = 'admin-statistics-'.str_replace('_', '-', $report).'.'.$format;
        if ('csv' === $format) {
            return $this->createCsvResponse($data, $filename);
        }

        return $this->createSpreadsheetResponse($data, $filename, $format);
    }

    /**
     * @param array{columns: array<int, array<string, string>>, items: array<int, array<string, mixed>>} $data
     */
    private function createSpreadsheetResponse(array $data, string $filename, string $format): BinaryFileResponse
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
            foreach ($data['columns'] as $column) {
                $key = (string) ($column['key'] ?? '');
                $value = $this->normalizeExportValue($item[$key] ?? '');
                $sheet->setCellValue([$columnIndex, $rowIndex], $value);
                ++$columnIndex;
            }
            ++$rowIndex;
        }

        foreach (range(1, max(1, \count($data['columns']))) as $index) {
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'admin-statistics-');
        if (false === $temporaryFile) {
            throw new RuntimeException('Could not create the statistics export file.');
        }

        $writer = 'xls' === $format ? new Xls($spreadsheet) : new Xlsx($spreadsheet);
        $writer->save($temporaryFile);
        $spreadsheet->disconnectWorksheets();

        $response = new BinaryFileResponse(new File($temporaryFile));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->deleteFileAfterSend(true);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * @param array{columns: array<int, array<string, string>>, items: array<int, array<string, mixed>>} $data
     */
    private function createCsvResponse(array $data, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($data): void {
            $output = fopen('php://output', 'wb');
            if (false === $output) {
                throw new RuntimeException('Could not open the CSV output stream.');
            }

            fputcsv($output, array_map(
                static fn (array $column): string => (string) ($column['label'] ?? $column['key'] ?? ''),
                $data['columns']
            ));

            foreach ($data['items'] as $item) {
                $row = [];
                foreach ($data['columns'] as $column) {
                    $key = (string) ($column['key'] ?? '');
                    $row[] = $this->normalizeExportValue($item[$key] ?? '');
                }
                fputcsv($output, $row);
            }

            fclose($output);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        ));
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function normalizeExportValue(mixed $value): float|int|string
    {
        if (\is_int($value) || \is_float($value)) {
            return $value;
        }

        return $this->neutralizeSpreadsheetFormula(strip_tags((string) $value));
    }

    private function neutralizeSpreadsheetFormula(string $value): string
    {
        if ('' !== $value && \in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
