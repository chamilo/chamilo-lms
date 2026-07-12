<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathCategorySubscription;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CLpCategoryRelUser;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProviderInterface<LearningPathCategorySubscription> */
final readonly class LearningPathCategorySubscriptionProvider implements ProviderInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CGroupRepository $groupRepository,
        private RequestStack $requestStack,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): LearningPathCategorySubscription
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $category = $this->getCategory($uriVariables);
        $this->assertCategoryContext($category, $course, $session, $group);
        $this->assertCategorySubscriptionsEnabled();

        $result = new LearningPathCategorySubscription();
        $result->categoryId = $category->getIid();
        $result->categoryTitle = $category->getTitle();
        $result->allowUserGroups = $this->settingEnabled('lp.allow_lp_subscription_to_usergroups');
        $result->csrfToken = $this->csrfTokenManager->getToken('learning_path_action')->getValue();
        $result->users = $this->getUserOptions($course, $session);
        $result->selectedUserIds = array_values(array_intersect(
            $this->getSelectedUserIds($category),
            array_column($result->users, 'id'),
        ));
        $result->groups = $this->getGroupOptions($course, $session);
        $result->selectedGroupIds = array_values(array_intersect(
            $this->getSelectedGroupIds($category, $course, $session),
            array_column($result->groups, 'id'),
        ));

        if ($result->allowUserGroups) {
            $result->userGroups = $this->getUserGroupOptions($course);
            $result->selectedUserGroupIds = array_values(array_intersect(
                $this->getSelectedUserGroupIds($category, $course, $session),
                array_column($result->userGroups, 'id'),
            ));
        }

        return $result;
    }

    /** @param array<string, mixed> $uriVariables */
    private function getCategory(array $uriVariables): CLpCategory
    {
        $categoryId = (int) ($uriVariables['categoryId'] ?? 0);
        if ($categoryId <= 0) {
            throw new BadRequestHttpException('Invalid learning path category id.');
        }

        $category = $this->entityManager->getRepository(CLpCategory::class)->find($categoryId);
        if (!$category instanceof CLpCategory) {
            throw new NotFoundHttpException('Learning path category not found.');
        }

        return $category;
    }

    private function assertCategoryContext(
        CLpCategory $category,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): void {
        $resourceNode = $category->getResourceNode();
        if (!$resourceNode instanceof ResourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this learning path category.');
        }

        $resourceLink = $resourceNode->getResourceLinkByContext($course, $session, $group);
        if (!$resourceLink instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The learning path category is not owned by the current context.');
        }
    }

    private function assertCategorySubscriptionsEnabled(): void
    {
        $value = $this->settingsManager->getSetting('lp.lp_subscription_settings');
        if (\is_string($value)) {
            $decoded = json_decode($value, true);
            if (\is_array($decoded)) {
                $value = $decoded;
            }
        }

        if (!\is_array($value)) {
            return;
        }

        $options = \is_array($value['options'] ?? null) ? $value['options'] : $value;
        if (!(bool) ($options['allow_add_users_to_lp_category'] ?? true)) {
            throw new AccessDeniedHttpException('Learning path category subscriptions are disabled.');
        }
    }

    private function settingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name);
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /** @return array<int, array{id: int, title: string}> */
    private function getUserOptions(Course $course, ?Session $session): array
    {
        $users = [];

        if (null === $session) {
            foreach ($course->getStudentSubscriptions() as $subscription) {
                if (!$subscription instanceof CourseRelUser) {
                    continue;
                }
                $user = $subscription->getUser();
                $users[(int) $user->getId()] = $user;
            }
        } else {
            foreach ($session->getSessionRelCourseRelUsers() as $subscription) {
                if (!$subscription instanceof SessionRelCourseRelUser
                    || Session::STUDENT !== $subscription->getStatus()
                    || $subscription->getCourse()->getId() !== $course->getId()
                ) {
                    continue;
                }
                $user = $subscription->getUser();
                $users[(int) $user->getId()] = $user;
            }
        }

        $options = [];
        foreach ($users as $user) {
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }
            $options[] = ['id' => (int) $user->getId(), 'title' => $user->getFullNameWithClasses()];
        }

        usort($options, static fn (array $left, array $right): int => strcasecmp($left['title'], $right['title']));

        return $options;
    }

    /** @return array<int, int> */
    private function getSelectedUserIds(CLpCategory $category): array
    {
        $ids = [];
        foreach ($category->getUsers() as $relation) {
            if (!$relation instanceof CLpCategoryRelUser || null === $relation->getUser()?->getId()) {
                continue;
            }
            $ids[] = (int) $relation->getUser()->getId();
        }

        sort($ids);

        return array_values(array_unique($ids));
    }

    /** @return array<int, array{id: int, title: string}> */
    private function getGroupOptions(Course $course, ?Session $session): array
    {
        /** @var array<int, CGroup> $groups */
        $groups = $this->groupRepository->getResourcesByCourse($course, $session)->getQuery()->getResult();
        $options = [];

        foreach ($groups as $group) {
            if (null === $group->getIid()) {
                continue;
            }
            $options[] = ['id' => (int) $group->getIid(), 'title' => $group->getTitle()];
        }

        usort($options, static fn (array $left, array $right): int => strcasecmp($left['title'], $right['title']));

        return $options;
    }

    /** @return array<int, int> */
    private function getSelectedGroupIds(CLpCategory $category, Course $course, ?Session $session): array
    {
        $ids = [];
        foreach ($category->getResourceNode()?->getResourceLinks() ?? [] as $link) {
            if (!$link instanceof ResourceLink
                || $link->getCourse()?->getId() !== $course->getId()
                || $link->getSession()?->getId() !== $session?->getId()
                || null === $link->getGroup()?->getIid()
            ) {
                continue;
            }
            $ids[] = (int) $link->getGroup()->getIid();
        }

        sort($ids);

        return array_values(array_unique($ids));
    }

    /** @return array<int, array{id: int, title: string}> */
    private function getUserGroupOptions(Course $course): array
    {
        /** @var array<int, Usergroup> $groups */
        $groups = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT usergroup')
            ->from(Usergroup::class, 'usergroup')
            ->innerJoin('usergroup.courses', 'courseRelation')
            ->andWhere('IDENTITY(courseRelation.course) = :courseId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('usergroup.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $options = [];
        foreach ($groups as $group) {
            if (null === $group->getId()) {
                continue;
            }
            $options[] = ['id' => (int) $group->getId(), 'title' => $group->getTitle()];
        }

        return $options;
    }

    /** @return array<int, int> */
    private function getSelectedUserGroupIds(CLpCategory $category, Course $course, ?Session $session): array
    {
        $parameters = [
            'courseId' => (int) $course->getId(),
            'categoryId' => (int) $category->getIid(),
        ];
        $types = [
            'courseId' => Types::INTEGER,
            'categoryId' => Types::INTEGER,
        ];
        $sessionCondition = 'session_id IS NULL';

        if (null !== $session) {
            $sessionCondition = 'session_id = :sessionId';
            $parameters['sessionId'] = (int) $session->getId();
            $types['sessionId'] = Types::INTEGER;
        }

        $ids = $this->entityManager->getConnection()->executeQuery(
            'SELECT usergroup_id FROM c_lp_category_rel_usergroup
             WHERE c_id = :courseId
               AND lp_category_id = :categoryId
               AND '.$sessionCondition,
            $parameters,
            $types,
        )->fetchFirstColumn();

        $ids = array_map('intval', $ids);
        sort($ids);

        return array_values(array_unique($ids));
    }
}
