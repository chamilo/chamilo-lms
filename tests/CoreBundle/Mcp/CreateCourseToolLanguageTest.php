<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Mcp\CreateCourseTool;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateCourseToolLanguageTest extends KernelTestCase
{
    public function testResolvesUserLocaleByDefaultAndRequestedLanguageByTitleOrCode(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $tool = $container->get(CreateCourseTool::class);

        $user = $em->getRepository(User::class)->find(1);
        if (!$user instanceof User) {
            self::markTestSkipped('User #1 does not exist in this DB, skipping.');
        }

        $reflection = new ReflectionMethod($tool, 'resolveCourseLanguage');
        $reflection->setAccessible(true);

        // No language requested: defaults to the user's own locale.
        $user->setLocale('en_US');
        self::assertSame('en_US', $reflection->invoke($tool, null, $user));

        $user->setLocale('es');
        self::assertSame('es', $reflection->invoke($tool, null, $user));

        // No language requested, user locale not a real/available language:
        // falls back to the platform default rather than throwing outright.
        $user->setLocale('not-a-real-locale');
        $fallback = $reflection->invoke($tool, null, $user);
        self::assertIsString($fallback);
        self::assertNotSame('', $fallback);

        $user->setLocale('en_US');

        // A specific language requested, by title: resolves regardless of the user's own locale.
        self::assertSame('es', $reflection->invoke($tool, 'Spanish', $user));
        self::assertSame('fr_FR', $reflection->invoke($tool, 'French', $user));

        // A specific language requested, by isocode: still works directly.
        self::assertSame('es', $reflection->invoke($tool, 'es', $user));

        // A bare ISO prefix resolves to the available specific variant.
        self::assertSame('en_US', $reflection->invoke($tool, 'en', $user));

        // Unknown language: clear error.
        try {
            $reflection->invoke($tool, 'not-a-real-language', $user);
            self::fail('Expected an InvalidArgumentException for an unknown language.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('not-a-real-language', $exception->getMessage());
        }
    }
}
