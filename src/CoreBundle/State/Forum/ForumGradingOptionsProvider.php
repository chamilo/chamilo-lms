<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumGradingOptions;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Repository\GradeBookCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<ForumGradingOptions>
 */
final class ForumGradingOptionsProvider implements ProviderInterface
{
    use ForumStateHelperTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly GradeBookCategoryRepository $gradeBookCategoryRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ForumGradingOptions
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return ForumGradingOptions::fromCategories([]);
        }

        if (!$this->canManageForumsInCurrentView($this->security, $request)) {
            throw new AccessDeniedHttpException('You are not allowed to manage forum grading.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $courseId = (int) $course->getId();
        $sessionId = null === $session ? null : (int) $session->getId();

        $this->gradeBookCategoryRepository->createDefaultCategory($courseId, $sessionId);

        return ForumGradingOptions::fromCategories(
            $this->formatGradebookCategories(
                $this->gradeBookCategoryRepository->getCategoriesForCourse($courseId, $sessionId)
            )
        );
    }

    /**
     * @param GradebookCategory[] $categories
     *
     * @return array<int, array<string, mixed>>
     */
    private function formatGradebookCategories(array $categories): array
    {
        $options = [];
        foreach ($categories as $category) {
            if (!$category instanceof GradebookCategory) {
                continue;
            }

            $options[] = [
                'id' => $category->getId(),
                'title' => $this->getGradebookCategoryLabel($category),
            ];
        }

        return $options;
    }

    private function getGradebookCategoryLabel(GradebookCategory $category): string
    {
        $parent = $category->getParent();
        if ($parent instanceof GradebookCategory) {
            return $parent->getTitle().' / '.$category->getTitle();
        }

        return 'Default';
    }
}
