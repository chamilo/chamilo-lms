<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Helpers\CsvFormulaEscaper;
use PHPUnit\Framework\TestCase;

final class CsvFormulaEscaperTest extends TestCase
{
    public function testFormulaPrefixesAndControlCharactersAreNeutralized(): void
    {
        foreach ([
            '=HYPERLINK("https://example.invalid", "click")',
            '+CMD',
            '-CMD',
            '@SUM(A1:A2)',
            "\tvalue",
            "\rvalue",
            "\nvalue",
            '  =SUM(A1:A2)',
            " \t@SUM(A1:A2)",
            '-42',
        ] as $dangerous) {
            self::assertSame("'".$dangerous, CsvFormulaEscaper::escapeCell($dangerous));
        }
    }

    public function testSafeValuesAndNumbersArePreserved(): void
    {
        foreach (['Name', 'User, comma', ' safe', "'=-already-escaped", '', null, -42, 12.5, false] as $safe) {
            self::assertSame($safe, CsvFormulaEscaper::escapeCell($safe));
        }
    }

    public function testCsvHeadersAndValuesAreEscaped(): void
    {
        self::assertSame(
            ["'=header" => "'@formula", 'number' => 2],
            CsvFormulaEscaper::escapeRow(['=header' => '@formula', 'number' => 2], true)
        );
    }

    public function testQuotedLegacySurveyCsvIsEscapedWithoutSplittingCells(): void
    {
        $source = "Name;Answer\nStudent;\"=HYPERLINK(\"\"https://example.invalid\"\",\"\"click\"\")\"\n";
        $escaped = CsvFormulaEscaper::escapeDelimitedContent($source);

        $handle = fopen('php://temp', 'w+');
        self::assertIsResource($handle);
        fwrite($handle, $escaped);
        rewind($handle);
        self::assertSame(['Name', 'Answer'], fgetcsv($handle, 0, ';', '"', ''));
        self::assertSame(
            ['Student', "'=HYPERLINK(\"https://example.invalid\",\"click\")"],
            fgetcsv($handle, 0, ';', '"', '')
        );
        fclose($handle);
    }
}
