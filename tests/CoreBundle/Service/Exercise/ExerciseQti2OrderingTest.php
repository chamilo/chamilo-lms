<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ExportService;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ImportService;
use Chamilo\CoreBundle\Service\Exercise\Qti2OrderingItem;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use DOMXPath;
use ImsAnswerOrdering;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * #9285: Sequence ordering (18) uses QTI 2.1 orderInteraction while retaining each item's
 * target position, position-specific weight and orientation across modern and legacy flows.
 *
 * @psalm-suppress UndefinedMethod, UndefinedPropertyFetch the XPath results are DOMElement nodes, which Psalm types as DOMNode
 */
final class ExerciseQti2OrderingTest extends KernelTestCase
{
    /**
     * @var list<object>
     */
    private array $persisted = [];

    /**
     * @var list<string>
     */
    private array $tempFiles = [];
    private int $nextId = 3000;

    public static function setUpBeforeClass(): void
    {
        require_once \dirname(__DIR__, 4).'/public/main/exercise/export/exercise_import.inc.php';

        require_once \dirname(__DIR__, 4).'/public/main/exercise/export/qti2/ImsAnswerInterface.php';

        require_once \dirname(__DIR__, 4).'/public/main/exercise/export/qti2/ImsAnswerOrdering.php';
    }

    protected function setUp(): void
    {
        self::bootKernel();
        Container::setContainer(self::getContainer());
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    public function testApiExportUsesOrderedCardinalityAndPartialCredit(): void
    {
        $xml = $this->exportQuestion($this->buildQuestion());
        $xpath = $this->xpath($xml);
        self::assertSame('ordered', $xpath->query('//q:responseDeclaration')->item(0)->getAttribute('cardinality'));
        self::assertSame('identifier', $xpath->query('//q:responseDeclaration')->item(0)->getAttribute('baseType'));
        self::assertSame('vertical', $xpath->query('//q:orderInteraction')->item(0)->getAttribute('orientation'));
        self::assertSame(['answer_202', 'answer_203', 'answer_201'], $this->nodeTexts($xpath, '//q:correctResponse/q:value'));
        self::assertSame(['answer_201', 'answer_202', 'answer_203'], $this->attributes($xpath, '//q:orderInteraction/q:simpleChoice', 'identifier'));
        self::assertSame(['3', '1', '2'], $this->attributes($xpath, '//q:responseIf/q:match/q:index', 'n'));
        self::assertSame(['1.5', '2', '3'], $this->nodeTexts($xpath, '//q:responseIf/q:setOutcomeValue/q:sum/q:baseValue'));
        self::assertSame('6.5', $xpath->query('//q:outcomeDeclaration')->item(0)->getAttribute('normalMaximum'));
    }

    public function testApiRoundTripPreservesQuestionTypePositionsWeightsAndOrientation(): void
    {
        $item = $this->exportQuestion($this->buildQuestion());
        $result = $this->makeImporter()->import($this->zip($this->wrap($item)), new Course(), null);
        self::assertSame(1, $result['importedCount']);
        self::assertSame(0, $result['skippedCount']);
        self::assertSame([], $result['errors']);
        $questions = array_values(array_filter($this->persisted, static fn (object $x): bool => $x instanceof CQuizQuestion));
        self::assertCount(1, $questions);
        $question = $questions[0];
        self::assertSame(18, $question->getType());
        self::assertSame('v', $question->getExtra());
        self::assertSame(6.5, $question->getPonderation());
        $rows = array_values(array_filter($this->persisted, static fn (object $x): bool => $x instanceof CQuizAnswer));
        usort($rows, static fn (CQuizAnswer $a, CQuizAnswer $b): int => $a->getPosition() <=> $b->getPosition());
        self::assertCount(6, $rows);
        self::assertSame(
            [['1', 0, 0.0, 1], ['2', 0, 0.0, 2], ['3', 0, 0.0, 3],
                ['First', 3, 1.5, 4], ['Second', 1, 2.0, 5], ['Third', 2, 3.0, 6]],
            array_map(static fn (CQuizAnswer $a): array => [$a->getAnswer(), $a->getCorrect(), $a->getPonderation(), $a->getPosition()], $rows)
        );
    }

    public function testLegacyParserRecognizesOrderingAndDoesNotLeakType(): void
    {
        $ordering = $this->exportQuestion($this->buildQuestion());
        $matching = '<assessmentItem identifier="QST_22" title="Unique"><responseDeclaration identifier="QST_22" cardinality="single" baseType="identifier">'
            .'<correctResponse><value>c</value></correctResponse></responseDeclaration><itemBody>'
            .'<choiceInteraction responseIdentifier="QST_22"><simpleChoice identifier="c">Ok</simpleChoice></choiceInteraction></itemBody></assessmentItem>';
        global $exerciseInfo;
        $exerciseInfo = ['question' => []];
        parseQti2($this->wrap($ordering.$matching));
        self::assertSame(DRAGGABLE, $exerciseInfo['question']['QST_77']['type']);
        self::assertSame(UNIQUE_ANSWER, $exerciseInfo['question']['QST_22']['type']);
        self::assertSame(['orientation' => 'v', 'items' => [
            ['answer' => 'First', 'target' => 3, 'weight' => 1.5],
            ['answer' => 'Second', 'target' => 1, 'weight' => 2.0],
            ['answer' => 'Third', 'target' => 2, 'weight' => 3.0],
        ]], $exerciseInfo['question']['QST_77']['ordered_items']);
    }

    public function testLegacyExporterAdapterProducesEquivalentOrderingItem(): void
    {
        $adapter = new class extends ImsAnswerOrdering {
            public function __construct() {}
            public function read()
            {
                return true;
            }
            public function getAnswersList($decode = false)
            {
                return [
                    ['id' => 1, 'correct' => 0, 'answer' => '1', 'grade' => 0],
                    ['id' => 2, 'correct' => 0, 'answer' => '2', 'grade' => 0],
                    ['id' => 3, 'correct' => 0, 'answer' => '3', 'grade' => 0],
                    ['id' => 4, 'correct' => 3, 'answer' => 'First', 'grade' => 1.5],
                    ['id' => 5, 'correct' => 1, 'answer' => 'Second', 'grade' => 2.0],
                    ['id' => 6, 'correct' => 2, 'answer' => 'Third', 'grade' => 3.0],
                ];
            }
            public function selectAutoId($id)
            {
                return $id + 100;
            }
        };
        $decl = $adapter->imsExportResponsesDeclaration('QST_1', null);
        self::assertTrue($adapter->isValidOrdering());
        $xml = '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" identifier="QST_1" title="Legacy">'
            .$decl.'<itemBody>'.$adapter->imsExportResponses('QST_1', 'Legacy').'</itemBody>'
            .$adapter->getOrderingResponseProcessing().'</assessmentItem>';
        $xpath = $this->xpath($xml);
        self::assertSame(['answer_105', 'answer_106', 'answer_104'], $this->nodeTexts($xpath, '//q:correctResponse/q:value'));
        self::assertSame(['1.5', '2', '3'], $this->nodeTexts($xpath, '//q:responseIf/q:setOutcomeValue/q:sum/q:baseValue'));
        $items = $xpath->query('//q:orderInteraction');
        self::assertSame(1, $items->length);
    }

    public function testLegacyExporterUnderstandsOldOptionIidReferences(): void
    {
        $adapter = new class extends ImsAnswerOrdering {
            public function __construct() {}
            public function read()
            {
                return true;
            }
            public function getAnswersList($decode = false)
            {
                return [
                    ['id' => 1, 'correct' => 0, 'answer' => '1', 'grade' => 0],
                    ['id' => 2, 'correct' => 0, 'answer' => '2', 'grade' => 0],
                    ['id' => 3, 'correct' => 102, 'answer' => 'First', 'grade' => 1],
                    ['id' => 4, 'correct' => 101, 'answer' => 'Second', 'grade' => 2],
                ];
            }
            public function selectAutoId($id)
            {
                return $id + 100;
            }
        };
        $decl = $adapter->imsExportResponsesDeclaration('QST_1', null);
        $xml = '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" identifier="QST_1">'
            .$decl.'<itemBody>'.$adapter->imsExportResponses('QST_1', '').'</itemBody>'
            .$adapter->getOrderingResponseProcessing().'</assessmentItem>';
        self::assertSame(['answer_104', 'answer_103'], $this->nodeTexts($this->xpath($xml), '//q:correctResponse/q:value'));
    }

    public function testRejectsIncompleteAndAmbiguousOrdering(): void
    {
        $items = [
            ['identifier' => 'a', 'text' => 'A', 'target' => 1, 'weight' => 2.0],
            ['identifier' => 'b', 'text' => 'B', 'target' => 1, 'weight' => 3.0],
        ];
        self::assertNull(Qti2OrderingItem::write('QST_1', 'Order', $items));
        $items[1]['target'] = 3;
        self::assertNull(Qti2OrderingItem::write('QST_1', 'Order', $items));
        $items[1]['target'] = 2;
        $items[1]['weight'] = -1;
        self::assertNull(Qti2OrderingItem::write('QST_1', 'Order', $items));
        $items[1]['weight'] = 3;
        $items[1]['identifier'] = 'a';
        self::assertNull(Qti2OrderingItem::write('QST_1', 'Order', $items));
    }

    public function testGenericQtiOrderUsesSafeDefaultWeights(): void
    {
        $fragment = Qti2OrderingItem::write('R', 'Order', [
            ['identifier' => 'a', 'text' => 'A', 'target' => 2, 'weight' => 1.5],
            ['identifier' => 'b', 'text' => 'B', 'target' => 1, 'weight' => 2.5],
        ]);
        $fragment = preg_replace(
            '#<responseProcessing>.*?</responseProcessing>#s',
            '<responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/match_correct"/>',
            $fragment
        );
        $document = new DOMDocument();
        self::assertTrue($document->loadXML('<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1">'.$fragment.'</assessmentItem>'));
        $interaction = $document->getElementsByTagName('orderInteraction')->item(0);
        self::assertSame(['orientation' => 'h', 'items' => [
            ['answer' => 'A', 'target' => 2, 'weight' => 1.0],
            ['answer' => 'B', 'target' => 1, 'weight' => 1.0],
        ]], Qti2OrderingItem::read($interaction));
    }

    public function testZeroWeightsStayZeroAndNoOtherTypeTurnsIntoOrdering(): void
    {
        $fragment = Qti2OrderingItem::write('R', 'Zero', [
            ['identifier' => 'a', 'text' => 'A', 'target' => 1, 'weight' => 0.0],
            ['identifier' => 'b', 'text' => 'B', 'target' => 2, 'weight' => 0.0],
        ]);
        $document = new DOMDocument();
        self::assertTrue($document->loadXML('<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1">'.$fragment.'</assessmentItem>'));
        self::assertSame([0.0, 0.0], array_column(Qti2OrderingItem::read($document->getElementsByTagName('orderInteraction')->item(0))['items'], 'weight'));
    }

    public function testImportedAnswerHtmlIsPurified(): void
    {
        $fragment = Qti2OrderingItem::write('R', 'Safe', [
            ['identifier' => 'a', 'text' => '<img src="x" onerror="alert(1)"><b>Safe</b>', 'target' => 1, 'weight' => 1.0],
            ['identifier' => 'b', 'text' => '<script>alert(2)</script>Text', 'target' => 2, 'weight' => 2.0],
        ]);
        $document = new DOMDocument();
        self::assertTrue($document->loadXML('<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1">'.$fragment.'</assessmentItem>'));
        $rows = Qti2OrderingItem::read($document->getElementsByTagName('orderInteraction')->item(0));
        self::assertNotNull($rows);
        $text = implode(' ', array_column($rows['items'], 'answer'));
        self::assertStringNotContainsString('onerror', $text);
        self::assertStringNotContainsString('<script', $text);
        self::assertStringContainsString('Safe', $text);
    }

    private function exportQuestion(CQuizQuestion $question): string
    {
        $service = (new ReflectionClass(ExerciseQti2ExportService::class))->newInstanceWithoutConstructor();

        return (new ReflectionMethod(ExerciseQti2ExportService::class, 'exportQuestion'))->invoke($service, $question);
    }

    private function buildQuestion(): CQuizQuestion
    {
        $question = (new CQuizQuestion())
            ->setQuestion('Order the steps')
            ->setType(18)
            ->setExtra('v')
            ->setPonderation(6.5)
        ;
        (new ReflectionProperty($question, 'iid'))->setValue($question, 77);
        $data = [
            [101, '1', 0, 0.0], [102, '2', 0, 0.0], [103, '3', 0, 0.0],
            [201, 'First', 3, 1.5], [202, 'Second', 1, 2.0], [203, 'Third', 2, 3.0],
        ];
        foreach ($data as $position => [$iid, $answer, $correct, $score]) {
            $item = (new CQuizAnswer())->setQuestion($question)->setAnswer($answer)
                ->setCorrect($correct)->setPonderation($score)->setPosition($position + 1)
            ;
            (new ReflectionProperty($item, 'iid'))->setValue($item, $iid);
            $question->getAnswers()->add($item);
        }

        return $question;
    }

    private function makeImporter(): ExerciseQti2ImportService
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(function (object $entity): void { $this->persisted[] = $entity; });
        $manager->method('flush')->willReturnCallback(function (): void {
            foreach ($this->persisted as $entity) {
                if ($entity instanceof CQuizAnswer && null === $entity->getIid()) {
                    (new ReflectionProperty($entity, 'iid'))->setValue($entity, $this->nextId++);
                }
            }
        });

        return new ExerciseQti2ImportService($manager, new CQuizQuestionCategoryRepository($this->createMock(ManagerRegistry::class)));
    }

    private function zip(string $xml): UploadedFile
    {
        $name = tempnam(sys_get_temp_dir(), 'qti-order-test-');
        self::assertIsString($name);
        $this->tempFiles[] = $name;
        $zip = new ZipArchive();
        self::assertTrue(true === $zip->open($name, ZipArchive::OVERWRITE));
        $zip->addFromString('bank.xml', $xml);
        $zip->close();

        return new UploadedFile($name, 'ordering.zip', 'application/zip', null, true);
    }

    private function wrap(string $items): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">'
            .'<questestinterop><section ident="EXO_1" title="Sequence ordering">'.$items.'</section></questestinterop>';
    }

    private function xpath(string $xml): DOMXPath
    {
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('q', 'http://www.imsglobal.org/xsd/imsqti_v2p1');

        return $xpath;
    }

    /**
     * @return list<string>
     */
    private function nodeTexts(DOMXPath $xpath, string $query): array
    {
        $values = [];
        foreach ($xpath->query($query) as $node) {
            $values[] = trim($node->textContent);
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private function attributes(DOMXPath $xpath, string $query, string $attribute): array
    {
        $values = [];
        foreach ($xpath->query($query) as $node) {
            $values[] = $node->getAttribute($attribute);
        }

        return $values;
    }
}
