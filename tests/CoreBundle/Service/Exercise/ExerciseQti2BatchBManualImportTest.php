<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\Exercise\ExerciseQti2ImportService;
use Chamilo\CoreBundle\Service\Exercise\Qti2SpecialQuestionItem;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use DOMDocument;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

use const LIBXML_NONET;

/**
 * Type 13 and 23 must keep their manual grading score and never become multiple choice.
 */
final class ExerciseQti2BatchBManualImportTest extends KernelTestCase
{
    /**
     * @var list<string>
     */
    private array $files = [];

    protected function setUp(): void
    {
        self::bootKernel();
        Container::setContainer(self::getContainer());
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function testOralIsImportedAsManuallyGradedQuestion(): void
    {
        $this->assertImportedManualQuestion(13, 6.5);
    }

    public function testUploadIsImportedAsManuallyGradedQuestion(): void
    {
        $this->assertImportedManualQuestion(23, 10.0);
    }

    private function assertImportedManualQuestion(int $type, float $score): void
    {
        $question = (new CQuizQuestion())->setQuestion('Submit your answer')->setDescription('Teacher will review')->setType($type)->setPonderation($score);
        $xml = '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1" identifier="B_1" title="Submit your answer" label="'
            .Qti2SpecialQuestionItem::label($type).'">'
            .Qti2SpecialQuestionItem::write($question, 'B_1').'</assessmentItem>';
        $content = '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">'
            .'<questestinterop><section ident="EXO_1" title="QTI2 Batch B">'.$xml.'</section></questestinterop>';
        $document = new DOMDocument();
        self::assertTrue($document->loadXML($content, LIBXML_NONET));
        $file = (string) tempnam(sys_get_temp_dir(), 'qti-b-manual-');
        $this->files[] = $file;
        $zip = new ZipArchive();
        self::assertTrue($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('bank.xml', $content);
        self::assertTrue($zip->close());
        $stored = [];
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(static function (object $entity) use (&$stored): void { $stored[] = $entity; });
        $service = new ExerciseQti2ImportService($manager, new CQuizQuestionCategoryRepository($this->createMock(ManagerRegistry::class)));
        $result = $service->import(new UploadedFile($file, 'qti-b-manual.zip', 'application/zip', null, true), new Course(), null);
        self::assertSame(1, $result['importedCount']);
        self::assertSame(0, $result['skippedCount']);
        $questions = array_values(array_filter($stored, static fn (object $object): bool => $object instanceof CQuizQuestion));
        self::assertCount(1, $questions);
        self::assertSame($type, $questions[0]->getType());
        self::assertSame($score, (float) $questions[0]->getPonderation());
    }
}
