<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<CLpCategory>
 */
final readonly class LearningPathCategoryCollectionProvider implements ProviderInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CLpCategoryRepository $categoryRepository,
        private Security $security,
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private CShortcutRepository $shortcutRepository,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, CLpCategory>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];
        $parentNodeId = (int) ($filters['resourceNode.parent'] ?? 0);
        if ($parentNodeId <= 0) {
            return [];
        }

        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (!$course instanceof Course) {
            return [];
        }

        $courseNode = $course->getResourceNode();
        if (null === $courseNode || $parentNodeId !== (int) $courseNode->getId()) {
            throw new AccessDeniedHttpException('resourceNode.parent does not match the current course.');
        }

        $contextSessionId = (int) ($this->cidReqHelper->getSessionId() ?? 0);
        $filterSessionId = isset($filters['sid']) ? (int) $filters['sid'] : $contextSessionId;
        if ($filterSessionId !== $contextSessionId) {
            throw new AccessDeniedHttpException('The requested session does not match the current context.');
        }

        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session) {
            $sessionCourse = $this->entityManager->getRepository(SessionRelCourse::class)->findOneBy([
                'course' => $course,
                'session' => $session,
            ]);

            if (!$sessionCourse instanceof SessionRelCourse) {
                throw new AccessDeniedHttpException('The requested session is not linked to this course.');
            }
        }

        $group = $this->getValidatedGroupFromContext($this->entityManager, $this->cidReqHelper, $course);
        $canManage = $this->canManageLearningPaths($this->security)
            && !$this->isStudentViewRequest($this->requestStack);

        /** @var array<int, CLpCategory> $categories */
        $categories = $this->categoryRepository
            ->getResourcesByCourse($course, $session, $group, null, !$canManage, true)
            ->getQuery()
            ->getResult()
        ;

        $subscriptionsAllowed = $this->allowsCategorySubscriptions();

        foreach ($categories as $category) {
            $link = $this->getContextResourceLink($category, $course, $session, $group);
            $resourceNode = $category->getResourceNode();
            $exactContextLink = $resourceNode?->getResourceLinkByContext($course, $session, $group);

            $category->setVisible(
                $link instanceof ResourceLink && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility(),
            );
            $category->setPublishedOnCourseHome(
                null !== $this->shortcutRepository->findShortcutFromResourceInCourse($category, $course),
            );
            $category->setSubscriptionsAllowed($subscriptionsAllowed);
            $category->setReorderable(
                $canManage
                && $exactContextLink instanceof ResourceLink
                && null !== $resourceNode
                && $this->security->isGranted('EDIT', $resourceNode),
            );
        }

        return $categories;
    }

    private function allowsCategorySubscriptions(): bool
    {
        $value = $this->settingsManager->getSetting('lp.lp_subscription_settings');
        if (\is_string($value)) {
            $decoded = json_decode($value, true);
            if (\is_array($decoded)) {
                $value = $decoded;
            }
        }
        if (!\is_array($value)) {
            return true;
        }

        $options = \is_array($value['options'] ?? null) ? $value['options'] : $value;

        return (bool) ($options['allow_add_users_to_lp_category'] ?? true);
    }
}
