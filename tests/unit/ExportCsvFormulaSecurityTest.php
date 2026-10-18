<?php

/* For licensing terms, see /license.txt */

require_once dirname(__DIR__, 2).'/main/inc/lib/export.lib.inc.php';

use PHPUnit\Framework\TestCase;

class ExportCsvFormulaSecurityTest extends TestCase
{
    public function testDangerousPrefixesAreEscaped()
    {
        foreach ([
            '=1+1',
            '+SUM(1,2)',
            '-CMD',
            '@SUM(A1:A2)',
            "\tvalue",
            "\rvalue",
            "\nvalue",
            '  =1+1',
            " \t@SUM(A1:A2)",
            '-42',
        ] as $value) {
            $this->assertSame("'".$value, Export::escapeCsvCell($value));
        }
    }

    public function testSafeValuesAreUnmodified()
    {
        foreach (['Alice', 'Alice =1+1', "'=1+1", '', -42, 1.5, null, false] as $value) {
            $this->assertSame($value, Export::escapeCsvCell($value));
        }
    }

    public function testQuotedSurveyAnswersKeepTheirColumns()
    {
        $source = "Name;Answer;Note\nAlice;\"=HYPERLINK(\"\"https://example.invalid\"\",\"\"click\"\")\";\"Some; text\"\n";
        $result = Export::escapeDelimitedCsvContent($source);
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $result);
        rewind($stream);
        $this->assertSame(['Name', 'Answer', 'Note'], fgetcsv($stream, 0, ';', '"', ''));
        $this->assertSame(
            ['Alice', "'=HYPERLINK(\"https://example.invalid\",\"click\")", 'Some; text'],
            fgetcsv($stream, 0, ';', '"', '')
        );
        fclose($stream);
    }

    public function testUnquotedFormulaAfterSeparatorIsEscaped()
    {
        $result = Export::escapeDelimitedCsvContent("Name;Answer\nAlice;=1+1\n");
        $this->assertSame("Name;Answer\nAlice;'=1+1\n", $result);
    }
}
