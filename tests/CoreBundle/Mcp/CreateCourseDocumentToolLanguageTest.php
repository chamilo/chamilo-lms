<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Mcp\CreateCourseDocumentTool;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateCourseDocumentToolLanguageTest extends KernelTestCase
{
    public function testResolvesCourseDefaultAndTitlesAcrossRealCourses(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $tool = $container->get(CreateCourseDocumentTool::class);

        $reflection = new ReflectionMethod($tool, 'resolveLanguageIsoCode');
        $reflection->setAccessible(true);

        foreach ([1 => 'es', 2 => 'en_US', 4 => 'fr_FR'] as $courseId => $expectedCourseLanguage) {
            $course = $em->getRepository(Course::class)->find($courseId);
            if (!$course instanceof Course) {
                self::markTestSkipped("Course #$courseId does not exist in this DB, skipping.");
            }

            self::assertSame($expectedCourseLanguage, $course->getCourseLanguage());

            // No language requested: falls back to the course's own language.
            self::assertSame($expectedCourseLanguage, $reflection->invoke($tool, $course, null));
            self::assertSame($expectedCourseLanguage, $reflection->invoke($tool, $course, ''));

            // A specific language requested, by title: resolves to its isocode
            // regardless of the course's own language.
            self::assertSame('es', $reflection->invoke($tool, $course, 'Spanish'));
            self::assertSame('fr_FR', $reflection->invoke($tool, $course, 'French'));
            self::assertSame('en_US', $reflection->invoke($tool, $course, 'English'));

            // A specific language requested, by isocode: still works directly.
            self::assertSame('es', $reflection->invoke($tool, $course, 'es'));

            // Unknown language: clear error, not the generic "Invalid resource language".
            try {
                $reflection->invoke($tool, $course, 'not-a-real-language');
                self::fail('Expected an InvalidArgumentException for an unknown language.');
            } catch (InvalidArgumentException $exception) {
                self::assertStringContainsString('not-a-real-language', $exception->getMessage());
            }
        }
    }
}
