<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
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
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<LearningPathCategorySubscription, void> */
final readonly class LearningPathCategorySubscriptionProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    private const SECTION_GROUPS = 'groups';
    private const SECTION_USER_GROUPS = 'usergroups';
    private const SECTION_USERS = 'users';

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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof LearningPathCategorySubscription) {
            throw new BadRequestHttpException('Learning path category subscription data is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $this->validateActionToken($this->csrfTokenManager, $data->csrfTokenInput);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $category = $this->getCategory($uriVariables);
        $this->assertCategoryContext($category, $course, $session, $group);
        $this->assertCategorySubscriptionsEnabled();

        $selectedIds = $this->normalizeIds($data->selectedIds);

        match ($data->section) {
            self::SECTION_USERS => $this->saveUsers($category, $course, $session, $selectedIds),
            self::SECTION_GROUPS => $this->saveGroups($category, $course, $session, $selectedIds),
            self::SECTION_USER_GROUPS => $this->saveUserGroups($category, $course, $session, $selectedIds),
            default => throw new BadRequestHttpException('Unsupported learning path category subscription section.'),
        };

        $this->entityManager->persist($category);
        $this->entityManager->flush();
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

    /**
     * @param array<int, int|string> $values
     *
     * @return array<int, int>
     */
    private function normalizeIds(array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        sort($ids);

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int, int> $selectedIds
     */
    private function saveUsers(CLpCategory $category, Course $course, ?Session $session, array $selectedIds): void
    {
        $allowedUsers = $this->getAllowedUsers($course, $session);
        $this->assertAllowedIds($selectedIds, array_keys($allowedUsers), 'A selected user is outside the current course context.');

        foreach ($category->getUsers()->toArray() as $relation) {
            if (!$relation instanceof CLpCategoryRelUser) {
                continue;
            }
            $userId = (int) ($relation->getUser()?->getId() ?? 0);
            if (!\in_array($userId, $selectedIds, true)) {
                $category->removeUsers($relation);
            }
        }

        foreach ($selectedIds as $userId) {
            $user = $allowedUsers[$userId] ?? null;
            if (!$user instanceof User || $category->hasUserAdded($user)) {
                continue;
            }
            $category->addUser((new CLpCategoryRelUser())->setUser($user));
        }
    }

    /**
     * @param array<int, int> $selectedIds
     */
    private function saveGroups(CLpCategory $category, Course $course, ?Session $session, array $selectedIds): void
    {
        $allowedGroups = $this->getAllowedGroups($course, $session);
        $this->assertAllowedIds($selectedIds, array_keys($allowedGroups), 'A selected group is outside the current course context.');

        $selectedMap = array_fill_keys($selectedIds, true);
        $currentMap = [];

        foreach ($category->getResourceNode()?->getResourceLinks() ?? [] as $link) {
            if (!$link instanceof ResourceLink
                || $link->getCourse()?->getId() !== $course->getId()
                || $link->getSession()?->getId() !== $session?->getId()
                || null === $link->getGroup()?->getIid()
            ) {
                continue;
            }

            $groupId = (int) $link->getGroup()->getIid();
            $currentMap[$groupId] = true;
            if (!isset($selectedMap[$groupId])) {
                $this->entityManager->remove($link);
            }
        }

        foreach ($selectedIds as $groupId) {
            if (isset($currentMap[$groupId])) {
                continue;
            }
            $category->addGroupLink($course, $allowedGroups[$groupId], $session);
        }
    }

    /**
     * @param array<int, int> $selectedIds
     */
    private function saveUserGroups(CLpCategory $category, Course $course, ?Session $session, array $selectedIds): void
    {
        if (!$this->settingEnabled('lp.allow_lp_subscription_to_usergroups')) {
            throw new AccessDeniedHttpException('Learning path category subscriptions for classes are disabled.');
        }

        $allowedGroups = $this->getAllowedUserGroups($course);
        $this->assertAllowedIds($selectedIds, array_keys($allowedGroups), 'A selected class is outside the current course context.');

        $existingIds = $this->getSelectedUserGroupIds($category, $course, $session);
        $removedIds = array_values(array_diff($existingIds, $selectedIds));
        $addedIds = array_values(array_diff($selectedIds, $existingIds));

        $this->deleteUserGroupRelations($category, $course, $session, $removedIds);
        $this->insertUserGroupRelations($category, $course, $session, $addedIds);

        if ([] === $selectedIds) {
            foreach ($category->getUsers()->toArray() as $relation) {
                if ($relation instanceof CLpCategoryRelUser) {
                    $category->removeUsers($relation);
                }
            }

            return;
        }

        foreach ($removedIds as $userGroupId) {
            $userGroup = $allowedGroups[$userGroupId] ?? $this->entityManager->getRepository(Usergroup::class)->find($userGroupId);
            if (!$userGroup instanceof Usergroup) {
                continue;
            }
            foreach ($userGroup->getUsers() as $member) {
                $user = $member->getUser();
                foreach ($category->getUsers()->toArray() as $relation) {
                    if ($relation instanceof CLpCategoryRelUser && $relation->getUser()?->getId() === $user->getId()) {
                        $category->removeUsers($relation);
                    }
                }
            }
        }

        foreach ($selectedIds as $userGroupId) {
            $userGroup = $allowedGroups[$userGroupId] ?? null;
            if (!$userGroup instanceof Usergroup) {
                continue;
            }
            foreach ($userGroup->getUsers() as $member) {
                $user = $member->getUser();
                if (!$category->hasUserAdded($user)) {
                    $category->addUser((new CLpCategoryRelUser())->setUser($user));
                }
            }
        }
    }

    /** @return array<int, User> */
    private function getAllowedUsers(Course $course, ?Session $session): array
    {
        $users = [];

        if (null === $session) {
            foreach ($course->getStudentSubscriptions() as $subscription) {
                if ($subscription instanceof CourseRelUser) {
                    $users[(int) $subscription->getUser()->getId()] = $subscription->getUser();
                }
            }

            return $users;
        }

        foreach ($session->getSessionRelCourseRelUsers() as $subscription) {
            if (!$subscription instanceof SessionRelCourseRelUser
                || Session::STUDENT !== $subscription->getStatus()
                || $subscription->getCourse()->getId() !== $course->getId()
            ) {
                continue;
            }
            $users[(int) $subscription->getUser()->getId()] = $subscription->getUser();
        }

        return $users;
    }

    /** @return array<int, CGroup> */
    private function getAllowedGroups(Course $course, ?Session $session): array
    {
        /** @var array<int, CGroup> $groups */
        $groups = $this->groupRepository->getResourcesByCourse($course, $session)->getQuery()->getResult();
        $map = [];
        foreach ($groups as $group) {
            if (null !== $group->getIid()) {
                $map[(int) $group->getIid()] = $group;
            }
        }

        return $map;
    }

    /** @return array<int, Usergroup> */
    private function getAllowedUserGroups(Course $course): array
    {
        /** @var array<int, Usergroup> $groups */
        $groups = $this->entityManager->createQueryBuilder()
            ->select('usergroup')
            ->from(Usergroup::class, 'usergroup')
            ->innerJoin('usergroup.courses', 'courseRelation')
            ->andWhere('IDENTITY(courseRelation.course) = :courseId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $map = [];
        foreach ($groups as $group) {
            if (null !== $group->getId()) {
                $map[(int) $group->getId()] = $group;
            }
        }

        return $map;
    }

    /**
     * @param array<int, int> $selectedIds
     * @param array<int, int> $allowedIds
     */
    private function assertAllowedIds(array $selectedIds, array $allowedIds, string $message): void
    {
        if ([] !== array_diff($selectedIds, $allowedIds)) {
            throw new AccessDeniedHttpException($message);
        }
    }

    /** @return array<int, int> */
    private function getSelectedUserGroupIds(CLpCategory $category, Course $course, ?Session $session): array
    {
        [$sessionCondition, $parameters, $types] = $this->getUserGroupRelationContext($category, $course, $session);
        $ids = $this->entityManager->getConnection()->executeQuery(
            'SELECT usergroup_id FROM c_lp_category_rel_usergroup
             WHERE c_id = :courseId
               AND lp_category_id = :categoryId
               AND '.$sessionCondition,
            $parameters,
            $types,
        )->fetchFirstColumn();

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /** @param array<int, int> $ids */
    private function deleteUserGroupRelations(
        CLpCategory $category,
        Course $course,
        ?Session $session,
        array $ids,
    ): void {
        if ([] === $ids) {
            return;
        }

        [$sessionCondition, $parameters, $types] = $this->getUserGroupRelationContext($category, $course, $session);
        $parameters['ids'] = $ids;
        $types['ids'] = ArrayParameterType::INTEGER;

        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM c_lp_category_rel_usergroup
             WHERE c_id = :courseId
               AND lp_category_id = :categoryId
               AND '.$sessionCondition.'
               AND usergroup_id IN (:ids)',
            $parameters,
            $types,
        );
    }

    /** @param array<int, int> $ids */
    private function insertUserGroupRelations(
        CLpCategory $category,
        Course $course,
        ?Session $session,
        array $ids,
    ): void {
        $connection = $this->entityManager->getConnection();
        foreach ($ids as $userGroupId) {
            $data = [
                'lp_category_id' => (int) $category->getIid(),
                'c_id' => (int) $course->getId(),
                'usergroup_id' => $userGroupId,
                'created_at' => new DateTime('now', new DateTimeZone('UTC')),
            ];
            $types = [
                'lp_category_id' => Types::INTEGER,
                'c_id' => Types::INTEGER,
                'usergroup_id' => Types::INTEGER,
                'created_at' => Types::DATETIME_MUTABLE,
            ];
            if (null !== $session) {
                $data['session_id'] = (int) $session->getId();
                $types['session_id'] = Types::INTEGER;
            }
            $connection->insert('c_lp_category_rel_usergroup', $data, $types);
        }
    }

    /**
     * @return array{0: string, 1: array<string, int>, 2: array<string, string>}
     */
    private function getUserGroupRelationContext(
        CLpCategory $category,
        Course $course,
        ?Session $session,
    ): array {
        $parameters = [
            'courseId' => (int) $course->getId(),
            'categoryId' => (int) $category->getIid(),
        ];
        $types = [
            'courseId' => Types::INTEGER,
            'categoryId' => Types::INTEGER,
        ];

        if (null === $session) {
            return ['session_id IS NULL', $parameters, $types];
        }

        $parameters['sessionId'] = (int) $session->getId();
        $types['sessionId'] = Types::INTEGER;

        return ['session_id = :sessionId', $parameters, $types];
    }
}
