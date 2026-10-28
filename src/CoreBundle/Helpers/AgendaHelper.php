<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Security\CourseAccessResolver;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class AgendaHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
        private CourseAccessResolver $courseAccessResolver,
        private SettingsCourseManager $settingsCourseManager,
        private CidReqHelper $cidReqHelper,
        private UserHelper $userHelper,
        private RequestStack $requestStack,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * Whether the current user may add an event with these resource links to the agenda of
     * each course they name: the course editors, or anyone with access to the course when the
     * course setting "Allow learners to edit agenda" is on (the rule of the agenda add button).
     * A user link is an invitation, see canInviteUser().
     */
    public function canAddLinks(array $links): bool
    {
        $user = $this->userHelper->getCurrent();

        if (null === $user) {
            return false;
        }

        foreach ($links as $link) {
            if (!\is_array($link)) {
                return false;
            }

            if (empty($link['cid'])) {
                // A session or group link always belongs to a course.
                if (!empty($link['sid']) || !empty($link['gid'])) {
                    return false;
                }

                if (!empty($link['uid']) && !$this->canInviteUser($user, (int) $link['uid'])) {
                    return false;
                }

                continue;
            }

            $course = $this->entityManager->find(Course::class, (int) $link['cid']);
            if (!$course instanceof Course) {
                return false;
            }

            $session = null;
            if (!empty($link['sid'])) {
                $session = $this->entityManager->find(Session::class, (int) $link['sid']);
                if (!$session instanceof Session) {
                    return false;
                }
            }

            if (!$this->isCourseRecipient($link, $course, $session)) {
                return false;
            }

            if ($this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)) {
                continue;
            }

            $this->settingsCourseManager->setCourse($course);

            if ('1' !== $this->settingsCourseManager->getCourseSettingValue('allow_user_edit_agenda')
                || [] === $this->courseAccessResolver->resolveCourseRoles($user, $course, $session)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the current user sees the event in their agenda, as Agenda::getCourseEvents() decided:
     * the teachers of a course see all its events, a member sees the events of the whole course,
     * of their groups and the events sent to them.
     */
    public function canViewEvent(CCalendarEvent $event): bool
    {
        $user = $this->userHelper->getCurrent();
        $resourceNode = $event->getResourceNode();

        if (null === $user || null === $resourceNode) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN') || $resourceNode->getCreator()?->getId() === $user->getId()) {
            return true;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            $linkUser = $link->getUser();
            if (null !== $linkUser) {
                if ($linkUser->getId() === $user->getId()) {
                    return true;
                }

                continue;
            }

            $course = $link->getCourse();
            if (null === $course) {
                continue;
            }

            $session = $link->getSession();
            $isTeacher = $session instanceof Session
                ? $session->hasUserAsGeneralCoach($user) || $session->hasCourseCoachInCourse($user, $course)
                : $course->hasUserAsTeacher($user);

            if ($isTeacher) {
                return true;
            }

            $isMember = $session instanceof Session
                ? $session->hasUserInCourse($user, $course)
                : $course->hasSubscriptionByUser($user);
            $group = $link->getGroup();

            if ($isMember
                && (null === $group || \in_array((int) $group->getIid(), $this->getGroupIdList($user, (int) $course->getId()), true))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * The course groups the user is a member or a tutor of, in one course or in all of them.
     *
     * @return list<int>
     */
    public function getGroupIdList(User $user, ?int $courseId = null): array
    {
        $groupIdList = [];

        foreach ([$user->getCourseGroupsAsMember(), $user->getCourseGroupsAsTutor()] as $subscriptions) {
            foreach ($subscriptions as $subscription) {
                if (null === $courseId || $courseId === (int) $subscription->getCId()) {
                    $groupIdList[] = (int) $subscription->getGroup()->getIid();
                }
            }
        }

        return array_values(array_unique($groupIdList));
    }

    /**
     * A course event sent to a user or a group can only be sent to a user subscribed to the course
     * (or to the course in the session) and to a group of the course.
     */
    private function isCourseRecipient(array $link, Course $course, ?Session $session): bool
    {
        if (!empty($link['uid'])) {
            $recipient = $this->entityManager->find(User::class, (int) $link['uid']);

            if (!$recipient instanceof User) {
                return false;
            }

            $isSubscribed = $session instanceof Session
                ? $session->hasUserInCourse($recipient, $course)
                : $course->hasSubscriptionByUser($recipient);

            if (!$isSubscribed) {
                return false;
            }
        }

        if (!empty($link['gid'])) {
            $group = $this->entityManager->find(CGroup::class, (int) $link['gid']);

            if (!$group instanceof CGroup) {
                return false;
            }

            foreach ($group->getResourceNode()?->getResourceLinks() ?? [] as $groupLink) {
                if ($groupLink->getCourse()?->getId() === $course->getId()) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    /**
     * An invitation shows the event in the invitee's own calendar, so it is limited to the
     * contacts the invitation picker offers, unless the platform lets users message everyone.
     * A pending request is not a contact yet.
     */
    private function canInviteUser(User $user, int $inviteeId): bool
    {
        if ($inviteeId === $user->getId()
            || $this->security->isGranted('ROLE_ADMIN')
            || 'true' === $this->settingsManager->getSetting('message.allow_send_message_to_all_platform_users')
        ) {
            return true;
        }

        $relations = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(relation.id)')
            ->from(UserRelUser::class, 'relation')
            ->where('(relation.user = :user AND relation.friend = :invitee) OR (relation.user = :invitee AND relation.friend = :user)')
            ->andWhere('relation.relationType NOT IN (:excludedTypes)')
            ->setParameter('user', (int) $user->getId())
            ->setParameter('invitee', $inviteeId)
            ->setParameter('excludedTypes', [
                UserRelUser::USER_RELATION_TYPE_DELETED,
                UserRelUser::USER_RELATION_TYPE_FRIEND_REQUEST,
                UserRelUser::USER_RELATION_TYPE_HRM_REQUEST,
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $relations > 0;
    }

    /**
     * The course links a new event really gets: ResourceListener::normalizeSingleLinkContextFromSession()
     * replaces the context of a single link with the course of the request.
     */
    public function getNewEventLinks(array $links): array
    {
        if (1 !== \count($links) || !\is_array($links[0] ?? null)
            || !empty($links[0]['uid']) || !empty($links[0]['ugid'])
        ) {
            return $links;
        }

        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (!$course instanceof Course) {
            return $links;
        }

        // Without a group context, the listener keeps the group the event is sent to.
        $groupId = $this->cidReqHelper->getGroupId() ?: ($links[0]['gid'] ?? null);

        return [[
            'cid' => $course->getId(),
            'sid' => $this->cidReqHelper->getDoctrineSessionEntity()?->getId(),
            'gid' => $groupId,
        ]];
    }

    /**
     * Whether the request asks for a global event, read as ResourceListener::addCCalendarEventGlobalLink() does.
     */
    public function isGlobalEventRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return false;
        }

        $type = $request->query->get('type');
        if (null !== $type) {
            return 'global' === $type;
        }

        $payload = json_decode($request->getContent(), true);

        return \is_array($payload) && isset($payload['isGlobal']) && 1 === (int) $payload['isGlobal'];
    }
}
