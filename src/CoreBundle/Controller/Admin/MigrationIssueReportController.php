<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Helpers\CsvFormulaEscaper;
use Chamilo\CoreBundle\Migrations\MigrationIssueReporter;
use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;

#[IsGranted('ROLE_GLOBAL_ADMIN')]
final class MigrationIssueReportController extends AbstractController
{
    private const array COLUMNS = [
        'migration', 'phase', 'table_name', 'course_id', 'course_code',
        'question_iid', 'legacy_question_id', 'answer_iid', 'legacy_answer_id',
        'legacy_answer_auto_id', 'relation_iid', 'row_iid', 'original_reference',
        'original_quiz_reference', 'original_question_reference', 'reason', 'planned_action',
    ];

    #[Route('/admin/migration-data-issues/{reportId}', name: 'admin_migration_data_issues_download', requirements: ['reportId' => 'pending|[0-9]{8}T[0-9]{6}Z-[a-f0-9]{12}'], methods: ['GET'])]
    public function __invoke(string $reportId, Connection $connection, MigrationIssueReporter $reporter): StreamedResponse
    {
        $files = $reporter->getReportFiles($connection, $reportId);
        if ([] === $files) {
            throw $this->createNotFoundException('Migration issue report not found.');
        }

        $response = new StreamedResponse(static function () use ($files): void {
            $output = fopen('php://output', 'wb');
            if (false === $output) {
                throw new RuntimeException('Cannot open migration report output.');
            }

            try {
                fputcsv($output, [...self::COLUMNS, 'details_json'], ',', '"', '');
                foreach ($files as $file) {
                    $input = fopen($file, 'rb');
                    if (false === $input) {
                        throw new RuntimeException('Cannot read migration issue report.');
                    }

                    try {
                        while (false !== ($line = fgets($input))) {
                            $issue = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                            $row = array_map(static fn (string $column): string => (string) ($issue[$column] ?? ''), self::COLUMNS);
                            // Future migrations can add identifiers without losing them in CSV.
                            $row[] = json_encode(array_diff_key($issue, array_flip(self::COLUMNS)), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                            fputcsv($output, CsvFormulaEscaper::escapeRow($row), ',', '"', '');
                        }
                        if (!feof($input)) {
                            throw new RuntimeException('Cannot read the complete migration issue report.');
                        }
                    } finally {
                        fclose($input);
                    }
                }
            } finally {
                fclose($output);
            }
        });
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="migration-data-issues-'.$reportId.'.csv"');
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
