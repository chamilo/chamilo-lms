<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use PhpZip\ZipFile;
use PHPUnit\Framework\TestCase;

require_once \dirname(__DIR__, 3).'/public/main/exercise/export/aiken/aiken_import.inc.php';

final class AikenImportValidationTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir().'/chamilo-aiken-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->temporaryDirectory, 0700));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory.'/*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($this->temporaryDirectory);
    }

    public function testValidScoresAndExistingAikenFormat(): void
    {
        $text = "What is one plus one?\nA. One\nB. Two\nANSWER: B\nSCORE: 15.5\n";

        self::assertTrue(\aiken_validate_score_directives($text));

        $exercise = ['question' => [], 'total_weight' => 20];
        \setExerciseInfoFromAikenText($text, $exercise);

        self::assertCount(1, $exercise['question']);
        self::assertSame([1], $exercise['question'][0]['correct_answers']);
        self::assertSame(15.5, $exercise['question'][0]['score']);
    }

    public function testLowercaseScoreDirectiveIsHandled(): void
    {
        $text = "What is one plus one?\nA. One\nB. Two\nANSWER: B\nscore: 3\n";

        self::assertTrue(\aiken_validate_score_directives($text));

        $exercise = ['question' => [], 'total_weight' => 20];
        \setExerciseInfoFromAikenText($text, $exercise);

        self::assertCount(1, $exercise['question']);
        self::assertSame(3.0, $exercise['question'][0]['score']);
    }

    public function testNegativeZeroAndMalformedScoresAreRejected(): void
    {
        foreach (['SCORE: -5', 'SCORE: 0', 'SCORE: banana', 'SCORE:', 'SCORE: 1e6', 'SCORE: 5 points'] as $score) {
            self::assertFalse(\aiken_validate_score_directives(
                "A question?\nA. Yes\nB. No\nANSWER: A\n".$score."\n"
            ), $score);
        }
    }

    public function testMissingScoreKeepsOriginalAikenValid(): void
    {
        self::assertTrue(\aiken_validate_score_directives("A question?\nA. Yes\nB. No\nANSWER: A\n"));
    }

    public function testPlainTextCanBeRead(): void
    {
        $path = $this->temporaryDirectory.'/quiz.txt';
        $text = "A question?\nA. Yes\nB. No\nANSWER: A\n";
        file_put_contents($path, $text);

        self::assertSame($text, \aiken_read_file_text($path));
    }

    public function testOversizedPlainTextIsRejected(): void
    {
        $path = $this->temporaryDirectory.'/large.txt';
        file_put_contents($path, str_repeat('A', \AIKEN_MAX_TEXT_BYTES + 1));

        self::assertNull(\aiken_read_file_text($path));
    }

    public function testZipReadsMultipleTextEntries(): void
    {
        $path = $this->zipFile([
            'question-one.txt' => "Question one?\nA. Yes\nB. No\nANSWER: A",
            'nested/question-two.txt' => "Question two?\nA. No\nB. Yes\nANSWER: B",
        ]);

        $text = \aiken_read_file_text($path);
        self::assertNotNull($text);
        self::assertStringContainsString('Question one?', $text);
        self::assertStringContainsString('Question two?', $text);
    }

    public function testZipWithoutTextIsRejected(): void
    {
        $path = $this->zipFile(['picture.png' => 'not a text question']);

        self::assertNull(\aiken_read_file_text($path));
    }

    public function testZipWithTooManyTextFilesIsRejected(): void
    {
        $entries = [];
        for ($i = 0; $i <= \AIKEN_MAX_ZIP_TEXT_FILES; ++$i) {
            $entries['question-'.$i.'.txt'] = 'Question?';
        }

        self::assertNull(\aiken_read_file_text($this->zipFile($entries)));
    }

    public function testZipWithTooManyEntriesIsRejected(): void
    {
        $entries = [];
        for ($i = 0; $i <= \AIKEN_MAX_ZIP_ENTRIES; ++$i) {
            $entries['item-'.$i.'.png'] = 'data';
        }

        self::assertNull(\aiken_read_file_text($this->zipFile($entries)));
    }

    public function testZipWithOversizedDecompressedTextIsRejected(): void
    {
        $path = $this->zipFile(['questions.txt' => str_repeat('A', \AIKEN_MAX_TEXT_BYTES + 1)]);

        self::assertNull(\aiken_read_file_text($path));
    }

    /**
     * @param array<string, string> $entries
     */
    private function zipFile(array $entries): string
    {
        $path = $this->temporaryDirectory.'/questions.zip';
        $zip = new ZipFile();

        try {
            foreach ($entries as $name => $data) {
                $zip->addFromString($name, $data);
            }
            $zip->saveAsFile($path);
        } finally {
            $zip->close();
        }

        return $path;
    }
}
