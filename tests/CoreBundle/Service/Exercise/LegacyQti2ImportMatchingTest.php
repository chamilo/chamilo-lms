<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Service\Exercise\Qti2ExactCombinationWriter;
use Chamilo\CoreBundle\Service\Exercise\Qti2FillBlanksWriter;
use Chamilo\CoreBundle\Service\Exercise\Qti2MatchingTypeMarker;
use DOMDocument;
use ImsAssessmentItem;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Covers the parsing half of the legacy QTI2 importer (main/exercise/qti2.php), which used to
 * parse matchInteraction and then skip the question (issue #1377).
 */
final class LegacyQti2ImportMatchingTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once \dirname(__DIR__, 4).'/public/main/exercise/export/exercise_import.inc.php';

        require_once \dirname(__DIR__, 4).'/public/main/exercise/export/qti2/qti2_export.php';
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

    public function testLegacyParserPreservesDraggableMatchingWithChamiloLabel(): void
    {
        $item = $this->buildMatchingItem(
            'QST_1',
            'Capitals',
            ['left_0' => 'France'],
            ['right_0' => 'Paris'],
            ['left_0 right_0' => 2.5]
        );
        $item = str_replace(
            '<assessmentItem identifier="QST_1"',
            '<assessmentItem identifier="QST_1" label="'.Qti2MatchingTypeMarker::DRAGGABLE_LABEL.'"',
            $item
        );

        $questions = $this->parse($item);

        self::assertSame(MATCHING_DRAGGABLE, $questions['QST_1']['type']);
        self::assertSame(
            [
                ['answer' => 'Paris', 'correct' => 0, 'weight' => 0.0],
                ['answer' => 'France', 'correct' => 1, 'weight' => 2.5],
            ],
            buildMatchingAnswerRows($questions['QST_1'])
        );
    }

    public function testLegacyParserDoesNotLeakDraggableLabelToOtherItems(): void
    {
        $first = $this->buildMatchingItem('QST_1', 'Drag', ['left_0' => 'A'], ['right_0' => 'B'], ['left_0 right_0' => 2]);
        $first = str_replace(
            '<assessmentItem identifier="QST_1"',
            '<assessmentItem identifier="QST_1" label="'.Qti2MatchingTypeMarker::DRAGGABLE_LABEL.'"',
            $first
        );
        $second = $this->buildMatchingItem('QST_2', 'Match', ['left_0' => 'C'], ['right_0' => 'D'], ['left_0 right_0' => 3]);

        $questions = $this->parse($first.$second);

        self::assertSame(MATCHING_DRAGGABLE, $questions['QST_1']['type']);
        self::assertSame(MATCHING, $questions['QST_2']['type']);
        self::assertSame(2.0, buildMatchingAnswerRows($questions['QST_1'])[1]['weight']);
        self::assertSame(3.0, buildMatchingAnswerRows($questions['QST_2'])[1]['weight']);
    }

    public function testLegacyExporterMarksOnlyDraggableMatching(): void
    {
        $item = (new ReflectionClass(ImsAssessmentItem::class))->newInstanceWithoutConstructor();
        $question = new class {
            public int $type = MATCHING_DRAGGABLE;
            public ?int $category = null;

            public function selectTitle(): string
            {
                return 'Match by dragging';
            }
        };
        $item->question = $question;
        $item->questionIdent = 'QST_123';

        $document = new DOMDocument();
        self::assertTrue($document->loadXML($item->start_item().'</assessmentItem>'));
        self::assertSame(Qti2MatchingTypeMarker::DRAGGABLE_LABEL, $document->documentElement->getAttribute('label'));

        $question->type = MATCHING;
        self::assertTrue($document->loadXML($item->start_item().'</assessmentItem>'));
        self::assertFalse($document->documentElement->hasAttribute('label'));
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

    /**
     * Independent QTI question types must survive a single legacy import without leaking state.
     */
    public function testMixedMultipleExactMatchingAndDraggableItemsStayDistinct(): void
    {
        $multiple = '<assessmentItem identifier="QST_10" title="Multiple answer" category="">'
            .'<responseDeclaration identifier="QST_10" cardinality="multiple" baseType="identifier">'
            .'<correctResponse><value>m1</value></correctResponse>'
            .'<mapping><mapEntry mapKey="m1" mappedValue="2"/><mapEntry mapKey="m2" mappedValue="0"/></mapping>'
            .'</responseDeclaration><itemBody><choiceInteraction responseIdentifier="QST_10" maxChoices="0">'
            .'<simpleChoice identifier="m1">Correct</simpleChoice><simpleChoice identifier="m2">Incorrect</simpleChoice>'
            .'</choiceInteraction></itemBody>'
            .'<responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_response"/>'
            .'</assessmentItem>';
        $exact = '<assessmentItem identifier="QST_20" title="Exact combination" category="">'
            .Qti2ExactCombinationWriter::declarations('QST_20', ['x1', 'x3'], 4.5)
            .'<itemBody><choiceInteraction responseIdentifier="QST_20" maxChoices="0">'
            .'<simpleChoice identifier="x1">First</simpleChoice>'
            .'<simpleChoice identifier="x2">Second</simpleChoice>'
            .'<simpleChoice identifier="x3">Third</simpleChoice>'
            .'</choiceInteraction></itemBody>'
            .Qti2ExactCombinationWriter::responseProcessing('QST_20', 4.5)
            .'</assessmentItem>';
        $matching = $this->buildMatchingItem('QST_30', 'Matching', ['left_0' => 'A'], ['right_0' => 'B'], ['left_0 right_0' => 3]);
        $draggable = $this->buildMatchingItem('QST_40', 'Draggable', ['left_0' => 'C'], ['right_0' => 'D'], ['left_0 right_0' => 5]);
        $draggable = str_replace(
            '<assessmentItem identifier="QST_40"',
            '<assessmentItem identifier="QST_40" label="'.Qti2MatchingTypeMarker::DRAGGABLE_LABEL.'"',
            $draggable
        );
        $questions = $this->parse($multiple.$exact.$matching.$draggable);

        self::assertSame(MCMA, $questions['QST_10']['type']);
        self::assertSame(MULTIPLE_ANSWER_COMBINATION, $questions['QST_20']['type']);
        self::assertSame(4.5, (float) $questions['QST_20']['combination_score']);
        self::assertSame(MATCHING, $questions['QST_30']['type']);
        self::assertSame(MATCHING_DRAGGABLE, $questions['QST_40']['type']);
        self::assertSame(3.0, buildMatchingAnswerRows($questions['QST_30'])[1]['weight']);
        self::assertSame(5.0, buildMatchingAnswerRows($questions['QST_40'])[1]['weight']);
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
     * The legacy importer cleans fill in blanks texts the same way: scripts, event handlers and
     * javascript: URLs are removed, formatting and the blanks are kept, and the blank lost with the
     * <script> takes its score with it.
     */
    public function testFillBlanksTextHtmlIsSanitizedAndBlanksKept(): void
    {
        $fib = Qti2FillBlanksWriter::write('<p onclick="alert(1)"><strong>Capital</strong> of France: [Paris|London|Rome] <script>alert([2])</script><a href="javascript:alert(3)">map</a> and [a||b] <img src="x.png" onerror="alert(4)"> {kept}</p>::2,5,3:200,200,200:0@0');
        self::assertNotNull($fib);
        $questions = $this->parse('<assessmentItem identifier="QST_9" title="FIB" category="">'.$fib['declarations']
            .'<itemBody>'.$fib['body'].'</itemBody></assessmentItem>');

        [$answer, $score] = buildFibAnswerString($questions['QST_9']);

        self::assertSame('<p><strong>Capital</strong> of France: [Paris|London|Rome] <a>map</a> and [a||b] <img src="x.png" alt="x.png" /> {kept}</p>::2,3:200,200:0@0', $answer);
        self::assertSame(5.0, (float) $score);
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
