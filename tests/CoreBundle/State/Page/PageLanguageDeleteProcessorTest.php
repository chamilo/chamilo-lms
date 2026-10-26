<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Page;

use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\ApiResource\Page\PageLanguageDeleteAction;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\State\Page\PageLanguageDeleteProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PageLanguageDeleteProcessorTest extends TestCase
{
    public function testDeletesOnlyExplicitlySelectedLanguagesInOneTransaction(): void
    {
        $url = $this->url(1);
        $category = $this->category(10);
        $english = $this->page(11, 'en_US', $url, $category);
        $spanish = $this->page(12, 'es', $url, $category);
        $unselected = $this->page(13, 'fr', $url, $category);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturnCallback(
            static fn (string $class, mixed $id) => [11 => $english, 12 => $spanish, 13 => $unselected][$id] ?? null
        );
        $removed = [];
        $entityManager->expects(self::once())->method('beginTransaction');
        $entityManager->expects(self::exactly(2))->method('remove')->willReturnCallback(
            static function (Page $page) use (&$removed): void {
                $removed[] = $page->getId();
            }
        );
        $entityManager->expects(self::once())->method('flush');
        $entityManager->expects(self::once())->method('commit');
        $entityManager->expects(self::never())->method('rollback');

        $result = $this->processor($entityManager, $url)->process(
            $this->payload([11, 12]),
            new Post(),
            ['pageId' => '11'],
        );

        self::assertSame(2, $result->deletedCount);
        self::assertSame([11, 12], $removed);
    }

    public function testRefusesPagesOutsideTheCurrentPlatformBeforeDeletion(): void
    {
        $url = $this->url(1);
        $otherUrl = $this->url(2);
        $category = $this->category(10);
        $english = $this->page(11, 'en', $url, $category);
        $spanish = $this->page(12, 'es', $otherUrl, $category);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturnCallback(
            static fn (string $class, mixed $id) => [11 => $english, 12 => $spanish][$id] ?? null
        );
        $entityManager->expects(self::never())->method('remove');
        $entityManager->expects(self::never())->method('flush');

        $this->expectException(NotFoundHttpException::class);
        $this->processor($entityManager, $url)->process($this->payload([11, 12]), new Post(), ['pageId' => '11']);
    }

    public function testRefusesPagesFromAnotherCategoryBeforeDeletion(): void
    {
        $url = $this->url(1);
        $english = $this->page(11, 'en', $url, $this->category(10));
        $spanish = $this->page(12, 'es', $url, $this->category(20));
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturnCallback(
            static fn (string $class, mixed $id) => [11 => $english, 12 => $spanish][$id] ?? null
        );
        $entityManager->expects(self::never())->method('remove');

        $this->expectException(NotFoundHttpException::class);
        $this->processor($entityManager, $url)->process($this->payload([11, 12]), new Post(), ['pageId' => '11']);
    }

    public function testRefusesSameLanguageOnlyWithoutMutatingAnything(): void
    {
        $url = $this->url(1);
        $category = $this->category(10);
        $first = $this->page(11, 'en', $url, $category);
        $second = $this->page(12, 'en', $url, $category);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturnCallback(
            static fn (string $class, mixed $id) => [11 => $first, 12 => $second][$id] ?? null
        );
        $entityManager->expects(self::never())->method('remove');
        $entityManager->expects(self::never())->method('flush');

        $this->expectException(BadRequestHttpException::class);
        $this->processor($entityManager, $url)->process($this->payload([11, 12]), new Post(), ['pageId' => '11']);
    }

    public function testRollsBackWhenDatabaseDeletionFails(): void
    {
        $url = $this->url(1);
        $category = $this->category(10);
        $english = $this->page(11, 'en', $url, $category);
        $spanish = $this->page(12, 'es', $url, $category);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('find')->willReturnCallback(
            static fn (string $class, mixed $id) => [11 => $english, 12 => $spanish][$id] ?? null
        );
        $entityManager->expects(self::once())->method('beginTransaction');
        $entityManager->expects(self::once())->method('flush')->willThrowException(new RuntimeException('Simulated DB error'));
        $entityManager->expects(self::never())->method('commit');
        $entityManager->expects(self::once())->method('rollback');

        $this->expectException(RuntimeException::class);
        $this->processor($entityManager, $url)->process($this->payload([11, 12]), new Post(), ['pageId' => '11']);
    }

    /**
     * @dataProvider invalidSelections
     */
    public function testRejectsAmbiguousOrMalformedSelections(array $ids): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('find');
        $entityManager->expects(self::never())->method('remove');

        $this->expectException(BadRequestHttpException::class);
        $this->processor($entityManager, $this->url(1))->process(
            $this->payload($ids),
            new Post(),
            ['pageId' => '11']
        );
    }

    public static function invalidSelections(): iterable
    {
        yield 'no other language selected' => [[11]];

        yield 'duplicate page' => [[11, 11]];

        yield 'source omitted' => [[12, 13]];

        yield 'string page identifier' => [[11, '12']];

        yield 'invalid page identifier' => [[11, 0]];

        yield 'too many pages' => [range(1, 101)];
    }

    public function testNonAdministratorsCannotDeletePages(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('find');
        $entityManager->expects(self::never())->method('remove');

        $this->expectException(AccessDeniedHttpException::class);
        $this->processor($entityManager, $this->url(1), $security)->process(
            $this->payload([11, 12]),
            new Post(),
            ['pageId' => '11']
        );
    }

    private function payload(array $ids): PageLanguageDeleteAction
    {
        $payload = new PageLanguageDeleteAction();
        $payload->pageIds = $ids;

        return $payload;
    }

    private function processor(
        EntityManagerInterface $entityManager,
        AccessUrl $url,
        ?Security $security = null,
    ): PageLanguageDeleteProcessor {
        $repository = $this->createMock(AccessUrlRepository::class);
        $repository->method('getFirstId')->willReturn(1);
        $repository->method('find')->willReturn($url);

        if (null === $security) {
            $security = $this->createMock(Security::class);
            $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);
        }

        return new PageLanguageDeleteProcessor(
            $entityManager,
            new AccessUrlHelper($repository, new RequestStack()),
            $security,
        );
    }

    private function url(int $id): AccessUrl
    {
        $url = new AccessUrl();
        (new ReflectionProperty(AccessUrl::class, 'id'))->setValue($url, $id);

        return $url;
    }

    private function category(int $id): PageCategory
    {
        $category = new PageCategory();
        (new ReflectionProperty(PageCategory::class, 'id'))->setValue($category, $id);

        return $category;
    }

    private function page(int $id, string $locale, AccessUrl $url, PageCategory $category): Page
    {
        $page = (new Page())->setLocale($locale)->setUrl($url)->setCategory($category);
        (new ReflectionProperty(Page::class, 'id'))->setValue($page, $id);

        return $page;
    }
}
