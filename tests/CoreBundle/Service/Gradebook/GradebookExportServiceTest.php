<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookReport;
use Chamilo\CoreBundle\Service\Gradebook\GradebookExportService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The Excel export of the Gradebook list view is meant to be worked on in a
 * spreadsheet (sums, averages, charts). A score written as the text
 * "18.00 / 20.00 (90.00%)" cannot be used that way, so each score has to land
 * in its own numeric cells (issue #1642).
 */
final class GradebookExportServiceTest extends TestCase
{
    public function testExcelListViewExportsEachScoreAsNumericPercentageScoreAndMaxCells(): void
    {
        $sheet = $this->exportFlatXls($this->buildReport());

        self::assertSame(
            [
                'First name',
                'Last name',
                'Username',
                'Official code',
                'Quiz 1 (Percentage)',
                'Quiz 1 (Score)',
                'Quiz 1 (Max score)',
                'Total (Percentage)',
                'Total (Score)',
                'Total (Max score)',
            ],
            $this->rowValues($sheet, 1, 10),
        );

        // Learner with a result: every score cell is a number Excel can compute with.
        self::assertSame(['Ada', 'Lovelace', 'alovelace', 'OC1'], $this->rowValues($sheet, 2, 4));
        $this->assertNumericCell($sheet, 'E2', 0.9);
        $this->assertNumericCell($sheet, 'F2', 18.0);
        $this->assertNumericCell($sheet, 'G2', 20.0);
        $this->assertNumericCell($sheet, 'H2', 0.9);
        $this->assertNumericCell($sheet, 'I2', 18.0);
        $this->assertNumericCell($sheet, 'J2', 20.0);
        self::assertSame('0.00%', $sheet->getStyle('E2')->getNumberFormat()->getFormatCode());

        // Learner without a result: the score cells stay empty instead of holding 0.
        foreach (['E3', 'F3', 'G3', 'H3', 'I3', 'J3'] as $coordinate) {
            self::assertNull($sheet->getCell($coordinate)->getValue(), $coordinate.' should be empty');
        }
    }

    private function exportFlatXls(GradebookReport $report): Worksheet
    {
        $service = new GradebookExportService($this->identityTranslator(), $this->createMock(SettingsManager::class));
        $response = $service->createFlatResponse($report, 'xls', 'Course');

        $file = tempnam(sys_get_temp_dir(), 'gradebook-xls-');
        self::assertIsString($file);
        file_put_contents($file, (string) $response->getContent());

        try {
            return IOFactory::createReader('Xls')->load($file)->getActiveSheet();
        } finally {
            unlink($file);
        }
    }

    private function buildReport(): GradebookReport
    {
        $result = [
            'hasResult' => true,
            'score' => 18.0,
            'maxScore' => 20.0,
            'percentage' => 90.0,
            'display' => '',
        ];

        $report = new GradebookReport();
        $report->columns = [['key' => 'link-1', 'title' => 'Quiz 1']];
        $report->settings = ['numberDecimals' => 2];
        $report->rows = [
            [
                'user' => ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'username' => 'alovelace', 'officialCode' => 'OC1'],
                'scores' => ['link-1' => $result],
                'total' => $result,
            ],
            [
                'user' => ['firstName' => 'Alan', 'lastName' => 'Turing', 'username' => 'aturing', 'officialCode' => ''],
                'scores' => ['link-1' => ['hasResult' => false]],
                'total' => ['hasResult' => false],
            ],
        ];

        return $report;
    }

    /**
     * @return list<mixed>
     */
    private function rowValues(Worksheet $sheet, int $row, int $columns): array
    {
        $values = [];
        for ($column = 1; $column <= $columns; $column++) {
            $values[] = $sheet->getCell([$column, $row])->getValue();
        }

        return $values;
    }

    private function assertNumericCell(Worksheet $sheet, string $coordinate, float $expected): void
    {
        $cell = $sheet->getCell($coordinate);
        self::assertSame(DataType::TYPE_NUMERIC, $cell->getDataType(), $coordinate.' should be numeric');
        self::assertEqualsWithDelta($expected, (float) $cell->getValue(), 0.0001, $coordinate);
    }

    private function identityTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return $translator;
    }
}
