<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ExportService;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ImportService;
use Chamilo\CoreBundle\Service\Exercise\Qti2TrueFalseItem;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

use const LIBXML_NONET;

/**
 * Type 11/12/22 matchInteraction and type 29 multiple choice preserve Chamilo scoring and type.
 */
final class ExerciseQti2BatchACoreTest extends KernelTestCase
{
    /**
     * @var list<object>
     */
    private array $persisted = [];

    /**
     * @var list<string>
     */
    private array $files = [];
    private int $nextId = 900;

    public static function setUpBeforeClass(): void
    {
        require_once \dirname(__DIR__, 4).'/public/main/exercise/export/exercise_import.inc.php';
    }

    protected function setUp(): void
    {
        self::bootKernel();
        Container::setContainer(self::getContainer());
        $this->persisted = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function testTrueFalsePartialRoundTripPreservesSignedScoresAndOptions(): void
    {
        $xml = $this->export($this->trueFalseQuestion(11));
        self::assertStringContainsString(Qti2TrueFalseItem::LABEL_PARTIAL, $xml);
        self::assertStringContainsString('<matchInteraction ', $xml);
        $result = $this->import($xml);
        self::assertSame(1, $result['importedCount']);
        $question = $this->onlyQuestion();
        self::assertSame(11, $question->getType());
        self::assertSame('2.5:-1.25:0.5', $question->getExtra());
        self::assertSame(5.0, $question->getPonderation());
        self::assertSame([1, 2], array_map(static fn (CQuizAnswer $row): int => $row->getPosition(), $this->answers()));
        self::assertSame([1, 2], $this->optionPositionsForCorrectAnswers());
    }

    public function testTrueFalseCombinationRoundTripPreservesGlobalScore(): void
    {
        $xml = $this->export($this->trueFalseQuestion(12));
        self::assertStringContainsString(Qti2TrueFalseItem::LABEL_COMBINATION, $xml);
        self::assertStringContainsString('<match><variable identifier="QST_777"/><correct identifier="QST_777"/></match>', $xml);
        $result = $this->import($xml);
        self::assertSame(1, $result['importedCount']);
        $question = $this->onlyQuestion();
        self::assertSame(12, $question->getType());
        self::assertSame(4.5, $question->getPonderation());
        self::assertSame([4.5, 0.0], array_map(static fn (CQuizAnswer $row): float => $row->getPonderation(), $this->answers()));
        self::assertSame([1, 2], $this->optionPositionsForCorrectAnswers());
    }

    public function testDegreeCertaintyRoundTripPreservesSixCertaintyOptions(): void
    {
        $xml = $this->export($this->trueFalseQuestion(22));
        self::assertStringContainsString(Qti2TrueFalseItem::LABEL_CERTAINTY, $xml);
        self::assertStringContainsString('responseIdentifier="QST_777_CERTAINTY"', $xml);
        self::assertStringContainsString('identifier="certainty_100"', $xml);
        $result = $this->import($xml);
        self::assertSame(1, $result['importedCount']);
        $question = $this->onlyQuestion();
        self::assertSame(22, $question->getType());
        self::assertSame('2.5:-1.25:0', $question->getExtra());
        self::assertSame(5.0, $question->getPonderation());
        self::assertSame([1, 2], $this->optionPositionsForCorrectAnswers());
        self::assertCount(8, array_filter($this->persisted, static fn (object $entity): bool => $entity instanceof CQuizQuestionOption));
        global $exerciseInfo;
        $exerciseInfo = ['question' => []];
        parseQti2($this->wrap($xml));
        self::assertSame(22, $exerciseInfo['question']['QST_777']['type']);
        self::assertIsArray($exerciseInfo['question']['QST_777']['true_false_items']);
    }

    public function testDegreeCertaintyInvalidLevelIsSkippedWithoutDiscardingValidQuestion(): void
    {
        $xml = $this->export($this->trueFalseQuestion(22));
        $xml = str_replace('identifier="certainty_100"', 'identifier="certainty_invalid"', $xml);
        $result = $this->import($xml.$this->export($this->dropdownQuestion()));
        self::assertSame(1, $result['importedCount']);
        self::assertSame(1, $result['skippedCount']);
        self::assertSame(29, $this->onlyQuestion()->getType());
    }

    public function testDegreeCertaintyMissingSecondInteractionIsSkipped(): void
    {
        $xml = $this->export($this->trueFalseQuestion(22));
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml, LIBXML_NONET));
        foreach ($document->getElementsByTagName('matchInteraction') as $interaction) {
            if ('QST_777_CERTAINTY' === $interaction->getAttribute('responseIdentifier')) {
                $interaction->parentNode->removeChild($interaction);

                break;
            }
        }
        $xml = $document->saveXML($document->documentElement);
        $result = $this->import($xml.$this->export($this->dropdownQuestion()));
        self::assertSame(1, $result['importedCount']);
        self::assertSame(1, $result['skippedCount']);
        self::assertSame(29, $this->onlyQuestion()->getType());
    }

    public function testUnmarkedMatchStaysMatching(): void
    {
        $xml = str_replace(Qti2TrueFalseItem::LABEL_PARTIAL, 'unmarked', $this->export($this->trueFalseQuestion(11)));
        global $exerciseInfo;
        $exerciseInfo = ['question' => []];
        parseQti2($this->wrap($xml));
        self::assertSame(MATCHING, $exerciseInfo['question']['QST_777']['type']);
    }

    public function testMarkedTrueFalseRejectsMissingCorrectPair(): void
    {
        $xml = $this->export($this->trueFalseQuestion(11));
        $xml = str_replace('<value>statement_101 choice_true</value>', '<value>statement_101 choice_bogus</value>', $xml);
        $result = $this->import($xml.$this->export($this->dropdownQuestion()));
        self::assertSame(1, $result['importedCount']);
        self::assertSame(1, $result['skippedCount']);
        self::assertSame(29, $this->onlyQuestion()->getType());
    }

    public function testMultipleDropdownRoundTripPreservesAnswersAndPoints(): void
    {
        $xml = $this->export($this->dropdownQuestion());
        self::assertStringContainsString('chamilo:multiple-answer-dropdown', $xml);
        self::assertStringContainsString('cardinality="multiple"', $xml);
        self::assertStringContainsString('maxChoices="0"', $xml);
        self::assertStringContainsString('<mapResponse identifier="QST_788"/>', $xml);
        $this->import($xml);
        self::assertSame(29, $this->onlyQuestion()->getType());
        self::assertSame(3.5, $this->onlyQuestion()->getPonderation());
        self::assertSame([3.5, 0.0], array_map(static fn (CQuizAnswer $row): float => $row->getPonderation(), $this->answers()));
    }

    public function testUnmarkedDropdownFallsBackToMultipleAnswer(): void
    {
        $xml = str_replace('chamilo:multiple-answer-dropdown', 'unmarked', $this->export($this->dropdownQuestion()));
        $this->import($xml);
        self::assertSame(2, $this->onlyQuestion()->getType());
    }

    private function trueFalseQuestion(int $type): CQuizQuestion
    {
        $question = (new CQuizQuestion())->setQuestion('Mark true or false')->setType($type)->setExtra(22 === $type ? '2.5:-1.25:0' : '2.5:-1.25:0.5')->setPonderation(12 === $type ? 4.5 : 5.0);
        $this->id($question, 777);
        foreach ((22 === $type
            ? [1 => 'True', 2 => 'False', 3 => '50%', 4 => '60%', 5 => '70%', 6 => '80%', 7 => '90%', 8 => '100%']
            : [1 => 'True', 2 => 'False', 3 => "Don't know"]) as $position => $title) {
            $option = (new CQuizQuestionOption())->setQuestion($question)->setTitle($title)->setPosition($position);
            $this->id($option, 500 + $position);
            $question->getOptions()->add($option);
        }
        foreach ([['Earth is round', 501, 4.5], ['Earth is flat', 502, 0.0]] as $position => [$text, $correct, $score]) {
            $answer = (new CQuizAnswer())->setQuestion($question)->setAnswer($text)->setCorrect($correct)->setPonderation($score)->setPosition($position + 1);
            $this->id($answer, 101 + $position);
            $question->getAnswers()->add($answer);
        }

        return $question;
    }

    private function dropdownQuestion(): CQuizQuestion
    {
        $question = (new CQuizQuestion())->setQuestion('Select all capitals')->setType(29)->setPonderation(3.5);
        $this->id($question, 788);
        foreach ([['Lima', 1, 3.5], ['Cuzco', 0, 0.0]] as $position => [$text, $correct, $score]) {
            $answer = (new CQuizAnswer())->setQuestion($question)->setAnswer($text)->setCorrect($correct)->setPonderation($score)->setPosition($position + 1);
            $this->id($answer, 201 + $position);
            $question->getAnswers()->add($answer);
        }

        return $question;
    }

    private function export(CQuizQuestion $question): string
    {
        $service = (new ReflectionClass(ExerciseQti2ExportService::class))->newInstanceWithoutConstructor();

        return (new ReflectionMethod(ExerciseQti2ExportService::class, 'exportQuestion'))->invoke($service, $question);
    }

    /**
     * @return array{importedCount: int, skippedCount: int, errors: array<int, string>}
     */
    private function import(string $items): array
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(function (object $entity): void { $this->persisted[] = $entity; });
        $manager->method('flush')->willReturnCallback(function (): void {
            foreach ($this->persisted as $entity) {
                if ($entity instanceof CQuizQuestionOption && null === $entity->getIid()) {
                    $this->id($entity, $this->nextId++);
                }
            }
        });
        $service = new ExerciseQti2ImportService($manager, new CQuizQuestionCategoryRepository($this->createMock(ManagerRegistry::class)));
        $result = $service->import($this->zip($this->wrap($items)), new Course(), null);

        return ['importedCount' => $result['importedCount'], 'skippedCount' => $result['skippedCount'], 'errors' => $result['errors']];
    }

    private function onlyQuestion(): CQuizQuestion
    {
        $questions = array_values(array_filter($this->persisted, static fn (object $entity): bool => $entity instanceof CQuizQuestion));
        self::assertCount(1, $questions);

        return $questions[0];
    }

    /**
     * @return list<CQuizAnswer>
     */
    private function answers(): array
    {
        $answers = array_values(array_filter($this->persisted, static fn (object $entity): bool => $entity instanceof CQuizAnswer));
        usort($answers, static fn (CQuizAnswer $a, CQuizAnswer $b): int => $a->getPosition() <=> $b->getPosition());

        return $answers;
    }

    /**
     * @return list<int>
     */
    private function optionPositionsForCorrectAnswers(): array
    {
        $options = [];
        foreach ($this->persisted as $entity) {
            if ($entity instanceof CQuizQuestionOption) {
                $options[(int) $entity->getIid()] = (int) $entity->getPosition();
            }
        }

        return array_map(static fn (CQuizAnswer $answer): int => $options[(int) $answer->getCorrect()] ?? -1, $this->answers());
    }

    private function zip(string $xml): UploadedFile
    {
        $file = tempnam(sys_get_temp_dir(), 'qti-ab-core-');
        self::assertIsString($file);
        $this->files[] = $file;
        $zip = new ZipArchive();
        self::assertTrue(true === $zip->open($file, ZipArchive::OVERWRITE));
        $zip->addFromString('bank.xml', $xml);
        $zip->close();

        return new UploadedFile($file, 'qti_batch_a_core.zip', 'application/zip', null, true);
    }

    private function wrap(string $items): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">'
            .'<questestinterop><section ident="EXO_1" title="QTI2 Batch A">'.$items.'</section></questestinterop>';
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($xml, LIBXML_NONET));

        return $xml;
    }

    private function id(object $entity, int $id): void
    {
        (new ReflectionProperty($entity, 'iid'))->setValue($entity, $id);
    }
}
