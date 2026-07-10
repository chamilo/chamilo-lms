<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class AnnouncementRecipientResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CGroupRepository $groupRepository,
        private UsergroupRepository $usergroupRepository,
    ) {}

    /**
     * @return array{
     *     options: array<int, array{value: string, label: string, type: string}>,
     *     classes: array<int, array{id: int, label: string, recipientValues: array<int, string>}>
     * }
     */
    public function getFormData(Course $course, ?Session $session, ?CGroup $group): array
    {
        $users = $this->getAvailableUsers($course, $session, $group);
        $groups = $this->getAvailableGroups($course, $session, $group);

        $options = [
            [
                'value' => 'everyone',
                'label' => $this->translate('Everyone'),
                'type' => 'everyone',
            ],
        ];

        foreach ($groups as $recipientGroup) {
            $groupId = (int) $recipientGroup->getIid();
            if ($groupId <= 0) {
                continue;
            }

            $options[] = [
                'value' => 'GROUP:'.$groupId,
                'label' => $this->translate('Group').': '.$recipientGroup->getTitle(),
                'type' => 'group',
            ];
        }

        foreach ($users as $user) {
            $userId = (int) $user->getId();
            if ($userId <= 0) {
                continue;
            }

            $options[] = [
                'value' => 'USER:'.$userId,
                'label' => $this->formatUserLabel($user),
                'type' => 'user',
            ];
        }

        return [
            'options' => $options,
            'classes' => $this->getClassOptions($course, $session, $group, $users),
        ];
    }

    /**
     * @param array<int, mixed> $rawRecipients
     *
     * @return array<int, string>
     */
    public function normalizeSelection(
        array $rawRecipients,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $selection = [];
        foreach ($rawRecipients as $rawRecipient) {
            if (!\is_scalar($rawRecipient)) {
                continue;
            }

            $recipient = trim((string) $rawRecipient);
            if ('' === $recipient) {
                continue;
            }

            $selection[$recipient] = $recipient;
        }

        $selection = array_values($selection);
        if ([] === $selection) {
            return ['everyone'];
        }

        if (\in_array('everyone', $selection, true)) {
            if (1 !== \count($selection)) {
                throw new BadRequestHttpException('Everyone cannot be combined with other recipients.');
            }

            return ['everyone'];
        }

        $availableUsers = $this->getAvailableUsers($course, $session, $group);
        $availableGroups = $this->getAvailableGroups($course, $session, $group);
        $userIds = array_fill_keys(array_keys($availableUsers), true);
        $groupIds = array_fill_keys(array_keys($availableGroups), true);
        $normalized = [];

        foreach ($selection as $recipient) {
            if (1 !== preg_match('/^(USER|GROUP):(\d+)$/', $recipient, $matches)) {
                throw new BadRequestHttpException('An announcement recipient is invalid.');
            }

            $type = $matches[1];
            $id = (int) $matches[2];
            if ('USER' === $type && isset($userIds[$id])) {
                $normalized[] = 'USER:'.$id;

                continue;
            }

            if ('GROUP' === $type && null === $group && isset($groupIds[$id])) {
                $normalized[] = 'GROUP:'.$id;

                continue;
            }

            throw new BadRequestHttpException('An announcement recipient does not belong to the current course context.');
        }

        if ([] === $normalized) {
            throw new BadRequestHttpException('At least one announcement recipient is required.');
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<int, string> $selection
     *
     * @return array<int, User>
     */
    public function resolveUsers(
        array $selection,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $availableUsers = $this->getAvailableUsers($course, $session, $group);
        $availableGroups = $this->getAvailableGroups($course, $session, $group);
        $resolvedUsers = [];

        if (\in_array('everyone', $selection, true)) {
            return $availableUsers;
        }

        foreach ($selection as $recipient) {
            [$type, $rawId] = explode(':', $recipient, 2);
            $id = (int) $rawId;

            if ('USER' === $type && isset($availableUsers[$id])) {
                $resolvedUsers[$id] = $availableUsers[$id];

                continue;
            }

            if ('GROUP' !== $type || !isset($availableGroups[$id])) {
                continue;
            }

            foreach ($availableGroups[$id]->getMembers() as $member) {
                $user = $member->getUser();
                if (!$user instanceof User || User::ACTIVE !== $user->getActive() || null === $user->getId()) {
                    continue;
                }

                $resolvedUsers[(int) $user->getId()] = $user;
            }
        }

        return $resolvedUsers;
    }

    /**
     * @param array<int, string> $selection
     *
     * @return array<int, string>
     */
    public function getPreviewLabels(
        array $selection,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $previewUsers = $this->resolveUsers($selection, $course, $session, $group);
        $labels = array_map(
            fn (User $user): string => $this->formatUserLabel($user),
            array_values($previewUsers),
        );

        if ([] === $labels && \in_array('everyone', $selection, true)) {
            $labels[] = $this->translate('Everyone');
        }

        natcasesort($labels);

        return array_values(array_unique($labels));
    }

    /**
     * @return array<int, string>
     */
    public function getSelectedRecipients(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $selected = [];

        foreach ($this->getScopedLinks($announcement, $course, $session, $group) as $link) {
            $linkedUser = $link->getUser();
            $linkedGroup = $link->getGroup();

            if ($linkedUser instanceof User && null !== $linkedUser->getId()) {
                $selected[] = 'USER:'.(int) $linkedUser->getId();

                continue;
            }

            if ($linkedGroup instanceof CGroup && null !== $linkedGroup->getIid()) {
                if ($group instanceof CGroup && $linkedGroup->getIid() === $group->getIid()) {
                    $selected[] = 'everyone';
                } else {
                    $selected[] = 'GROUP:'.(int) $linkedGroup->getIid();
                }

                continue;
            }

            $selected[] = 'everyone';
        }

        $selected = array_values(array_unique($selected));
        if (\in_array('everyone', $selected, true)) {
            return ['everyone'];
        }

        return [] !== $selected ? $selected : ['everyone'];
    }

    /**
     * @return array<int, ResourceLink>
     */
    public function getScopedLinks(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $resourceNode = $announcement->getResourceNode();
        if (null === $resourceNode) {
            return [];
        }

        $links = [];
        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink || null !== $link->getDeletedAt()) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $linkGroup = $link->getGroup();

            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();
            $sameGroup = null === $group
                || (null !== $linkGroup && $linkGroup->getIid() === $group->getIid());

            if ($sameCourse && $sameSession && $sameGroup) {
                $links[] = $link;
            }
        }

        return $links;
    }

    public function hasMultipleGroupTargets(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
    ): bool {
        $groupIds = [];
        foreach ($this->getScopedLinks($announcement, $course, $session, null) as $link) {
            $linkedGroup = $link->getGroup();
            if (!$linkedGroup instanceof CGroup || null === $linkedGroup->getIid()) {
                continue;
            }

            $groupIds[(int) $linkedGroup->getIid()] = true;
        }

        return \count($groupIds) > 1;
    }

    /**
     * @param array<int, string> $selection
     */
    public function replaceRecipientLinks(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        array $selection,
    ): void {
        $existingLinks = $this->getScopedLinks($announcement, $course, $session, $group);
        $visibility = ResourceLink::VISIBILITY_PUBLISHED;
        $displayOrder = null;

        if ([] !== $existingLinks) {
            $visibility = $this->resolveVisibility($existingLinks);
            $displayOrder = min(array_map(
                static fn (ResourceLink $link): int => $link->getDisplayOrder(),
                $existingLinks,
            ));
        }

        $resourceNode = $announcement->getResourceNode();
        foreach ($existingLinks as $link) {
            if (null !== $resourceNode) {
                $resourceNode->getResourceLinks()->removeElement($link);
            }
            $this->entityManager->remove($link);
        }

        $users = $this->getAvailableUsers($course, $session, $group);
        $groups = $this->getAvailableGroups($course, $session, $group);

        if (\in_array('everyone', $selection, true)) {
            $this->addRecipientLink(
                $announcement,
                $course,
                $session,
                $group,
                null,
                $visibility,
                $displayOrder,
            );

            return;
        }

        foreach ($selection as $recipient) {
            [$type, $rawId] = explode(':', $recipient, 2);
            $id = (int) $rawId;

            if ('USER' === $type && isset($users[$id])) {
                $this->addRecipientLink(
                    $announcement,
                    $course,
                    $session,
                    $group,
                    $users[$id],
                    $visibility,
                    $displayOrder,
                );

                continue;
            }

            if ('GROUP' === $type && isset($groups[$id])) {
                $this->addRecipientLink(
                    $announcement,
                    $course,
                    $session,
                    $groups[$id],
                    null,
                    $visibility,
                    $displayOrder,
                );
            }
        }
    }

    /**
     * @return array<int, User>
     */
    private function getAvailableUsers(Course $course, ?Session $session, ?CGroup $group): array
    {
        if ($group instanceof CGroup) {
            $users = [];
            foreach ($group->getMembers() as $member) {
                $user = $member->getUser();
                if (!$user instanceof User || User::ACTIVE !== $user->getActive() || null === $user->getId()) {
                    continue;
                }

                $users[(int) $user->getId()] = $user;
            }

            return $users;
        }

        if ($session instanceof Session) {
            $subscriptions = $this->entityManager->createQueryBuilder()
                ->select('subscription', 'u')
                ->from(SessionRelCourseRelUser::class, 'subscription')
                ->innerJoin('subscription.user', 'u')
                ->andWhere('subscription.course = :course')
                ->andWhere('subscription.session = :session')
                ->andWhere('u.active = :active')
                ->orderBy('u.lastname', 'ASC')
                ->addOrderBy('u.firstname', 'ASC')
                ->setParameter('course', (int) $course->getId(), Types::INTEGER)
                ->setParameter('session', (int) $session->getId(), Types::INTEGER)
                ->setParameter('active', User::ACTIVE, Types::INTEGER)
                ->getQuery()
                ->getResult()
            ;

            return $this->extractUsers($subscriptions);
        }

        $subscriptions = $this->entityManager->createQueryBuilder()
            ->select('subscription', 'u')
            ->from(CourseRelUser::class, 'subscription')
            ->innerJoin('subscription.user', 'u')
            ->andWhere('subscription.course = :course')
            ->andWhere('u.active = :active')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->setParameter('course', (int) $course->getId(), Types::INTEGER)
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        return $this->extractUsers($subscriptions);
    }

    /**
     * @return array<int, CGroup>
     */
    private function getAvailableGroups(Course $course, ?Session $session, ?CGroup $group): array
    {
        if ($group instanceof CGroup) {
            return [];
        }

        $results = $this->groupRepository
            ->getResourcesByCourse($course, $session)
            ->getQuery()
            ->getResult()
        ;

        $groups = [];
        foreach ($results as $result) {
            if (!$result instanceof CGroup || !$result->getStatus() || null === $result->getIid()) {
                continue;
            }

            $groups[(int) $result->getIid()] = $result;
        }

        uasort(
            $groups,
            static fn (CGroup $left, CGroup $right): int => strcasecmp($left->getTitle(), $right->getTitle()),
        );

        return $groups;
    }

    /**
     * @param array<int, User> $availableUsers
     *
     * @return array<int, array{id: int, label: string, recipientValues: array<int, string>}>
     */
    private function getClassOptions(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        array $availableUsers,
    ): array {
        if ($group instanceof CGroup) {
            return [];
        }

        $classes = $session instanceof Session
            ? $this->usergroupRepository->findBySession($session)
            : $this->usergroupRepository->findByCourse($course);

        $options = [];
        foreach ($classes as $class) {
            if (!$class instanceof Usergroup || null === $class->getId()) {
                continue;
            }

            $recipientValues = [];
            foreach ($class->getUsers() as $relation) {
                $user = $relation->getUser();
                if (!$user instanceof User || null === $user->getId()) {
                    continue;
                }

                $userId = (int) $user->getId();
                if (isset($availableUsers[$userId])) {
                    $recipientValues[] = 'USER:'.$userId;
                }
            }

            $options[] = [
                'id' => (int) $class->getId(),
                'label' => $class->getTitle(),
                'recipientValues' => array_values(array_unique($recipientValues)),
            ];
        }

        usort(
            $options,
            static fn (array $left, array $right): int => strcasecmp($left['label'], $right['label']),
        );

        return $options;
    }

    /**
     * @param array<int, object> $subscriptions
     *
     * @return array<int, User>
     */
    private function extractUsers(array $subscriptions): array
    {
        $users = [];
        foreach ($subscriptions as $subscription) {
            if (!$subscription instanceof CourseRelUser
                && !$subscription instanceof SessionRelCourseRelUser
            ) {
                continue;
            }

            $user = $subscription->getUser();
            if (!$user instanceof User || null === $user->getId()) {
                continue;
            }

            $users[(int) $user->getId()] = $user;
        }

        return $users;
    }

    private function addRecipientLink(
        CAnnouncement $announcement,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        ?User $user,
        int $visibility,
        ?int $displayOrder,
    ): void {
        $link = (new ResourceLink())
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
            ->setUser($user)
            ->setVisibility($visibility)
        ;

        if (null !== $displayOrder) {
            $link->setDisplayOrder($displayOrder);
        }

        $resourceNode = $announcement->getResourceNode();
        if (null !== $resourceNode) {
            $resourceNode->addResourceLink($link);

            return;
        }

        $announcement->addLink($link);
    }

    /**
     * @param array<int, ResourceLink> $links
     */
    private function resolveVisibility(array $links): int
    {
        foreach ($links as $link) {
            if ($link->isPublished()) {
                return ResourceLink::VISIBILITY_PUBLISHED;
            }
        }

        return $links[0]->getVisibility();
    }

    private function formatUserLabel(User $user): string
    {
        $fullName = trim($user->getFullName());
        if ('' === $fullName) {
            return $user->getUsername();
        }

        return $fullName.' ('.$user->getUsername().')';
    }

    private function translate(string $message): string
    {
        if (\function_exists('get_lang')) {
            return (string) \get_lang($message);
        }

        return $message;
    }
}
