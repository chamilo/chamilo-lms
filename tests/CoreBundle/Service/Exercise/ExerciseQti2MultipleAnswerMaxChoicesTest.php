<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ExportService;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ImportService;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use DOMElement;
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
 * Multiple answer questions are exported to QTI2 by both exporters with maxChoices="0" (the QTI default is
 * a single choice), single choice questions keep the default, and the export still imports as Multiple answer.
 */
final class ExerciseQti2MultipleAnswerMaxChoicesTest extends KernelTestCase
{
    private const UNIQUE_ANSWER = 1;
    private const MULTIPLE_ANSWER = 2;

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

    public function testApiExportAllowsAnyNumberOfChoicesForMultipleAnswer(): void
    {
        $xpath = $this->xpath($this->exportWithApiService($this->buildQuestion(self::MULTIPLE_ANSWER)));

        self::assertSame(['0'], $this->attributes($xpath, '//q:choiceInteraction', 'maxChoices'));
        self::assertSame(['multiple'], $this->attributes($xpath, '//q:responseDeclaration', 'cardinality'));
    }

    public function testApiExportKeepsTheDefaultForUniqueAnswer(): void
    {
        $xpath = $this->xpath($this->exportWithApiService($this->buildQuestion(self::UNIQUE_ANSWER)));

        self::assertSame([false], $this->hasAttribute($xpath, '//q:choiceInteraction', 'maxChoices'));
    }

    public function testLegacyExportAllowsAnyNumberOfChoicesForMultipleAnswer(): void
    {
        $xpath = $this->xpath($this->wrapItems($this->exportWithLegacyExporter(MCMA)));

        self::assertSame(['0'], $this->attributes($xpath, '//q:choiceInteraction', 'maxChoices'));
        self::assertSame(['multiple'], $this->attributes($xpath, '//q:responseDeclaration', 'cardinality'));
    }

    public function testLegacyExportKeepsTheDefaultForUniqueAnswer(): void
    {
        $xpath = $this->xpath($this->wrapItems($this->exportWithLegacyExporter(UNIQUE_ANSWER)));

        self::assertSame([false], $this->hasAttribute($xpath, '//q:choiceInteraction', 'maxChoices'));
    }

    public function testApiExportStillImportsAsMultipleAnswer(): void
    {
        $xml = $this->wrapItems($this->exportWithApiService($this->buildQuestion(self::MULTIPLE_ANSWER)));

        self::assertSame([self::MULTIPLE_ANSWER, ['answer_501', 'answer_503']], $this->parseWithLegacyImporter($xml));
        self::assertSame(self::MULTIPLE_ANSWER, $this->importWithApiService($xml));
    }

    public function testLegacyExportStillImportsAsMultipleAnswer(): void
    {
        $xml = $this->wrapItems($this->exportWithLegacyExporter(MCMA));

        self::assertSame([self::MULTIPLE_ANSWER, ['answer_1', 'answer_3']], $this->parseWithLegacyImporter($xml));
        self::assertSame(self::MULTIPLE_ANSWER, $this->importWithApiService($xml));
    }

    private function buildQuestion(int $type): CQuizQuestion
    {
        $question = (new CQuizQuestion())
            ->setQuestion('Which cities are in France?')
            ->setType($type)
            ->setPonderation(4.0)
        ;
        $this->setIid($question, 50);
        foreach ([['Paris', 1, 2.0], ['London', 0, 0.0], ['Lyon', self::MULTIPLE_ANSWER === $type ? 1 : 0, 2.0]] as $index => [$text, $correct, $weight]) {
            $answer = (new CQuizAnswer())
                ->setQuestion($question)
                ->setAnswer($text)
                ->setCorrect($correct)
                ->setComment('')
                ->setPonderation($weight)
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

        return $exportQuestion->invoke($exportService, $question);
    }

    /**
     * A whole assessmentItem from the legacy ImsAssessmentItem, its answers read from a stub instead of the DB.
     */
    private function exportWithLegacyExporter(int $type): string
    {
        $answer = new class extends ImsAnswerMultipleChoice {
            public int $questionType = MCMA;

            public function __construct()
            {
                $this->weighting = [1 => 2.0, 2 => 0.0, 3 => 2.0];
            }

            /**
             * @param mixed $decode
             *
             * @return list<array<string, mixed>>
             */
            public function getAnswersList($decode = false)
            {
                return [
                    ['id' => 1, 'answer' => 'Paris', 'comment' => '', 'grade' => 2.0, 'correct' => 1],
                    ['id' => 2, 'answer' => 'London', 'comment' => '', 'grade' => 0.0, 'correct' => 0],
                    ['id' => 3, 'answer' => 'Lyon', 'comment' => '', 'grade' => 2.0, 'correct' => MCMA === $this->questionType ? 1 : 0],
                ];
            }

            public function getQuestionType()
            {
                return $this->questionType;
            }
        };
        $answer->questionType = $type;

        $question = (new ReflectionClass(Ims2Question::class))->newInstanceWithoutConstructor();
        $question->id = 50;
        $question->type = $type;
        $question->question = 'Which cities are in France?';
        $question->description = '';
        $question->weighting = 4.0;
        $question->category = 0;

        $item = (new ReflectionClass(ImsAssessmentItem::class))->newInstanceWithoutConstructor();
        $item->question = $question;
        $item->answer = $answer;
        $item->questionIdent = 'QST_50';

        return $item->export();
    }

    /**
     * The type of the question the API importer would save.
     */
    private function importWithApiService(string $xml): int
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
        self::assertCount(1, $questions);

        return $questions[0]->getType();
    }

    /**
     * @return array{0: mixed, 1: list<mixed>} type and correct answers the legacy importer would save
     */
    private function parseWithLegacyImporter(string $xml): array
    {
        global $exerciseInfo;
        $exerciseInfo = ['name' => 'test', 'question' => []];

        self::assertTrue(qti_parse_file($xml));

        /** @var array<int, array<string, mixed>> $questions filled in by qti_parse_file() */
        $questions = array_values($exerciseInfo['question']);
        self::assertCount(1, $questions);
        $correct = $questions[0]['correct_answers'] ?? [];

        return [$questions[0]['type'], array_values(\is_array($correct) ? $correct : [])];
    }

    private function wrapItems(string $items): string
    {
        $xml = '<?xml version = "1.0" encoding = "UTF-8" standalone = "no"?>
<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">
<questestinterop>
<section ident = "EXO_1" title = "Multiple answer export">
'.$items.'</section>
</questestinterop>
';
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml, LIBXML_NONET), 'The export must be well-formed XML.');

        return $xml;
    }

    private function xpath(string $xml): DOMXPath
    {
        if (!str_contains($xml, '<questestinterop>')) {
            $xml = $this->wrapItems($xml);
        }
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
     * @return array<int, bool>
     */
    private function hasAttribute(DOMXPath $xpath, string $query, string $attribute): array
    {
        $values = [];
        foreach ($xpath->query($query) as $node) {
            self::assertInstanceOf(DOMElement::class, $node);
            $values[] = $node->hasAttribute($attribute);
        }

        return $values;
    }

    private function setIid(object $entity, int $iid): void
    {
        (new ReflectionProperty($entity, 'iid'))->setValue($entity, $iid);
    }

    private function createZip(string $xml): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'qti2-mcma-test-');
        self::assertIsString($path);
        $this->tempFiles[] = $path;
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('qti2export_1.xml', $xml);
        $zip->close();

        return new UploadedFile($path, 'qti2_export_1.zip', 'application/zip', null, true);
    }
}
