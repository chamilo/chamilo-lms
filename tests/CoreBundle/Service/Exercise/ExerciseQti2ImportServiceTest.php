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
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * Matching questions used to be detected by the QTI2 importer and then silently dropped,
 * so a Chamilo QTI2 export containing them could not be imported back (issue #1377).
 */
final class ExerciseQti2ImportServiceTest extends TestCase
{
    private const MATCHING = 4;

    /**
     * @var array<int, object>
     */
    private array $persisted = [];

    private int $nextIid = 1000;

    /**
     * @var array<int, string>
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    /**
     * @dataProvider provideResponseDeclarations
     */
    public function testImportsMatchingQuestionWithOptionsAssociationsAndScores(string $cardinality, string $baseType): void
    {
        $xml = $this->wrapItems($this->buildLegacyMatchingItem($cardinality, $baseType));

        $result = $this->createImportService()->import($this->createZip($xml), new Course(), null);

        self::assertSame(1, $result['importedCount']);
        self::assertSame(0, $result['skippedCount']);

        $question = $this->getSingleQuestion();
        self::assertSame(self::MATCHING, $question->getType());
        self::assertSame('Capitals', $question->getQuestion());
        self::assertSame(6.0, $question->getPonderation());

        $answers = $this->getAnswers($question);
        self::assertCount(7, $answers);

        // Options (second simpleMatchSet) come first, with correct = 0, as Chamilo stores them.
        $options = \array_slice($answers, 0, 4);
        self::assertSame(['Rome', 'Paris', 'Madrid', 'Lisbon'], array_map(static fn (CQuizAnswer $a): string => $a->getAnswer(), $options));
        foreach ($options as $index => $option) {
            self::assertSame(0, $option->getCorrect());
            self::assertSame($index + 1, $option->getPosition());
        }

        // Items (first simpleMatchSet) point to the iid of the option they must be associated with.
        $optionIids = [];
        foreach ($options as $option) {
            $optionIids[$option->getAnswer()] = $option->getIid();
        }
        $items = \array_slice($answers, 4);
        self::assertSame(
            [
                ['France', $optionIids['Paris'], 1.0, 5],
                ['Spain', $optionIids['Madrid'], 2.0, 6],
                ['<b>Italy</b>', $optionIids['Rome'], 3.0, 7],
            ],
            array_map(
                static fn (CQuizAnswer $a): array => [$a->getAnswer(), $a->getCorrect(), $a->getPonderation(), $a->getPosition()],
                $items
            )
        );
    }

    /**
     * The legacy exporter (ImsAnswerMatching) used cardinality="single"; QTI 2.1 and the current
     * exporters use cardinality="multiple" with directedPair values. Both must import the same way.
     */
    public static function provideResponseDeclarations(): iterable
    {
        yield 'QTI 2.1 directedPair' => ['multiple', 'directedPair'];

        yield 'legacy Chamilo identifier' => ['single', 'identifier'];
    }

    public function testMatchingQuestionRoundTripsThroughTheQti2Export(): void
    {
        $original = $this->buildMatchingQuestion();
        $exportService = (new ReflectionClass(ExerciseQti2ExportService::class))->newInstanceWithoutConstructor();
        $exportQuestion = new ReflectionMethod(ExerciseQti2ExportService::class, 'exportQuestion');
        $itemXml = $exportQuestion->invoke($exportService, $original);

        $this->createImportService()->import($this->createZip($this->wrapItems($itemXml)), new Course(), null);

        $imported = $this->getSingleQuestion();
        self::assertSame(self::MATCHING, $imported->getType());
        self::assertSame($original->getQuestion(), $imported->getQuestion());
        self::assertSame($original->getPonderation(), $imported->getPonderation());
        self::assertSame($this->describeMatching($original), $this->describeMatching($imported));
    }

    public function testMatchingQuestionWithoutAssociationsIsReportedAsSkipped(): void
    {
        // Older exports wrote matchInteraction with empty match sets and no correct response.
        $emptyMatching = '<assessmentItem identifier="QST_2" title="Empty matching" category="">
  <responseDeclaration identifier="QST_2" cardinality="single" baseType="identifier">
    <correctResponse>
    </correctResponse>
    <mapping>
    </mapping>
  </responseDeclaration>
  <itemBody>
<matchInteraction responseIdentifier="QST_2" maxAssociations="0">
  <simpleMatchSet>
  </simpleMatchSet>
  <simpleMatchSet>
  </simpleMatchSet>
</matchInteraction>
  </itemBody>
</assessmentItem>';

        $result = $this->createImportService()->import(
            $this->createZip($this->wrapItems($this->buildLegacyMatchingItem('multiple', 'directedPair').$emptyMatching)),
            new Course(),
            null
        );

        self::assertSame(1, $result['importedCount']);
        self::assertSame(1, $result['skippedCount']);
        self::assertSame(['QTI question QST_2 skipped because it has no matching associations.'], $result['errors']);
    }

    private function createImportService(): ExerciseQti2ImportService
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            $this->persisted[] = $entity;
        });
        // Simulate the database assigning iids on flush, which matching items rely on.
        $entityManager->method('flush')->willReturnCallback(function (): void {
            foreach ($this->persisted as $entity) {
                if ($entity instanceof CQuizAnswer && null === $entity->getIid()) {
                    $this->setIid($entity, $this->nextIid++);
                }
            }
        });

        return new ExerciseQti2ImportService(
            $entityManager,
            new CQuizQuestionCategoryRepository($this->createMock(ManagerRegistry::class)),
        );
    }

    /**
     * Same structure as ImsAnswerMatching output: items in the first simpleMatchSet,
     * options in the second, "left_X right_Y" associations scored in the mapping.
     */
    private function buildLegacyMatchingItem(string $cardinality, string $baseType): string
    {
        return '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 imsqti_v2p1.xsd"
                identifier="QST_1"
                title = "Capitals"
                category = ""
        >
  <responseDeclaration identifier="QST_1" cardinality="'.$cardinality.'" baseType="'.$baseType.'">
    <correctResponse>
      <value>left_0 right_1</value>
      <value>left_1 right_2</value>
      <value>left_2 right_0</value>
    </correctResponse>
    <mapping>
          <mapEntry mapKey="left_0 right_1" mappedValue="1"/>
          <mapEntry mapKey="left_1 right_2" mappedValue="2"/>
          <mapEntry mapKey="left_2 right_0" mappedValue="3"/>
    </mapping>
  </responseDeclaration>
  <itemBody>
<matchInteraction responseIdentifier="QST_1" maxAssociations="4">
      <prompt><![CDATA[Capitals]]></prompt>
  <simpleMatchSet>
                <simpleAssociableChoice identifier="left_0" >
                    <![CDATA[France]]>
                </simpleAssociableChoice>
                <simpleAssociableChoice identifier="left_1" >
                    <![CDATA[Spain]]>
                </simpleAssociableChoice>
                <simpleAssociableChoice identifier="left_2" >
                    <![CDATA[&lt;b&gt;Italy&lt;/b&gt;]]>
                </simpleAssociableChoice>
  </simpleMatchSet>
  <simpleMatchSet>
<simpleAssociableChoice identifier="right_0" >
                        <![CDATA[Rome]]>
                        </simpleAssociableChoice>
<simpleAssociableChoice identifier="right_1" >
                        <![CDATA[Paris]]>
                        </simpleAssociableChoice>
<simpleAssociableChoice identifier="right_2" >
                        <![CDATA[Madrid]]>
                        </simpleAssociableChoice>
<simpleAssociableChoice identifier="right_3" >
                        <![CDATA[Lisbon]]>
                        </simpleAssociableChoice>
  </simpleMatchSet>
</matchInteraction>
  </itemBody>
  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_correct"/>
</assessmentItem>
';
    }

    private function wrapItems(string $items): string
    {
        return '<?xml version = "1.0" encoding = "UTF-8" standalone = "no"?>
<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">
<questestinterop>
<section
            ident = "EXO_1"
            title = "Matching export"
        >
<presentation_material><flow_mat><material>
  <mattext><![CDATA[]]></mattext>
</material></flow_mat></presentation_material>
<selection_ordering sequence_type="Normal">
  <selection />
</selection_ordering>
'.$items.'</section>
</questestinterop>
';
    }

    private function buildMatchingQuestion(): CQuizQuestion
    {
        $question = (new CQuizQuestion())
            ->setQuestion('Match the authors')
            ->setType(self::MATCHING)
            ->setPonderation(7.5)
        ;
        $this->setIid($question, 77);

        $rows = [
            // [iid, text, correct (option iid or 0), score]
            [11, 'Don Quixote', 0, 0.0],
            [12, 'Hamlet', 0, 0.0],
            [13, 'Faust', 0, 0.0],
            [14, 'Cervantes', 11, 2.5],
            [15, 'Shakespeare', 12, 5.0],
        ];
        foreach ($rows as $position => [$iid, $text, $correct, $score]) {
            $answer = (new CQuizAnswer())
                ->setQuestion($question)
                ->setAnswer($text)
                ->setCorrect($correct)
                ->setPonderation($score)
                ->setPosition($position + 1)
            ;
            $this->setIid($answer, $iid);
            $question->getAnswers()->add($answer);
        }

        return $question;
    }

    /**
     * Describes a matching question independently of iids: options in order, then each item
     * with the text of its correct option and its score.
     *
     * @return array{options: array<int, string>, items: array<int, array{0: string, 1: string, 2: float}>}
     */
    private function describeMatching(CQuizQuestion $question): array
    {
        $answers = $this->getAnswers($question);
        $optionTexts = [];
        $items = [];
        foreach ($answers as $answer) {
            if (0 === (int) $answer->getCorrect()) {
                $optionTexts[(int) $answer->getIid()] = $answer->getAnswer();
            }
        }
        foreach ($answers as $answer) {
            if (0 !== (int) $answer->getCorrect()) {
                $items[] = [$answer->getAnswer(), $optionTexts[(int) $answer->getCorrect()] ?? '', $answer->getPonderation()];
            }
        }

        return ['options' => array_values($optionTexts), 'items' => $items];
    }

    private function getSingleQuestion(): CQuizQuestion
    {
        $questions = array_values(array_filter($this->persisted, static fn (object $entity): bool => $entity instanceof CQuizQuestion));
        self::assertCount(1, $questions);

        return $questions[0];
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getAnswers(CQuizQuestion $question): array
    {
        $answers = [];
        foreach ($question->getAnswers() as $answer) {
            $answers[] = $answer;
        }
        foreach ($this->persisted as $entity) {
            if ($entity instanceof CQuizAnswer && $entity->getQuestion() === $question && !\in_array($entity, $answers, true)) {
                $answers[] = $entity;
            }
        }
        usort($answers, static fn (CQuizAnswer $left, CQuizAnswer $right): int => $left->getPosition() <=> $right->getPosition());

        return $answers;
    }

    private function setIid(object $entity, int $iid): void
    {
        $property = new ReflectionProperty($entity, 'iid');
        $property->setValue($entity, $iid);
    }

    private function createZip(string $xml): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'qti2-test-');
        $this->tempFiles[] = $path;
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('qti2export_1.xml', $xml);
        $zip->close();

        return new UploadedFile($path, 'qti2_export_1.zip', 'application/zip', null, true);
    }
}
