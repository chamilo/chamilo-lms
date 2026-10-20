<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use PHPUnit\Framework\TestCase;

/**
 * Covers the parsing half of the legacy QTI2 importer (main/exercise/qti2.php), which used to
 * parse matchInteraction and then skip the question (issue #1377).
 */
final class LegacyQti2ImportMatchingTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once \dirname(__DIR__, 4).'/public/main/exercise/export/exercise_import.inc.php';
    }

    public function testMatchingItemsAreTurnedIntoChamiloMatchingAnswerRows(): void
    {
        $questions = $this->parse($this->buildMatchingItem('QST_1', 'Capitals', [
            'left_0' => 'France',
            'left_1' => 'Spain',
            'left_2' => 'Italy',
        ], [
            'right_0' => 'Rome',
            'right_1' => 'Paris',
            'right_2' => 'Madrid',
            'right_3' => 'Lisbon',
        ], [
            'left_0 right_1' => 1,
            'left_1 right_2' => 2,
            'left_2 right_0' => 3,
        ]));

        self::assertSame(MATCHING, $questions['QST_1']['type']);

        // Options first with correct = 0, then each item with the position of its option,
        // which Answer::save() converts into the option iid.
        self::assertSame(
            [
                ['answer' => 'Rome', 'correct' => 0, 'weight' => 0.0],
                ['answer' => 'Paris', 'correct' => 0, 'weight' => 0.0],
                ['answer' => 'Madrid', 'correct' => 0, 'weight' => 0.0],
                ['answer' => 'Lisbon', 'correct' => 0, 'weight' => 0.0],
                ['answer' => 'France', 'correct' => 2, 'weight' => 1.0],
                ['answer' => 'Spain', 'correct' => 3, 'weight' => 2.0],
                ['answer' => 'Italy', 'correct' => 1, 'weight' => 3.0],
            ],
            buildMatchingAnswerRows($questions['QST_1'])
        );
    }

    public function testEachMatchingItemOfAFileKeepsItsOwnMatchSets(): void
    {
        $questions = $this->parse(
            $this->buildMatchingItem('QST_1', 'First', ['left_0' => 'A'], ['right_0' => '1'], ['left_0 right_0' => 1])
            .$this->buildMatchingItem('QST_2', 'Second', ['left_0' => 'B'], ['right_0' => '2'], ['left_0 right_0' => 4])
        );

        self::assertSame(
            [
                ['answer' => '2', 'correct' => 0, 'weight' => 0.0],
                ['answer' => 'B', 'correct' => 1, 'weight' => 4.0],
            ],
            buildMatchingAnswerRows($questions['QST_2'])
        );
    }

    public function testMatchingItemWithoutAssociationsYieldsNoRows(): void
    {
        $questions = $this->parse($this->buildMatchingItem('QST_1', 'Empty', [], [], []));

        self::assertSame(MATCHING, $questions['QST_1']['type']);
        self::assertSame([], buildMatchingAnswerRows($questions['QST_1']));
    }

    /**
     * Matching answers are displayed without filtering, so scripts, event handlers and javascript:
     * URLs from an uploaded package must not reach the database.
     */
    public function testMatchingAnswerHtmlIsSanitized(): void
    {
        $questions = $this->parse($this->buildMatchingItem('QST_1', 'XSS', [
            'left_0' => '<b>Bold</b><img src="x.png" onerror="alert(1)"><script>alert(2)</script>',
        ], [
            'right_0' => '&lt;a href="javascript:alert(3)"&gt;Link&lt;/a&gt;&lt;svg onload=alert(4)&gt;',
        ], [
            'left_0 right_0' => 1,
        ]));

        $rows = buildMatchingAnswerRows($questions['QST_1']);

        self::assertCount(2, $rows);
        self::assertSame('<a>Link</a>', $rows[0]['answer']);
        self::assertSame('<b>Bold</b><img src="x.png" alt="x.png" />', $rows[1]['answer']);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function parse(string $items): array
    {
        global $exerciseInfo;
        $exerciseInfo = ['name' => 'test', 'question' => []];

        self::assertTrue(qti_parse_file('<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">
<questestinterop><section ident="EXO_1" title="Matching">'.$items.'</section></questestinterop>'));

        return $exerciseInfo['question'];
    }

    /**
     * Same structure as ImsAnswerMatching output.
     *
     * @param array<string, string> $left
     * @param array<string, string> $right
     * @param array<string, int>    $pairs
     */
    private function buildMatchingItem(string $ident, string $title, array $left, array $right, array $pairs): string
    {
        $xml = '<assessmentItem identifier="'.$ident.'" title="'.$title.'" category="">'
            .'<responseDeclaration identifier="'.$ident.'" cardinality="multiple" baseType="directedPair"><correctResponse>';
        foreach (array_keys($pairs) as $pair) {
            $xml .= '<value>'.$pair.'</value>';
        }
        $xml .= '</correctResponse><mapping>';
        foreach ($pairs as $pair => $score) {
            $xml .= '<mapEntry mapKey="'.$pair.'" mappedValue="'.$score.'"/>';
        }
        $xml .= '</mapping></responseDeclaration><itemBody>'
            .'<matchInteraction responseIdentifier="'.$ident.'" maxAssociations="'.\count($left).'"><simpleMatchSet>';
        foreach ($left as $id => $text) {
            $xml .= '<simpleAssociableChoice identifier="'.$id.'"><![CDATA['.$text.']]></simpleAssociableChoice>';
        }
        $xml .= '</simpleMatchSet><simpleMatchSet>';
        foreach ($right as $id => $text) {
            $xml .= '<simpleAssociableChoice identifier="'.$id.'"><![CDATA['.$text.']]></simpleAssociableChoice>';
        }

        return $xml.'</simpleMatchSet></matchInteraction></itemBody></assessmentItem>';
    }
}
