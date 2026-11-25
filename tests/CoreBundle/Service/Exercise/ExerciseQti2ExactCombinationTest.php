<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ExportService;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ImportService;
use Chamilo\CoreBundle\Service\Exercise\Qti2ExactCombinationWriter;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Ims2Question;
use ImsAnswerMultipleChoice;
use ImsAssessmentItem;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

use const LIBXML_NONET;

/**
 * #2263: Exact combination questions (MULTIPLE_ANSWER_COMBINATION) are exported to QTI2 by both exporters as a
 * multiple-response choiceInteraction scored all or nothing, and come back from both importers as Exact
 * combination questions with the same correct answers and score.
 */
final class ExerciseQti2ExactCombinationTest extends KernelTestCase
{
    private const MULTIPLE_ANSWER = 2;
    private const MULTIPLE_ANSWER_COMBINATION = 9;
    private const MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE = 12;

    /**
     * @var array<int, object>
     */
    private array $persisted = [];

    /**
     * @var array<int, string>
     */
    private array $tempFiles = [];

    public static function setUpBeforeClass(): void
    {
        require_once \dirname(__DIR__, 4).'/public/main/exercise/export/exercise_import.inc.php';

        $exportDir = \dirname(__DIR__, 4).'/public/main/exercise/export';
        // The legacy exporter classes of this checkout, before the class map points elsewhere.
        if (!class_exists(ImsAssessmentItem::class, false)) {
            require_once $exportDir.'/qti2/qti2_export.php';
        }
        if (!class_exists(ImsAnswerMultipleChoice::class, false)) {
            require_once $exportDir.'/qti2/ImsAnswerMultipleChoice.php';
        }
        if (!class_exists(Ims2Question::class, false)) {
            require_once $exportDir.'/qti2/Ims2Question.php';
        }
    }

    protected function setUp(): void
    {
        // The legacy importer and exporter read settings through the legacy container.
        self::bootKernel();
        Container::setContainer(self::getContainer());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    public function testApiExportIsAnAllOrNothingMultipleResponse(): void
    {
        $xpath = $this->xpath($this->exportWithApiService($this->buildQuestion()));

        self::assertSame(['multiple'], $this->attributes($xpath, '//q:responseDeclaration', 'cardinality'));
        self::assertSame(['identifier'], $this->attributes($xpath, '//q:responseDeclaration', 'baseType'));
        self::assertSame(['answer_501', 'answer_503'], $this->texts($xpath, '//q:responseDeclaration/q:correctResponse/q:value'));
        self::assertCount(0, $xpath->query('//q:mapping'), 'No per-answer mapping: the score is all or nothing.');
        self::assertSame(['SCORE'], $this->attributes($xpath, '//q:outcomeDeclaration', 'identifier'));
        self::assertSame(['0'], $this->attributes($xpath, '//q:choiceInteraction', 'maxChoices'));
        self::assertSame(['answer_501', 'answer_502', 'answer_503'], $this->attributes($xpath, '//q:simpleChoice', 'identifier'));

        self::assertSame([''], $this->attributes($xpath, '//q:responseProcessing', 'template'));
        self::assertSame(['QST_50'], $this->attributes($xpath, '//q:responseIf/q:match/q:variable', 'identifier'));
        self::assertSame(['QST_50'], $this->attributes($xpath, '//q:responseIf/q:match/q:correct', 'identifier'));
        self::assertSame(['4.5'], $this->texts($xpath, '//q:responseIf/q:setOutcomeValue[@identifier="SCORE"]/q:baseValue'));
        self::assertSame(['0'], $this->texts($xpath, '//q:responseElse/q:setOutcomeValue[@identifier="SCORE"]/q:baseValue'));
    }

    public function testApiExportRoundTripsThroughBothImporters(): void
    {
        $xml = $this->exportWithApiService($this->buildQuestion());

        self::assertSame($this->expectedImport(), $this->importWithApiService($xml));
        self::assertSame($this->expectedLegacyParse('answer_501', 'answer_503'), $this->parseWithLegacyImporter($xml));
    }

    public function testLegacyExportMatchesAndRoundTripsThroughBothImporters(): void
    {
        $xml = $this->wrapItems($this->exportWithLegacyExporter());
        $xpath = $this->xpath($xml);

        self::assertSame(['multiple'], $this->attributes($xpath, '//q:responseDeclaration', 'cardinality'));
        self::assertSame(['answer_1', 'answer_3'], $this->texts($xpath, '//q:correctResponse/q:value'));
        self::assertCount(0, $xpath->query('//q:mapping'));
        self::assertSame(['0'], $this->attributes($xpath, '//q:choiceInteraction', 'maxChoices'));
        self::assertSame(['4.5'], $this->texts($xpath, '//q:responseIf/q:setOutcomeValue/q:baseValue'));

        self::assertSame($this->expectedImport(), $this->importWithApiService($xml));
        self::assertSame($this->expectedLegacyParse('answer_1', 'answer_3'), $this->parseWithLegacyImporter($xml));
    }

    public function testScoreFallsBackToTheQuestionScoreWhenTheFirstAnswerHasNone(): void
    {
        $question = $this->buildQuestion(0.0)->setPonderation(2.0);
        $xpath = $this->xpath($this->exportWithApiService($question));

        self::assertSame(['2'], $this->texts($xpath, '//q:responseIf/q:setOutcomeValue/q:baseValue'));
    }

    public function testExactCombinationAndItsTrueFalseVariantAreExportedTypes(): void
    {
        $service = (new ReflectionClass(ExerciseQti2ExportService::class))->newInstanceWithoutConstructor();
        $isExportable = new ReflectionMethod(ExerciseQti2ExportService::class, 'isLegacyExportableQuestion');

        self::assertTrue($isExportable->invoke($service, (new CQuizQuestion())->setType(self::MULTIPLE_ANSWER_COMBINATION)));
        // Exported through Qti2TrueFalseItem since "Extend QTI2 question type support" (#9285); this test used
        // to assert that only the exact combination was exportable.
        self::assertTrue($isExportable->invoke($service, (new CQuizQuestion())->setType(self::MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE)));
    }

    public function testTextsAreEscaped(): void
    {
        $question = $this->buildQuestion();
        $question->getAnswers()->first()->setAnswer('<script>alert(1)</script> ]]> & "q"');
        $xml = $this->exportWithApiService($question);

        self::assertStringNotContainsString('<script>', $xml);
        self::assertSame(
            ['&lt;script&gt;alert(1)&lt;/script&gt; ]]&gt; &amp; &quot;q&quot;'],
            \array_slice(array_map('trim', $this->texts($this->xpath($xml), '//q:simpleChoice')), 0, 1)
        );
    }

    public function testOtherMultipleResponsesKeepTheirImport(): void
    {
        $item = '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" identifier="QST_7" title="Plain">
  <responseDeclaration identifier="QST_7" cardinality="multiple" baseType="identifier">
    <correctResponse><value>a</value></correctResponse>
    <mapping><mapEntry mapKey="a" mappedValue="2"/><mapEntry mapKey="b" mappedValue="0"/></mapping>
  </responseDeclaration>
  <itemBody><choiceInteraction responseIdentifier="QST_7"><simpleChoice identifier="a">A</simpleChoice><simpleChoice identifier="b">B</simpleChoice></choiceInteraction></itemBody>
  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_response"/>
</assessmentItem>
';
        $xml = $this->wrapItems($item);

        [$type, $score] = $this->importWithApiService($xml);
        self::assertSame([self::MULTIPLE_ANSWER, 2.0], [$type, $score]);
        self::assertSame(MCMA, $this->parseWithLegacyImporter($xml)['type']);
    }

    public function testReadScoreOnlyAcceptsTheExactMatchProcessing(): void
    {
        self::assertSame(3.0, $this->readScore(Qti2ExactCombinationWriter::responseProcessing('Q', 3.0)));
        self::assertNull($this->readScore('<responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/match_correct"/>'));
        self::assertNull($this->readScore('<responseProcessing><responseCondition><responseIf><isNull><variable identifier="Q"/></isNull><setOutcomeValue identifier="SCORE"><baseValue baseType="float">1</baseValue></setOutcomeValue></responseIf></responseCondition></responseProcessing>'));
        self::assertSame(0.0, $this->readScore(Qti2ExactCombinationWriter::responseProcessing('Q', -5.0)), 'Negative scores are not imported.');
    }

    private function readScore(string $responseProcessing): ?float
    {
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($responseProcessing, LIBXML_NONET));
        $element = $document->documentElement;
        self::assertInstanceOf(DOMElement::class, $element);

        return Qti2ExactCombinationWriter::readScore($element);
    }

    /**
     * @return array{0: int, 1: float, 2: list<array{0: string, 1: int, 2: float}>}
     */
    private function expectedImport(): array
    {
        return [self::MULTIPLE_ANSWER_COMBINATION, 4.5, [
            ['Paris', 1, 4.5],
            ['London', 0, 0.0],
            ['Lyon', 1, 0.0],
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private function expectedLegacyParse(string ...$correct): array
    {
        return ['type' => MULTIPLE_ANSWER_COMBINATION, 'combination_score' => 4.5, 'correct_answers' => $correct];
    }

    private function buildQuestion(float $firstAnswerWeight = 4.5): CQuizQuestion
    {
        $question = (new CQuizQuestion())
            ->setQuestion('Which cities are in France?')
            ->setType(self::MULTIPLE_ANSWER_COMBINATION)
            ->setPonderation($firstAnswerWeight)
        ;
        $this->setIid($question, 50);
        foreach ([['Paris', 1], ['London', 0], ['Lyon', 1]] as $index => [$text, $correct]) {
            $answer = (new CQuizAnswer())
                ->setQuestion($question)
                ->setAnswer($text)
                ->setCorrect($correct)
                ->setComment('')
                ->setPonderation(0 === $index ? $firstAnswerWeight : 0.0)
                ->setPosition($index + 1)
            ;
            $this->setIid($answer, 501 + $index);
            $question->getAnswers()->add($answer);
        }

        return $question;
    }

    private function exportWithApiService(CQuizQuestion $question): string
    {
        $exportService = (new ReflectionClass(ExerciseQti2ExportService::class))->newInstanceWithoutConstructor();
        $exportQuestion = new ReflectionMethod(ExerciseQti2ExportService::class, 'exportQuestion');

        $xml = $this->wrapItems($exportQuestion->invoke($exportService, $question));
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml, LIBXML_NONET), 'The export must be well-formed XML.');

        return $xml;
    }

    /**
     * A whole assessmentItem from the legacy ImsAssessmentItem, its answers read from a stub instead of the DB.
     */
    private function exportWithLegacyExporter(): string
    {
        $answer = new class extends ImsAnswerMultipleChoice {
            public function __construct()
            {
                $this->weighting = [1 => 4.5, 2 => 0.0, 3 => 0.0];
            }

            /**
             * @param mixed $decode
             *
             * @return list<array<string, mixed>>
             */
            public function getAnswersList($decode = false)
            {
                return [
                    ['id' => 1, 'answer' => 'Paris', 'comment' => '', 'grade' => 4.5, 'correct' => 1],
                    ['id' => 2, 'answer' => 'London', 'comment' => '', 'grade' => 0.0, 'correct' => 0],
                    ['id' => 3, 'answer' => 'Lyon', 'comment' => '', 'grade' => 0.0, 'correct' => 1],
                ];
            }

            public function getQuestionType()
            {
                return MULTIPLE_ANSWER_COMBINATION;
            }
        };

        $question = (new ReflectionClass(Ims2Question::class))->newInstanceWithoutConstructor();
        $question->id = 50;
        $question->type = MULTIPLE_ANSWER_COMBINATION;
        $question->question = 'Which cities are in France?';
        $question->description = '';
        $question->weighting = 4.5;
        $question->category = 0;

        $item = (new ReflectionClass(ImsAssessmentItem::class))->newInstanceWithoutConstructor();
        $item->question = $question;
        $item->answer = $answer;
        $item->questionIdent = 'QST_50';

        return $item->export();
    }

    /**
     * @return array{0: int, 1: float, 2: list<array{0: string, 1: int, 2: float}>}
     */
    private function importWithApiService(string $xml): array
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });
        $importService = new ExerciseQti2ImportService(
            $entityManager,
            new CQuizQuestionCategoryRepository($this->createMock(ManagerRegistry::class)),
        );

        $result = $importService->import($this->createZip($xml), new Course(), null);
        self::assertSame(1, $result['importedCount']);

        $questions = array_values(array_filter($this->persisted, static fn (object $entity): bool => $entity instanceof CQuizQuestion));
        $answers = array_values(array_filter($this->persisted, static fn (object $entity): bool => $entity instanceof CQuizAnswer));
        self::assertCount(1, $questions);

        return [
            $questions[0]->getType(),
            $questions[0]->getPonderation(),
            array_map(
                static fn (CQuizAnswer $answer): array => [trim($answer->getAnswer()), (int) $answer->getCorrect(), $answer->getPonderation()],
                $answers
            ),
        ];
    }

    /**
     * @return array<string, mixed> type, score and correct answers the legacy importer would save
     */
    private function parseWithLegacyImporter(string $xml): array
    {
        global $exerciseInfo;
        $exerciseInfo = ['name' => 'test', 'question' => []];

        self::assertTrue(qti_parse_file($xml));

        /** @var array<int, array<string, mixed>> $questions filled in by qti_parse_file() */
        $questions = array_values($exerciseInfo['question']);
        self::assertCount(1, $questions);

        return [
            'type' => $questions[0]['type'],
            'combination_score' => $questions[0]['combination_score'] ?? null,
            'correct_answers' => array_values($questions[0]['correct_answers']),
        ];
    }

    private function wrapItems(string $items): string
    {
        return '<?xml version = "1.0" encoding = "UTF-8" standalone = "no"?>
<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">
<questestinterop>
<section ident = "EXO_1" title = "Exact combination export">
'.$items.'</section>
</questestinterop>
';
    }

    private function xpath(string $xml): DOMXPath
    {
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml, LIBXML_NONET));
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('q', 'http://www.imsglobal.org/xsd/imsqti_v2p1');

        return $xpath;
    }

    /**
     * @return array<int, string>
     */
    private function attributes(DOMXPath $xpath, string $query, string $attribute): array
    {
        $values = [];
        foreach ($xpath->query($query) as $node) {
            self::assertInstanceOf(DOMElement::class, $node);
            $values[] = $node->getAttribute($attribute);
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function texts(DOMXPath $xpath, string $query): array
    {
        $values = [];
        foreach ($xpath->query($query) as $node) {
            self::assertInstanceOf(DOMNode::class, $node);
            $values[] = $node->textContent;
        }

        return $values;
    }

    private function setIid(object $entity, int $iid): void
    {
        (new ReflectionProperty($entity, 'iid'))->setValue($entity, $iid);
    }

    private function createZip(string $xml): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'qti2-exact-test-');
        self::assertIsString($path);
        $this->tempFiles[] = $path;
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('qti2export_1.xml', $xml);
        $zip->close();

        return new UploadedFile($path, 'qti2_export_1.zip', 'application/zip', null, true);
    }
}
