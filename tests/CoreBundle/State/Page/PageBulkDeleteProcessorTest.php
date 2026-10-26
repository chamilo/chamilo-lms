<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\State\Page;

use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\ApiResource\Page\PageBulkDeleteAction;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Page;
use Chamilo\CoreBundle\Entity\PageCategory;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\State\Page\PageBulkDeleteProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PageBulkDeleteProcessorTest extends TestCase
{
    public function testDeletesPagesAcrossCategoriesInOneTransaction(): void
    {
        $url = $this->url(1);
        $first = $this->page(11, $url, $this->category(10));
        $second = $this->page(12, $url, $this->category(20));
        $unselected = $this->page(13, $url, $this->category(30));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturnCallback(
            static fn (string $class, mixed $id) => [11 => $first, 12 => $second, 13 => $unselected][$id] ?? null
        );
        $removed = [];
        $em->expects(self::once())->method('beginTransaction');
        $em->expects(self::exactly(2))->method('remove')->willReturnCallback(
            static function (Page $page) use (&$removed): void {
                $removed[] = $page->getId();
            }
        );
        $em->expects(self::once())->method('flush');
        $em->expects(self::once())->method('commit');
        $em->expects(self::never())->method('rollback');

        $result = $this->processor($em, $url)->process($this->payload([11, 12]), new Post());

        self::assertSame(2, $result->deletedCount);
        self::assertSame([11, 12], $removed);
    }

    public function testCrossPortalSelectionIsRejectedBeforeMutation(): void
    {
        $url = $this->url(1);
        $first = $this->page(11, $url, $this->category(10));
        $second = $this->page(12, $this->url(2), $this->category(20));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturnCallback(
            static fn (string $class, mixed $id) => [11 => $first, 12 => $second][$id] ?? null
        );
        $em->expects(self::never())->method('remove');
        $em->expects(self::never())->method('beginTransaction');

        $this->expectException(NotFoundHttpException::class);
        $this->processor($em, $url)->process($this->payload([11, 12]), new Post());
    }

    /**
     * @dataProvider invalidSelections
     */
    public function testRejectsInvalidIdentifiersWithoutMutation(array $ids): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('find');
        $em->expects(self::never())->method('remove');

        $this->expectException(BadRequestHttpException::class);
        $this->processor($em, $this->url(1))->process($this->payload($ids), new Post());
    }

    public static function invalidSelections(): iterable
    {
        yield 'empty selection' => [[]];

        yield 'duplicate ids' => [[11, 11]];

        yield 'string id' => [['11']];

        yield 'negative id' => [[-1]];

        yield 'too many ids' => [range(1, 101)];
    }

    public function testNonAdministratorCannotDelete(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('find');
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->processor($em, $this->url(1), $security)->process($this->payload([11]), new Post());
    }

    public function testRollbackOnFlushFailure(): void
    {
        $url = $this->url(1);
        $page = $this->page(11, $url, $this->category(10));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturn($page);
        $em->expects(self::once())->method('beginTransaction');
        $em->expects(self::once())->method('flush')->willThrowException(new RuntimeException('Simulated failure'));
        $em->expects(self::never())->method('commit');
        $em->expects(self::once())->method('rollback');

        $this->expectException(RuntimeException::class);
        $this->processor($em, $url)->process($this->payload([11]), new Post());
    }

    private function processor(
        EntityManagerInterface $em,
        AccessUrl $url,
        ?Security $security = null
    ): PageBulkDeleteProcessor {
        $repository = $this->createMock(AccessUrlRepository::class);
        $repository->method('getFirstId')->willReturn(1);
        $repository->method('find')->willReturn($url);

        if (null === $security) {
            $security = $this->createMock(Security::class);
            $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);
        }

        return new PageBulkDeleteProcessor(
            $em,
            new AccessUrlHelper($repository, new RequestStack()),
            $security,
        );
    }

    private function payload(array $ids): PageBulkDeleteAction
    {
        $payload = new PageBulkDeleteAction();
        $payload->pageIds = $ids;

        return $payload;
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

    private function page(int $id, AccessUrl $url, PageCategory $category): Page
    {
        $page = (new Page())->setLocale('en')->setUrl($url)->setCategory($category);
        (new ReflectionProperty(Page::class, 'id'))->setValue($page, $id);

        return $page;
    }
}
