<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiCategory;
use Chamilo\CourseBundle\Repository\CWikiCategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class WikiCategoryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CWikiCategoryRepository $categoryRepository,
    ) {}

    /**
     * @return array<int, array{id:int, title:string, label:string, pathTitle:string, parentId:?int, level:int}>
     */
    public function getOptions(Course $course, ?Session $session): array
    {
        return array_values(array_map(
            fn (CWikiCategory $category): array => $this->normalizeCategory($category),
            array_filter(
                $this->categoryRepository->findByCourse($course, $session),
                static fn (CWikiCategory $category): bool => null !== $category->getId(),
            ),
        ));
    }

    /**
     * @return array<int, array{id:int, title:string, label:string, pathTitle:string, parentId:?int, level:int, pageCount:int, descendantCount:int}>
     */
    public function getManagementRows(Course $course, ?Session $session): array
    {
        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $pageCounts = $this->getPageCounts($courseId, $sessionId);
        $categories = array_values(array_filter(
            $this->categoryRepository->findByCourse($course, $session),
            static fn (CWikiCategory $category): bool => null !== $category->getId(),
        ));
        $rows = [];

        foreach ($categories as $category) {
            $categoryId = (int) $category->getId();
            $rows[] = [
                ...$this->normalizeCategory($category),
                'pageCount' => $pageCounts[$categoryId] ?? 0,
                'descendantCount' => $this->countDescendants($category, $categories),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, int> $categoryIds
     *
     * @return array<int, CWikiCategory>
     */
    public function resolveCategories(array $categoryIds, Course $course, ?Session $session): array
    {
        $normalizedIds = array_values(array_unique(array_filter(
            array_map('intval', $categoryIds),
            static fn (int $categoryId): bool => $categoryId > 0,
        )));

        if ([] === $normalizedIds) {
            return [];
        }

        $categories = $this->categoryRepository->findByIdsInContext($normalizedIds, $course, $session);
        if (\count($categories) !== \count($normalizedIds)) {
            throw new BadRequestHttpException('One or more selected Wiki categories are invalid for the current context.');
        }

        return $categories;
    }

    /**
     * @return array<int, int>
     */
    public function getSelectedIds(CWiki $wiki, Course $course, ?Session $session): array
    {
        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $selectedIds = [];

        foreach ($wiki->getCategories() as $category) {
            if (!$category instanceof CWikiCategory || null === $category->getId()) {
                continue;
            }

            $categoryCourseId = (int) $category->getCourse()->getId();
            $categorySessionId = null !== $category->getSession() ? (int) $category->getSession()->getId() : 0;
            if ($categoryCourseId !== $courseId || $categorySessionId !== $sessionId) {
                continue;
            }

            $selectedIds[(int) $category->getId()] = (int) $category->getId();
        }

        return array_values($selectedIds);
    }

    /**
     * @param array<int, CWikiCategory> $categories
     */
    public function applyCategories(CWiki $wiki, array $categories): void
    {
        foreach ($categories as $category) {
            $wiki->addCategory($category);
        }
    }

    public function getPathTitle(CWikiCategory $category): string
    {
        $titles = [];
        $current = $category;
        $guard = 0;

        while ($current instanceof CWikiCategory && $guard < 100) {
            array_unshift($titles, $current->getTitle());
            $current = $current->getParent();
            ++$guard;
        }

        return implode(' / ', $titles);
    }

    public function assertParentDoesNotCreateCycle(CWikiCategory $category, ?CWikiCategory $parent): void
    {
        $categoryId = $category->getId();
        $current = $parent;
        $guard = 0;

        while ($current instanceof CWikiCategory && $guard < 100) {
            if (null !== $categoryId && $current->getId() === $categoryId) {
                throw new BadRequestHttpException('A Wiki category cannot be its own parent or descendant.');
            }

            $current = $current->getParent();
            ++$guard;
        }
    }

    /**
     * @return array{id:int, title:string, label:string, pathTitle:string, parentId:?int, level:int}
     */
    private function normalizeCategory(CWikiCategory $category): array
    {
        $level = max(0, $category->getLevel());

        return [
            'id' => (int) $category->getId(),
            'title' => $category->getTitle(),
            'label' => str_repeat('— ', $level).$category->getTitle(),
            'pathTitle' => $this->getPathTitle($category),
            'parentId' => $category->getParent()?->getId(),
            'level' => $level,
        ];
    }

    /**
     * @param array<int, CWikiCategory> $categories
     */
    private function countDescendants(CWikiCategory $category, array $categories): int
    {
        $rootId = $category->getRoot()?->getId() ?? $category->getId();
        $count = 0;

        foreach ($categories as $candidate) {
            if ($candidate === $category) {
                continue;
            }

            $candidateRootId = $candidate->getRoot()?->getId() ?? $candidate->getId();
            if ($candidateRootId !== $rootId) {
                continue;
            }

            if ($candidate->getLeft() > $category->getLeft() && $candidate->getRight() < $category->getRight()) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @return array<int, int>
     */
    private function getPageCounts(int $courseId, int $sessionId): array
    {
        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    relation.category_id AS category_id,
                    COUNT(DISTINCT wiki.page_id) AS page_count
                FROM c_wiki_rel_category relation
                INNER JOIN c_wiki wiki ON wiki.iid = relation.wiki_id
                WHERE wiki.c_id = :courseId
                  AND COALESCE(wiki.session_id, 0) = :sessionId
                GROUP BY relation.category_id
                SQL,
            [
                'courseId' => $courseId,
                'sessionId' => $sessionId,
            ],
            [
                'courseId' => Types::INTEGER,
                'sessionId' => Types::INTEGER,
            ],
        );
        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row['category_id']] = (int) $row['page_count'];
        }

        return $counts;
    }
}
