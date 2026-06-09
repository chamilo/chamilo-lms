<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<array<string, mixed>>
 */
final class ForumCategoryCollectionStateProvider implements ProviderInterface
{
    use ForumStateHelperTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumCategoryRepository $categoryRepository,
        private readonly Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, array<string, mixed>>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        return $this->getCategories($request);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCategoriesFromCurrentRequest(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        return $this->getCategories($request);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(Request $request): array
    {
        $this->assertForumMemberAccess($this->security, 'You are not allowed to access forum categories.');

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $parentNode = $this->getParentNode($this->entityManager, $request);
        $showHidden = $this->canManageForumsInCurrentView($this->security, $request);

        $queryBuilder = $this->categoryRepository->getResourcesByCourse(
            $course,
            $session,
            $group,
            $parentNode,
            !$showHidden,
            true,
        );

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $category) {
            if (!$category instanceof CForumCategory) {
                continue;
            }

            $items[] = $this->normalizeCategory($category, $course, $session);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCategory(CForumCategory $category, Course $course, ?Session $session): array
    {
        return [
            '@id' => '/api/forum_categories/'.$category->getIid(),
            '@type' => 'ForumCategory',
            'iid' => $category->getIid(),
            'title' => $category->getTitle(),
            'catComment' => $category->getCatComment(),
            'locked' => $category->getLocked(),
            'forumCategoryVisible' => $category->isVisible($course, $session),
            'position' => $category->getResourceNode()?->getResourceLinkByContext($course, $session)?->getDisplayOrder()
                ?? $category->getResourceNode()?->getResourceLinkByContext($course)?->getDisplayOrder()
                ?? 0,
        ];
    }
}
