<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AnnouncementEmailRecipientResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AnnouncementRecipientResolver $recipientResolver,
        private UserRepository $userRepository,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    /**
     * @param array<int, string> $selection
     *
     * @return array<int, User>
     */
    public function resolvePrimaryRecipients(
        array $selection,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        bool $sendToUsersInSessions,
    ): array {
        $users = $this->recipientResolver->resolveUsers($selection, $course, $session, $group);

        if ($sendToUsersInSessions) {
            foreach ($this->getUsersFromAllCourseSessions($course) as $userId => $user) {
                $users[$userId] = $user;
            }
        }

        $this->sortUsers($users);

        return $users;
    }

    /**
     * @param array<int, User> $recipientUsers
     *
     * @return array<int, array{user: User, relatedUsers: array<int, User>}>
     */
    public function resolveHrmCopies(array $recipientUsers): array
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (null === $accessUrl || null === $accessUrl->getId()) {
            return [];
        }

        /** @var array<int, array<int, User>> $relatedUsersByHrm */
        $relatedUsersByHrm = [];
        $hrmIds = [];

        foreach ($recipientUsers as $recipientUser) {
            if (null === $recipientUser->getId()) {
                continue;
            }

            $hrmList = $this->userRepository->getDrhListFromUser(
                (int) $recipientUser->getId(),
                (int) $accessUrl->getId(),
            );

            foreach ($hrmList as $hrmData) {
                $hrmId = (int) ($hrmData['id'] ?? 0);
                if ($hrmId <= 0) {
                    continue;
                }

                $hrmIds[$hrmId] = $hrmId;
                $relatedUsersByHrm[$hrmId][(int) $recipientUser->getId()] = $recipientUser;
            }
        }

        if ([] === $hrmIds) {
            return [];
        }

        $hrmUsers = $this->entityManager->getRepository(User::class)->findBy([
            'id' => array_values($hrmIds),
            'active' => User::ACTIVE,
        ]);

        $copies = [];
        foreach ($hrmUsers as $hrmUser) {
            if (!$hrmUser instanceof User || null === $hrmUser->getId()) {
                continue;
            }

            $hrmId = (int) $hrmUser->getId();
            $relatedUsers = $relatedUsersByHrm[$hrmId] ?? [];
            $this->sortUsers($relatedUsers);

            $copies[$hrmId] = [
                'user' => $hrmUser,
                'relatedUsers' => $relatedUsers,
            ];
        }

        uasort(
            $copies,
            static fn (array $left, array $right): int => strcasecmp(
                $left['user']->getFullName(),
                $right['user']->getFullName(),
            ),
        );

        return $copies;
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
        bool $sendToUsersInSessions,
        bool $sendToHrmUsers,
        bool $sendCopyToSelf,
        User $sender,
    ): array {
        $users = $this->resolvePrimaryRecipients(
            $selection,
            $course,
            $session,
            $group,
            $sendToUsersInSessions,
        );

        if ($sendToHrmUsers) {
            foreach ($this->resolveHrmCopies($users) as $hrmId => $copy) {
                $users[$hrmId] = $copy['user'];
            }
        }

        if ($sendCopyToSelf && null !== $sender->getId()) {
            $users[(int) $sender->getId()] = $sender;
        }

        $labels = [];
        foreach ($users as $user) {
            $labels[] = $this->formatUserLabel($user);
        }

        if ([] === $labels && \in_array('everyone', $selection, true)) {
            $labels[] = \function_exists('get_lang') ? (string) get_lang('Everyone') : 'Everyone';
        }

        natcasesort($labels);

        return array_values(array_unique($labels));
    }

    /**
     * @return array<int, User>
     */
    private function getUsersFromAllCourseSessions(Course $course): array
    {
        $subscriptions = $this->entityManager->createQueryBuilder()
            ->select('subscription', 'u')
            ->from(SessionRelCourseRelUser::class, 'subscription')
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

        $users = [];
        foreach ($subscriptions as $subscription) {
            if (!$subscription instanceof SessionRelCourseRelUser) {
                continue;
            }

            $user = $subscription->getUser();
            if (User::ACTIVE !== $user->getActive() || null === $user->getId()) {
                continue;
            }

            $users[(int) $user->getId()] = $user;
        }

        return $users;
    }

    /**
     * @param array<int, User> $users
     */
    private function sortUsers(array &$users): void
    {
        uasort(
            $users,
            static fn (User $left, User $right): int => strcasecmp($left->getFullName(), $right->getFullName()),
        );
    }

    private function formatUserLabel(User $user): string
    {
        $fullName = trim($user->getFullName());
        if ('' === $fullName) {
            return $user->getUsername();
        }

        return $fullName.' ('.$user->getUsername().')';
    }
}
