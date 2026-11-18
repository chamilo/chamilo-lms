<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ExportService;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ImportService;
use Chamilo\CoreBundle\Service\Exercise\Qti2UniqueAnswerUnknownMarker;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
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
 * #9285: QTI 2.1 preserves the fixed zero-score unknown answer of type 10.
 * An unmarked QTI choiceInteraction must remain an ordinary unique-answer question.
 *
 * @psalm-suppress UndefinedMethod, UndefinedPropertyFetch the XPath results are DOMElement nodes, which Psalm types as DOMNode
 */
final class ExerciseQti2UniqueUnknownTest extends KernelTestCase
{
    /**
     * @var list<object>
     */
    private array $persisted = [];

    /**
     * @var list<string>
     */
    private array $tempFiles = [];

    public static function setUpBeforeClass(): void
    {
        require_once \dirname(__DIR__, 4).'/public/main/exercise/export/exercise_import.inc.php';
        $exportDir = \dirname(__DIR__, 4).'/public/main/exercise/export/qti2';
        if (!class_exists(ImsAssessmentItem::class, false)) {
            require_once $exportDir.'/qti2_export.php';
        }
        if (!class_exists(ImsAnswerMultipleChoice::class, false)) {
            require_once $exportDir.'/ImsAnswerMultipleChoice.php';
        }
        if (!class_exists(Ims2Question::class, false)) {
            require_once $exportDir.'/Ims2Question.php';
        }
    }

    protected function setUp(): void
    {
        self::bootKernel();
        Container::setContainer(self::getContainer());
        $this->persisted = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function testApiExportPreservesFixedUnknownAndMapping(): void
    {
        $xpath = $this->xpath($this->apiExport($this->question()));
        self::assertSame([Qti2UniqueAnswerUnknownMarker::LABEL], $this->attributes($xpath, '//q:assessmentItem', 'label'));
        self::assertSame(['single'], $this->attributes($xpath, '//q:responseDeclaration', 'cardinality'));
        self::assertSame(['answer_501'], $this->texts($xpath, '//q:correctResponse/q:value'));
        self::assertSame(['answer_501', 'answer_502', Qti2UniqueAnswerUnknownMarker::UNKNOWN_IDENTIFIER], $this->attributes($xpath, '//q:simpleChoice', 'identifier'));
        self::assertSame(['false', 'false', 'true'], $this->attributes($xpath, '//q:simpleChoice', 'fixed'));
        self::assertSame(['3.5', '-1', '0'], $this->attributes($xpath, '//q:mapEntry', 'mappedValue'));
        self::assertSame(['0'], $this->texts($xpath, '//q:outcomeDeclaration/q:defaultValue/q:value'));
        self::assertSame(['QST_50'], $this->attributes($xpath, '//q:responseProcessing/q:setOutcomeValue/q:mapResponse', 'identifier'));
    }

    public function testLegacyExportRetainsTheSameUnknownMarkerAndScore(): void
    {
        $xpath = $this->xpath($this->legacyExport());
        self::assertSame([Qti2UniqueAnswerUnknownMarker::LABEL], $this->attributes($xpath, '//q:assessmentItem', 'label'));
        self::assertSame(['answer_1', 'answer_2', Qti2UniqueAnswerUnknownMarker::UNKNOWN_IDENTIFIER], $this->attributes($xpath, '//q:simpleChoice', 'identifier'));
        self::assertSame(['false', 'false', 'true'], $this->attributes($xpath, '//q:simpleChoice', 'fixed'));
        self::assertSame(['3.5', '-1', '0'], $this->attributes($xpath, '//q:mapEntry', 'mappedValue'));
        self::assertSame(['QST_50'], $this->attributes($xpath, '//q:responseProcessing/q:setOutcomeValue/q:mapResponse', 'identifier'));
    }

    public function testModernImporterRestoresTypeTenAndPositionSixHundredSixtySix(): void
    {
        $this->import($this->wrap($this->apiExport($this->question())));
        $question = $this->onlyQuestion();
        self::assertSame(10, $question->getType());
        self::assertSame(3.5, $question->getPonderation());
        self::assertSame([
            ['Paris', 1, 3.5, 1],
            ['London', 0, -1.0, 2],
            ["Don't know", 0, 0.0, 666],
        ], $this->rows());
    }

    public function testLegacyParserKeepsMarkedTypeTenButUnmarkedTypeOne(): void
    {
        $marked = $this->apiExport($this->question());
        $normal = $this->apiExport($this->question(1));
        $questions = $this->legacyParse($this->wrap($marked.$normal));
        self::assertSame(10, $questions['QST_50']['type']);
        self::assertSame(1, $questions['QST_51']['type']);
        self::assertTrue(Qti2UniqueAnswerUnknownMarker::hasValidUnknownChoice($questions['QST_50']));
        self::assertSame('chamilo_unknown', array_key_last($questions['QST_50']['answer']));
    }

    public function testLegacyExportRoundTripsThroughModernImporter(): void
    {
        $this->import($this->wrap($this->legacyExport()));
        self::assertSame(10, $this->onlyQuestion()->getType());
        self::assertSame(3.5, $this->onlyQuestion()->getPonderation());
        self::assertSame([
            ['Paris', 1, 3.5, 1],
            ['London', 0, -1.0, 2],
            ["Don't know", 0, 0.0, 666],
        ], $this->rows());
    }

    public function testModernImporterDoesNotConvertUnmarkedUniqueAnswer(): void
    {
        $this->import($this->wrap($this->apiExport($this->question(1))));
        self::assertSame(1, $this->onlyQuestion()->getType());
        self::assertSame([1, 2, 3], array_column($this->rows(), 3));
    }

    public function testMarkedQuestionWithMissingFixedChoiceIsRejectedWithoutAffectingNeighbor(): void
    {
        $malformed = str_replace('identifier="chamilo_unknown"', 'identifier="ordinary_unknown"', $this->apiExport($this->question()));
        $valid = $this->apiExport($this->question(1));
        $result = $this->import($this->wrap($malformed.$valid));
        self::assertSame(1, $result['importedCount']);
        self::assertSame(1, $result['skippedCount']);
        self::assertCount(1, $result['errors']);
        self::assertSame(1, $this->onlyQuestion()->getType());
    }

    public function testMarkedQuestionWithMultipleCardinalityIsRejected(): void
    {
        $malformed = str_replace('cardinality="single" baseType="identifier"', 'cardinality="multiple" baseType="identifier"', $this->apiExport($this->question()));
        $valid = $this->apiExport($this->question(1));
        $result = $this->import($this->wrap($malformed.$valid));
        self::assertSame(1, $result['importedCount']);
        self::assertSame(1, $result['skippedCount']);
        self::assertSame(1, $this->onlyQuestion()->getType());
    }

    public function testUnknownNeverBecomesCorrectOrScoredOnImport(): void
    {
        $item = $this->apiExport($this->question());
        $item = str_replace('mapKey="chamilo_unknown" mappedValue="0"', 'mapKey="chamilo_unknown" mappedValue="999"', $item);
        $this->import($this->wrap($item));
        self::assertSame(["Don't know", 0, 0.0, 666], $this->rows()[2]);
    }

    private function question(int $type = 10): CQuizQuestion
    {
        $question = (new CQuizQuestion())->setQuestion('What is the capital of France?')->setType($type)->setPonderation(3.5);
        $this->iid($question, 10 === $type ? 50 : 51);
        foreach ([['Paris', 1, 3.5, 1], ['London', 0, -1.0, 2], ["Don't know", 0, 0.0, 666]] as $index => [$text, $correct, $score, $position]) {
            $answer = (new CQuizAnswer())->setQuestion($question)->setAnswer($text)->setComment('')->setCorrect($correct)->setPonderation($score)->setPosition(1 === $type ? $index + 1 : $position);
            $this->iid($answer, 501 + $index);
            $question->getAnswers()->add($answer);
        }

        return $question;
    }

    private function apiExport(CQuizQuestion $question): string
    {
        $service = (new ReflectionClass(ExerciseQti2ExportService::class))->newInstanceWithoutConstructor();

        return (new ReflectionMethod(ExerciseQti2ExportService::class, 'exportQuestion'))->invoke($service, $question);
    }

    private function legacyExport(): string
    {
        $answer = new class extends ImsAnswerMultipleChoice {
            public function __construct()
            {
                $this->position = [1 => 1, 2 => 2, 3 => 666];
            }

            public function getAnswersList($decode = false)
            {
                return [
                    ['id' => 1, 'answer' => 'Paris', 'comment' => '', 'grade' => 3.5, 'correct' => 1],
                    ['id' => 2, 'answer' => 'London', 'comment' => '', 'grade' => -1.0, 'correct' => 0],
                    ['id' => 3, 'answer' => "Don't know", 'comment' => '', 'grade' => 0.0, 'correct' => 0],
                ];
            }

            public function getQuestionType()
            {
                return UNIQUE_ANSWER_NO_OPTION;
            }
        };
        $question = (new ReflectionClass(Ims2Question::class))->newInstanceWithoutConstructor();
        $question->id = 50;
        $question->type = UNIQUE_ANSWER_NO_OPTION;
        $question->question = 'What is the capital of France?';
        $question->description = '';
        $question->weighting = 3.5;
        $question->category = 0;

        $item = (new ReflectionClass(ImsAssessmentItem::class))->newInstanceWithoutConstructor();
        $item->question = $question;
        $item->answer = $answer;
        $item->questionIdent = 'QST_50';

        return $item->export();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function legacyParse(string $xml): array
    {
        global $exerciseInfo;
        $exerciseInfo = ['question' => []];
        self::assertTrue(qti_parse_file($xml));

        return $exerciseInfo['question'];
    }

    /**
     * @return array{quiz: object, importedCount: int, skippedCount: int, errors: array<int, string>}
     */
    private function import(string $xml): array
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });
        $service = new ExerciseQti2ImportService(
            $manager,
            new CQuizQuestionCategoryRepository($this->createMock(ManagerRegistry::class))
        );

        return $service->import($this->zip($xml), new Course(), null);
    }

    private function onlyQuestion(): CQuizQuestion
    {
        $questions = array_values(array_filter($this->persisted, static fn (object $entity): bool => $entity instanceof CQuizQuestion));
        self::assertCount(1, $questions);

        return $questions[0];
    }

    /**
     * @return list<array{string, int, float, int}>
     */
    private function rows(): array
    {
        $rows = array_values(array_filter($this->persisted, static fn (object $entity): bool => $entity instanceof CQuizAnswer));
        usort($rows, static fn (CQuizAnswer $left, CQuizAnswer $right): int => $left->getPosition() <=> $right->getPosition());

        return array_map(static fn (CQuizAnswer $answer): array => [
            $answer->getAnswer(), $answer->getCorrect(), $answer->getPonderation(), $answer->getPosition(),
        ], $rows);
    }

    private function iid(object $entity, int $iid): void
    {
        (new ReflectionProperty($entity, 'iid'))->setValue($entity, $iid);
    }

    private function zip(string $xml): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'qti2-unknown-');
        self::assertIsString($path);
        $this->tempFiles[] = $path;
        $archive = new ZipArchive();
        self::assertTrue(true === $archive->open($path, ZipArchive::OVERWRITE));
        $archive->addFromString('qti2export_1.xml', $xml);
        $archive->close();

        return new UploadedFile($path, 'qti2_unknown.zip', 'application/zip', null, true);
    }

    private function wrap(string $items): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">'
            .'<questestinterop><section ident="EXO_1" title="Unknown answers">'
            .$items.'</section></questestinterop>';
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($xml, LIBXML_NONET));

        return $xml;
    }

    private function xpath(string $xml): DOMXPath
    {
        $dom = new DOMDocument();
        self::assertTrue($dom->loadXML($this->wrap($xml), LIBXML_NONET));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('q', 'http://www.imsglobal.org/xsd/imsqti_v2p1');

        return $xpath;
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

    /**
     * @return list<string>
     */
    private function texts(DOMXPath $xpath, string $query): array
    {
        $values = [];
        foreach ($xpath->query($query) as $node) {
            $values[] = trim($node->textContent);
        }

        return $values;
    }
}
