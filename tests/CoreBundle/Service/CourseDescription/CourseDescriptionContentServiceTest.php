<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\CourseDescription;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Service\CourseDescription\CourseDescriptionContentService;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class CourseDescriptionContentServiceTest extends KernelTestCase
{
    private CourseDescriptionContentService $service;
    private Course $course;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));

        $entityManager = $container->get(EntityManagerInterface::class);
        $course = $entityManager->getRepository(Course::class)->find(2);
        $user = $entityManager->getRepository(User::class)->find(1);
        if (!$course instanceof Course || !$user instanceof User) {
            self::markTestSkipped('Course #2 / User #1 fixtures are not present in this DB.');
        }

        $this->service = $container->get(CourseDescriptionContentService::class);
        $this->course = $course;

        $container->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($user, 'api', $user->getRoles())
        );
    }

    public function testCreatingTheSameStandardTypeTwiceUpdatesInPlace(): void
    {
        $title = 'Objectives '.bin2hex(random_bytes(6));

        $created = $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_OBJECTIVES,
            $title,
            '<p>Initial objectives content.</p>',
            null,
        );

        self::assertTrue($created['created']);
        self::assertFalse($created['updated_existing']);
        self::assertSame($title, $created['title']);

        $updatedTitle = 'Objectives updated '.bin2hex(random_bytes(6));
        $updated = $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_OBJECTIVES,
            $updatedTitle,
            '<p>Revised objectives content.</p>',
            null,
        );

        self::assertFalse($updated['created']);
        self::assertTrue($updated['updated_existing']);
        self::assertSame($created['description_id'], $updated['description_id']);
        self::assertSame($updatedTitle, $updated['title']);
        self::assertStringContainsString('Revised objectives content.', $updated['content']);
    }

    public function testCustomTypeAlwaysCreatesANewItem(): void
    {
        $titleA = 'Custom A '.bin2hex(random_bytes(6));
        $titleB = 'Custom B '.bin2hex(random_bytes(6));

        $first = $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_CUSTOM,
            $titleA,
            '<p>First custom item.</p>',
            null,
        );
        $second = $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_CUSTOM,
            $titleB,
            '<p>Second custom item.</p>',
            null,
        );

        self::assertTrue($first['created']);
        self::assertTrue($second['created']);
        self::assertNotSame($first['description_id'], $second['description_id']);
    }

    public function testTemplateReflectsExistingContent(): void
    {
        $title = 'Methodology '.bin2hex(random_bytes(6));
        $created = $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_METHODOLOGY,
            $title,
            '<p>Methodology content for the template check.</p>',
            null,
        );

        $template = $this->service->getTemplate($this->course);

        $methodologySection = null;
        foreach ($template['sections'] as $section) {
            if (CCourseDescription::TYPE_METHODOLOGY === $section['description_type']) {
                $methodologySection = $section;
            }
        }

        self::assertNotNull($methodologySection);
        self::assertTrue($methodologySection['exists']);
        self::assertSame($created['description_id'], $methodologySection['description_id']);
        self::assertSame($title, $methodologySection['title']);
        self::assertNotSame('', $methodologySection['guiding_question']);
    }

    public function testEditRequiresAtLeastOneChange(): void
    {
        $created = $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_TOPICS,
            'Topics '.bin2hex(random_bytes(6)),
            '<p>Topics content.</p>',
            null,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->service->edit($this->course, (int) $created['description_id'], null, null, null, null);
    }

    public function testEditByTypeUpdatesContentAndDeleteRemovesTheItem(): void
    {
        $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_RESOURCES,
            'Resources '.bin2hex(random_bytes(6)),
            '<p>Original resources content.</p>',
            null,
        );

        $edited = $this->service->edit(
            $this->course,
            null,
            CCourseDescription::TYPE_RESOURCES,
            '<p>Updated resources content.</p>',
            'Resources renamed '.bin2hex(random_bytes(6)),
            null,
        );

        self::assertContains('content', $edited['changed_fields']);
        self::assertContains('title', $edited['changed_fields']);
        self::assertStringContainsString('Updated resources content.', $edited['content']);

        $deleted = $this->service->delete($this->course, (int) $edited['description_id'], null);
        self::assertTrue($deleted['deleted']);

        $this->expectException(InvalidArgumentException::class);
        $this->service->delete($this->course, null, CCourseDescription::TYPE_RESOURCES);
    }

    public function testDeletingACustomItemRequiresAnExplicitId(): void
    {
        $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_CUSTOM,
            'Custom '.bin2hex(random_bytes(6)),
            '<p>Custom content.</p>',
            null,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->service->delete($this->course, null, CCourseDescription::TYPE_CUSTOM);
    }
}
