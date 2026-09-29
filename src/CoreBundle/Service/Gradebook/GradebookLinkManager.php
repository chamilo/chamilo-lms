<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\GradeBookCategoryRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkResourceResolver;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Central Doctrine lifecycle for links between course tools and Gradebook.
 *
 * Tool processors remain responsible for their own resource permissions and
 * domain fields. This service owns Gradebook category context validation,
 * locking, duplicate cleanup, and GradebookLink persistence.
 */
final readonly class GradebookLinkManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GradeBookCategoryRepository $categoryRepository,
        private SettingsManager $settingsManager,
        private Security $security,
    ) {}

    public function assertSessionBelongsToCourse(Course $course, ?Session $session): void
    {
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not belong to the current course.');
        }
    }

    public function getRootCategory(Course $course, ?Session $session, bool $createIfMissing = true): ?GradebookCategory
    {
        $this->assertSessionBelongsToCourse($course, $session);

        $root = $this->categoryRepository->findOneBy(
            ['course' => $course, 'session' => $session, 'parent' => null],
            ['id' => 'ASC'],
        );

        if (!$root instanceof GradebookCategory && $createIfMissing) {
            $root = $this->categoryRepository->createDefaultCategory(
                (int) $course->getId(),
                $session?->getId(),
            );
        }

        return $root instanceof GradebookCategory ? $root : null;
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function getCategoryOptions(Course $course, ?Session $session): array
    {
        $root = $this->getRootCategory($course, $session, true);
        if (!$root instanceof GradebookCategory) {
            return [];
        }

        $categories = $this->categoryRepository->getCategoriesForCourse(
            (int) $course->getId(),
            $session?->getId(),
        );

        $options = [];
        foreach ($categories as $category) {
            if (!$category instanceof GradebookCategory
                || null === $category->getId()
                || null !== $category->getGradeModel()
                || !$this->isCategoryDescendantOf($category, $root)
            ) {
                continue;
            }

            $options[] = [
                'value' => (int) $category->getId(),
                'label' => $this->getCategoryLabel($category, $root),
            ];
        }

        usort(
            $options,
            static function (array $left, array $right) use ($root): int {
                $rootId = (int) $root->getId();
                if ($left['value'] === $rootId) {
                    return -1;
                }
                if ($right['value'] === $rootId) {
                    return 1;
                }

                return strnatcasecmp($left['label'], $right['label']);
            },
        );

        return $options;
    }

    public function requireCategory(
        Course $course,
        ?Session $session,
        int $categoryId,
        bool $requireEditable = true,
    ): GradebookCategory {
        if ($categoryId <= 0) {
            throw new BadRequestHttpException('A valid Gradebook category id is required.');
        }

        $root = $this->getRootCategory($course, $session, true);
        if (!$root instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $category = $this->categoryRepository->find($categoryId);
        if (!$category instanceof GradebookCategory) {
            throw new NotFoundHttpException('The requested Gradebook category was not found.');
        }

        $this->assertCategoryContext($category, $course, $session);
        if (!$this->isCategoryDescendantOf($category, $root)) {
            throw new AccessDeniedHttpException('The requested Gradebook category is outside the current Gradebook.');
        }
        if (null !== $category->getGradeModel()) {
            throw new BadRequestHttpException('Online activities cannot be added to a Gradebook category using a grade model.');
        }
        if ($requireEditable) {
            $this->assertCategoryEditable($category);
        }

        return $category;
    }

    public function findLink(Course $course, ?Session $session, int $type, int $refId): ?GradebookLink
    {
        $links = $this->findLinks($course, $session, $type, $refId);

        return $links[0] ?? null;
    }

    /**
     * @return list<GradebookLink>
     */
    public function findLinks(Course $course, ?Session $session, int $type, int $refId): array
    {
        if ($refId <= 0) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('link', 'category')
            ->from(GradebookLink::class, 'link')
            ->innerJoin('link.category', 'category')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->andWhere('link.type = :type')
            ->andWhere('link.refId = :refId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('type', $type, Types::INTEGER)
            ->setParameter('refId', $refId, Types::INTEGER)
            ->orderBy('link.id', 'ASC')
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('IDENTITY(category.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $qb->andWhere('category.session IS NULL');
        }

        $root = $this->getRootCategory($course, $session, false);
        $links = [];
        foreach ($qb->getQuery()->getResult() as $link) {
            if (!$link instanceof GradebookLink) {
                continue;
            }
            if ($root instanceof GradebookCategory && !$this->isCategoryDescendantOf($link->getCategory(), $root)) {
                continue;
            }

            $links[] = $link;
        }

        return $links;
    }

    public function getLinkById(Course $course, ?Session $session, int $linkId): GradebookLink
    {
        if ($linkId <= 0) {
            throw new BadRequestHttpException('A valid Gradebook online activity id is required.');
        }

        $link = $this->entityManager->getRepository(GradebookLink::class)->find($linkId);
        if (!$link instanceof GradebookLink) {
            throw new NotFoundHttpException('The requested Gradebook online activity was not found.');
        }
        if ((int) $link->getCourse()->getId() !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('The requested Gradebook online activity belongs to another course.');
        }

        $category = $link->getCategory();
        $this->assertCategoryContext($category, $course, $session);
        $root = $this->getRootCategory($course, $session, false);
        if ($root instanceof GradebookCategory && !$this->isCategoryDescendantOf($category, $root)) {
            throw new AccessDeniedHttpException('The requested Gradebook online activity is outside the current Gradebook.');
        }

        return $link;
    }

    public function upsertLink(
        Course $course,
        ?Session $session,
        int $type,
        int $refId,
        int $categoryId,
        float $weight,
        bool $visible = true,
        float $minScore = 0.0,
        ?float $pointsOne = null,
        ?float $pointsMany = null,
    ): GradebookLink {
        $this->validateLinkValues($type, $refId, $weight, $minScore, $pointsOne, $pointsMany);
        $category = $this->requireCategory($course, $session, $categoryId, true);
        $links = $this->findLinks($course, $session, $type, $refId);

        foreach ($links as $existingLink) {
            $this->assertLinkEditable($existingLink);
        }

        $link = array_shift($links);
        if (!$link instanceof GradebookLink) {
            $link = new GradebookLink();
            $link
                ->setType($type)
                ->setRefId($refId)
                ->setCourse($course)
                ->setCreatedAt(new DateTime())
                ->setLocked(0)
            ;
        }

        $link
            ->setCategory($category)
            ->setWeight($weight)
            ->setVisible($visible ? 1 : 0)
            ->setMinScore($minScore)
            ->setPointsOne(null !== $pointsOne ? (string) $pointsOne : null)
            ->setPointsMany(null !== $pointsMany ? (string) $pointsMany : null)
        ;

        $this->entityManager->persist($link);

        // A source resource has one Gradebook relation per course/session context.
        // Remove stale duplicates left by older implementations while editing it.
        foreach ($links as $duplicate) {
            $this->entityManager->remove($duplicate);
        }

        return $link;
    }

    public function removeLinks(Course $course, ?Session $session, int $type, int $refId): int
    {
        $links = $this->findLinks($course, $session, $type, $refId);
        foreach ($links as $link) {
            $this->assertLinkEditable($link);
        }
        foreach ($links as $link) {
            $this->entityManager->remove($link);
        }

        return \count($links);
    }

    /**
     * @param list<int> $types
     */
    public function removeLinksForTypes(Course $course, ?Session $session, array $types, int $refId): int
    {
        $removed = 0;
        foreach (array_values(array_unique(array_map('intval', $types))) as $type) {
            $removed += $this->removeLinks($course, $session, $type, $refId);
        }

        return $removed;
    }

    public function removeAllCourseLinks(Course $course, int $type, int $refId): int
    {
        if ($refId <= 0) {
            return 0;
        }

        $links = $this->entityManager->createQueryBuilder()
            ->select('link')
            ->from(GradebookLink::class, 'link')
            ->andWhere('IDENTITY(link.course) = :courseId')
            ->andWhere('link.type = :type')
            ->andWhere('link.refId = :refId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('type', $type, Types::INTEGER)
            ->setParameter('refId', $refId, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        foreach ($links as $link) {
            if ($link instanceof GradebookLink) {
                $this->assertLinkEditable($link);
            }
        }
        foreach ($links as $link) {
            if ($link instanceof GradebookLink) {
                $this->entityManager->remove($link);
            }
        }

        return \count($links);
    }

    /**
     * Checks whether a tool action must be blocked by a locked Gradebook link.
     * This follows the historical gradebook_locking_enabled switch used by tools.
     */
    public function isResourceLocked(Course $course, ?Session $session, int $type, int $refId): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN') || !$this->isGradebookLockingEnabled()) {
            return false;
        }

        foreach ($this->findLinks($course, $session, $type, $refId) as $link) {
            if (1 === (int) $link->getLocked() || 1 === (int) $link->getCategory()->getLocked()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<int> $types
     */
    public function isResourceLockedForTypes(Course $course, ?Session $session, array $types, int $refId): bool
    {
        foreach ($types as $type) {
            if ($this->isResourceLocked($course, $session, (int) $type, $refId)) {
                return true;
            }
        }

        return false;
    }

    private function validateLinkValues(
        int $type,
        int $refId,
        float $weight,
        float $minScore,
        ?float $pointsOne,
        ?float $pointsMany,
    ): void {
        $supportedTypes = [
            GradebookLinkResourceResolver::LINK_EXERCISE,
            GradebookLinkResourceResolver::LINK_STUDENT_PUBLICATION,
            GradebookLinkResourceResolver::LINK_LEARNING_PATH,
            GradebookLinkResourceResolver::LINK_FORUM_THREAD,
            GradebookLinkResourceResolver::LINK_ATTENDANCE,
            GradebookLinkResourceResolver::LINK_SURVEY,
            GradebookLinkResourceResolver::LINK_FORUM_PARTICIPATION,
        ];
        if (!\in_array($type, $supportedTypes, true)) {
            throw new BadRequestHttpException('Unsupported Gradebook online activity type.');
        }
        if ($refId <= 0) {
            throw new BadRequestHttpException('A valid linked resource id is required.');
        }
        if ($weight < 0 || $minScore < 0) {
            throw new BadRequestHttpException('Gradebook scores and weights must be zero or greater.');
        }
        if ((null !== $pointsOne && $pointsOne < 0) || (null !== $pointsMany && $pointsMany < 0)) {
            throw new BadRequestHttpException('Forum participation points must be zero or greater.');
        }
    }

    private function assertCategoryContext(GradebookCategory $category, Course $course, ?Session $session): void
    {
        if ((int) $category->getCourse()->getId() !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('The requested Gradebook category belongs to another course.');
        }

        $categorySessionId = $category->getSession()?->getId() ?? 0;
        $sessionId = $session?->getId() ?? 0;
        if ((int) $categorySessionId !== (int) $sessionId) {
            throw new AccessDeniedHttpException('The requested Gradebook category belongs to another session context.');
        }
    }

    private function assertCategoryEditable(GradebookCategory $category): void
    {
        if (1 === (int) $category->getLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('The requested Gradebook category is locked.');
        }
    }

    private function assertLinkEditable(GradebookLink $link): void
    {
        if (1 === (int) $link->getLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('The requested Gradebook online activity is locked.');
        }

        $this->assertCategoryEditable($link->getCategory());
    }

    private function isCategoryDescendantOf(GradebookCategory $category, GradebookCategory $root): bool
    {
        $visited = [];
        $current = $category;
        while ($current instanceof GradebookCategory) {
            $currentId = (int) $current->getId();
            if ($currentId === (int) $root->getId()) {
                return true;
            }
            if (isset($visited[$currentId])) {
                return false;
            }
            $visited[$currentId] = true;
            $current = $current->getParent();
        }

        return false;
    }

    private function getCategoryLabel(GradebookCategory $category, GradebookCategory $root): string
    {
        if ((int) $category->getId() === (int) $root->getId()) {
            return 'Default';
        }

        $parts = [];
        $current = $category;
        $visited = [];
        while ($current instanceof GradebookCategory && (int) $current->getId() !== (int) $root->getId()) {
            $currentId = (int) $current->getId();
            if (isset($visited[$currentId])) {
                break;
            }
            $visited[$currentId] = true;
            $title = trim((string) $current->getTitle());
            $parts[] = '' !== $title ? $title : 'Category #'.$currentId;
            $current = $current->getParent();
        }

        $parts = array_reverse($parts);

        return 'Default'.([] !== $parts ? ' / '.implode(' / ', $parts) : '');
    }

    private function isGradebookLockingEnabled(): bool
    {
        $value = $this->settingsManager->getSetting('gradebook.gradebook_locking_enabled', true);
        if (null === $value || '' === trim((string) $value)) {
            $value = $this->settingsManager->getSetting('gradebook_locking_enabled', true);
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
