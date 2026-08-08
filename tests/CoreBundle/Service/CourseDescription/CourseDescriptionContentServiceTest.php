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

    public function testReadReturnsFullHtmlContentForAllAndForASingleSection(): void
    {
        $title = 'Assessment '.bin2hex(random_bytes(6));
        $created = $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_ASSESSMENT,
            $title,
            '<p>Assessment body for the read check.</p>',
            null,
        );

        $all = $this->service->read($this->course);
        self::assertGreaterThanOrEqual(1, $all['total']);
        self::assertSame($this->course->getId(), $all['course_id']);
        self::assertSame('full', $all['mode']);

        $found = null;
        foreach ($all['items'] as $item) {
            if ($item['description_id'] === $created['description_id']) {
                $found = $item;
            }
        }
        self::assertNotNull($found);
        self::assertStringContainsString('Assessment body for the read check.', $found['content']);
        self::assertSame($title, $found['title']);
        self::assertSame(CCourseDescription::TYPE_ASSESSMENT, $found['description_type']);

        $byType = $this->service->read($this->course, null, CCourseDescription::TYPE_ASSESSMENT);
        self::assertSame(1, $byType['total']);
        self::assertSame($created['description_id'], $byType['items'][0]['description_id']);
        self::assertStringContainsString('Assessment body for the read check.', $byType['items'][0]['content']);

        $byId = $this->service->read($this->course, (int) $created['description_id'], null);
        self::assertSame(1, $byId['total']);
        self::assertSame($created['description_id'], $byId['items'][0]['description_id']);
    }

    public function testReadInventoryAndSourceModesOmitFullMultiLangBody(): void
    {
        $title = 'Methodology '.bin2hex(random_bytes(6));
        $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_METHODOLOGY,
            $title,
            '<p>Source methodology content for translation.</p>',
            null,
        );

        $inventory = $this->service->read(
            $this->course,
            null,
            CCourseDescription::TYPE_METHODOLOGY,
            'inventory',
        );
        self::assertSame('inventory', $inventory['mode']);
        self::assertSame(1, $inventory['total']);
        self::assertArrayNotHasKey('content', $inventory['items'][0]);
        self::assertArrayHasKey('content_sha256', $inventory['items'][0]);
        self::assertArrayHasKey('present_languages', $inventory['items'][0]);

        $source = $this->service->read(
            $this->course,
            null,
            CCourseDescription::TYPE_METHODOLOGY,
            'source',
        );
        self::assertSame('source', $source['mode']);
        self::assertArrayNotHasKey('content', $source['items'][0]);
        self::assertArrayHasKey('source_html', $source['items'][0]);
        self::assertStringContainsString(
            'Source methodology content for translation.',
            $source['items'][0]['source_html'],
        );
    }

    public function testUpsertLanguageAppendsVariantWithoutFullRewrite(): void
    {
        $title = 'Topics '.bin2hex(random_bytes(6));
        $created = $this->service->createOrUpdate(
            $this->course,
            CCourseDescription::TYPE_TOPICS,
            $title,
            '<p>English topics body.</p>',
            null,
        );

        $source = $this->service->read(
            $this->course,
            (int) $created['description_id'],
            null,
            'source',
            'en',
        );
        $sha = $source['items'][0]['content_sha256'];

        $upserted = $this->service->upsertLanguage(
            $this->course,
            (int) $created['description_id'],
            null,
            'es',
            '<p>Temas en español.</p>',
            'upsert',
            'en',
            $sha,
        );

        self::assertTrue($upserted['updated']);
        self::assertSame('created', $upserted['action']);
        self::assertSame('es', $upserted['language']);
        self::assertArrayNotHasKey('content', $upserted);
        self::assertNotEmpty($upserted['present_languages']);

        $full = $this->service->read(
            $this->course,
            (int) $created['description_id'],
            null,
            'full',
        );
        $content = $full['items'][0]['content'];
        self::assertStringContainsString('English topics body.', $content);
        self::assertStringContainsString('Temas en español.', $content);
        self::assertStringContainsString('mce-translatehtml', $content);
    }
}
