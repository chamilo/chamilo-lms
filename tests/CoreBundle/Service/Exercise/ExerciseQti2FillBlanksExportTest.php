<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ExportService;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ImportService;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use FillBlanks;
use ImsAnswerFillInBlanks;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

use const LIBXML_NONET;

/**
 * Fill in blanks questions are exported to QTI2 (textEntryInteraction for typed blanks,
 * inlineChoiceInteraction for menus) by both exporters and must import back through both importers
 * with the same blanks, correct answers and scores.
 */
final class ExerciseQti2FillBlanksExportTest extends TestCase
{
    private const FILL_IN_BLANKS = 3;

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
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    /**
     * @dataProvider provideFillBlanks
     */
    public function testApiExportRoundTripsThroughBothImporters(string $stored, string $expected, float $expectedScore): void
    {
        $xml = $this->exportWithApiService($this->buildFillBlanksQuestion($stored));

        $this->assertValidQti($xml);
        self::assertSame([$expected, $expectedScore, ''], $this->importWithApiService($xml));
        self::assertSame([$expected, $expectedScore, ''], $this->importWithLegacyImporter($xml));
    }

    /**
     * @dataProvider provideFillBlanks
     */
    public function testLegacyExportMatchesApiExportAndRoundTrips(string $stored, string $expected, float $expectedScore): void
    {
        $legacyItem = $this->exportWithLegacyExporter($stored, '');
        $apiXml = $this->exportWithApiService($this->buildFillBlanksQuestion($stored));

        self::assertStringContainsString($legacyItem, $apiXml);

        $xml = $this->wrapItems($this->wrapLegacyItem($legacyItem));
        $this->assertValidQti($xml);
        self::assertSame([$expected, $expectedScore, ''], $this->importWithApiService($xml));
        self::assertSame([$expected, $expectedScore, ''], $this->importWithLegacyImporter($xml));
    }

    /**
     * [stored answer, expected answer after import (sizes, separator and order switch are not part of QTI)].
     */
    public static function provideFillBlanks(): iterable
    {
        yield 'typed blanks with alternatives' => [
            '<p>I use a [computer] and a [pen||biro].</p>::1,2.5:200,100:0@1',
            '<p>I use a [computer] and a [pen||biro].</p>::1,2.5:200,200:0@0',
            3.5,
        ];

        yield 'drop-down menu' => [
            'Capital of France: [Paris|London|Rome] ok::3:200:0@',
            'Capital of France: [Paris|London|Rome] ok::3:200:0@0',
            3.0,
        ];

        yield 'menu and typed blanks mixed' => [
            '[cat] and [dog|cow] then [x||y]::1,2,0.5:200,200,200:0@0',
            '[cat] and [dog|cow] then [x||y]::1,2,0.5:200,200,200:0@0',
            3.5,
        ];

        yield 'other separator' => [
            'Use {word} here::4:150:1@0',
            'Use [word] here::4:200:0@0',
            4.0,
        ];

        yield 'html and entities' => [
            'a &lt; b [AT&amp;T] <b>bold</b> ]]&gt; [caf&eacute;]::1,1.5:200,200:0@0',
            'a &lt; b [AT&amp;T] <b>bold</b> ]]&gt; [caf&eacute;]::1,1.5:200,200:0@0',
            2.5,
        ];

        yield 'same word with different scores' => [
            '[the] and [the]::1,2:200,200:0@0',
            '[the] and [the]::1,2:200,200:0@0',
            3.0,
        ];
    }

    public function testCaseInsensitiveBlanksKeepTheirSetting(): void
    {
        $question = $this->buildFillBlanksQuestion('[Paris] and [Rome||Roma]::1,1:200,200:0@0')->setExtra('case:false');
        $xml = $this->exportWithApiService($question);

        self::assertStringContainsString('<mapEntry mapKey="Roma" mappedValue="1" caseSensitive="false"/>', $xml);
        self::assertSame(['[Paris] and [Rome||Roma]::1,1:200,200:0@0', 2.0, 'case:false'], $this->importWithApiService($xml));
        self::assertSame(['[Paris] and [Rome||Roma]::1,1:200,200:0@0', 2.0, 'case:false'], $this->importWithLegacyImporter($xml));

        $legacyItem = $this->exportWithLegacyExporter('[Paris] and [Rome||Roma]::1,1:200,200:0@0', 'case:false');
        self::assertStringContainsString('caseSensitive="false"', $legacyItem);
    }

    public function testBlanksAreExportedAsQtiInteractions(): void
    {
        $xml = $this->exportWithApiService($this->buildFillBlanksQuestion('[cat] and [dog|cow] then [x||y]::1,2,0.5:200,200,200:0@0'));
        $xpath = $this->xpath($xml);

        self::assertSame(['fill_1', 'fill_3'], $this->attributes($xpath, '//q:textEntryInteraction', 'responseIdentifier'));
        self::assertSame(['fill_2'], $this->attributes($xpath, '//q:inlineChoiceInteraction', 'responseIdentifier'));
        self::assertSame(['fill_2_1', 'fill_2_2'], $this->attributes($xpath, '//q:inlineChoice', 'identifier'));
        self::assertSame(['dog', 'cow'], array_map('trim', $this->texts($xpath, '//q:inlineChoice')));
        self::assertSame(['string', 'identifier', 'string'], $this->attributes($xpath, '//q:responseDeclaration', 'baseType'));
        self::assertSame(['cat', 'fill_2_1', 'x||y'], $this->texts($xpath, '//q:correctResponse/q:value'));
        self::assertSame(['cat', 'fill_2_1', 'x', 'y'], $this->attributes($xpath, '//q:mapEntry', 'mapKey'));
        self::assertSame(['1', '2', '0.5', '0.5'], $this->attributes($xpath, '//q:mapEntry', 'mappedValue'));
        self::assertSame(
            ['http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_response'],
            $this->attributes($xpath, '//q:responseProcessing', 'template')
        );
    }

    public function testTextIsEscapedInTheXml(): void
    {
        $stored = '<p onclick="x()">]]><script>alert(1)</script> [a"b<c>&amp;||d]]]> [m"1|<i>m2</i>]</p>::1,2:200,200:0@0';
        $xml = $this->exportWithApiService($this->buildFillBlanksQuestion($stored));

        $this->assertValidQti($xml);
        self::assertStringNotContainsString('<script>', $xml);
        self::assertStringNotContainsString('<p onclick', $xml);
        $xpath = $this->xpath($xml);
        // Every text comes back as the stored HTML, escaped once: nothing is interpreted as markup.
        self::assertSame(['a"b<c>&amp;', 'd'], \array_slice($this->attributes($xpath, '//q:mapEntry', 'mapKey'), 0, 2));
        self::assertSame(
            '&lt;p onclick=&quot;x()&quot;&gt;]]&gt;&lt;script&gt;alert(1)&lt;/script&gt; ]]&gt; ',
            implode('', \array_slice($this->texts($xpath, '//q:itemBody/q:p/q:span'), 0, 2))
        );
        self::assertSame(['m&quot;1', '&lt;i&gt;m2&lt;/i&gt;'], $this->texts($xpath, '//q:inlineChoice'));
        self::assertCount(0, $xpath->query('//q:itemBody//q:script|//q:itemBody//q:i'));
    }

    public function testQuestionWithoutBlanksHasNoInteraction(): void
    {
        $xml = $this->exportWithApiService($this->buildFillBlanksQuestion('No blank here::'));

        self::assertStringNotContainsString('Interaction', $xml);
        self::assertStringNotContainsString('responseDeclaration', $xml);
    }

    private function buildFillBlanksQuestion(string $answerText): CQuizQuestion
    {
        $question = (new CQuizQuestion())
            ->setQuestion('Fill the blanks')
            ->setType(self::FILL_IN_BLANKS)
            ->setPonderation(1.0)
        ;
        $this->setIid($question, 42);
        $answer = (new CQuizAnswer())
            ->setQuestion($question)
            ->setAnswer($answerText)
            ->setCorrect(0)
            ->setPonderation(0.0)
            ->setPosition(1)
        ;
        $this->setIid($answer, 420);
        $question->getAnswers()->add($answer);

        return $question;
    }

    private function exportWithApiService(CQuizQuestion $question): string
    {
        $exportService = (new ReflectionClass(ExerciseQti2ExportService::class))->newInstanceWithoutConstructor();
        $exportQuestion = new ReflectionMethod(ExerciseQti2ExportService::class, 'exportQuestion');

        return $this->wrapItems($exportQuestion->invoke($exportService, $question));
    }

    /**
     * Response declarations and item body produced by the legacy ImsAnswerFillInBlanks.
     */
    private function exportWithLegacyExporter(string $answerText, string $extra): string
    {
        $answer = new class([['id' => 1, 'answer' => $answerText]]) extends ImsAnswerFillInBlanks {
            /**
             * @param array<int, array<string, mixed>> $rows
             */
            public function __construct(
                private readonly array $rows
            ) {}

            public function getAnswersList($decode = false)
            {
                return $this->rows;
            }
        };
        $question = (new ReflectionClass(FillBlanks::class))->newInstanceWithoutConstructor();
        $question->extra = $extra;

        return $answer->imsExportResponsesDeclaration('QST_42', $question)
            ."  <itemBody>\n".$answer->imsExportResponses('QST_42', 'Fill the blanks')."  </itemBody>\n";
    }

    private function wrapLegacyItem(string $content): string
    {
        return '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" identifier="QST_42" title="Fill the blanks" category="">'."\n"
            .$content
            .'  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_response"/>'."\n"
            .'</assessmentItem>'."\n";
    }

    /**
     * @return array{0: string, 1: float, 2: string} stored answer, question score and extra
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
        self::assertCount(1, $answers);
        self::assertSame(self::FILL_IN_BLANKS, $questions[0]->getType());

        return [$answers[0]->getAnswer(), $questions[0]->getPonderation(), (string) $questions[0]->getExtra()];
    }

    /**
     * @return array{0: string, 1: float, 2: string} answer string, score and extra the legacy importer would save
     */
    private function importWithLegacyImporter(string $xml): array
    {
        global $exerciseInfo;
        $exerciseInfo = ['name' => 'test', 'question' => []];

        self::assertTrue(qti_parse_file($xml));

        /** @var array<int, array<string, mixed>> $questions filled in by qti_parse_file() */
        $questions = array_values($exerciseInfo['question']);
        self::assertCount(1, $questions);
        self::assertSame(FIB, $questions[0]['type']);
        [$answer, $score] = buildFibAnswerString($questions[0]);

        return [$answer, (float) $score, !empty($questions[0]['fib_case_insensitive']) ? 'case:false' : ''];
    }

    private function wrapItems(string $items): string
    {
        return '<?xml version = "1.0" encoding = "UTF-8" standalone = "no"?>
<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">
<questestinterop>
<section ident = "EXO_1" title = "Fill in blanks export">
'.$items.'</section>
</questestinterop>
';
    }

    private function assertValidQti(string $xml): void
    {
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml, LIBXML_NONET), 'The export must be well-formed XML.');
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
        $property = new ReflectionProperty($entity, 'iid');
        $property->setValue($entity, $iid);
    }

    private function createZip(string $xml): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'qti2-fib-test-');
        self::assertIsString($path);
        $this->tempFiles[] = $path;
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('qti2export_1.xml', $xml);
        $zip->close();

        return new UploadedFile($path, 'qti2_export_1.zip', 'application/zip', null, true);
    }
}
