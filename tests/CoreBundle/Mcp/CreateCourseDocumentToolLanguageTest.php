<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Mcp;

use Chamilo\CoreBundle\Mcp\CreateCourseDocumentTool;
use Chamilo\Tests\ChamiloTestTrait;
use InvalidArgumentException;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateCourseDocumentToolLanguageTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testResolvesCourseDefaultAndTitles(): void
    {
        self::bootKernel();

        $tool = self::getContainer()->get(CreateCourseDocumentTool::class);

        $reflection = new ReflectionMethod($tool, 'resolveLanguageIsoCode');
        $reflection->setAccessible(true);

        foreach (['es', 'en_US', 'fr_FR'] as $courseLanguage) {
            $course = $this->createCourse('Language '.$courseLanguage);
            $course->setCourseLanguage($courseLanguage);
            $this->getEntityManager()->flush();

            // No language requested: falls back to the course's own language.
            self::assertSame($courseLanguage, $reflection->invoke($tool, $course, null));
            self::assertSame($courseLanguage, $reflection->invoke($tool, $course, ''));

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
