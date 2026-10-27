<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use UserGroupModel;

final class CCalendarEventExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly UserHelper $userHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly SettingsManager $settingsManager,
        private readonly CourseRepository $courseRepository,
        private readonly SessionRepository $sessionRepository,
        private readonly Security $security,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass, $context);
    }

    private function addWhere(QueryBuilder $qb, string $resourceClass, array $context): void
    {
        if (CCalendarEvent::class !== $resourceClass) {
            return;
        }

        $isGlobalType = isset($context['filters']['type']) && 'global' === $context['filters']['type'];
        if ($isGlobalType) {
            return;
        }

        $courseId = $this->cidReqHelper->getCourseId();
        $sessionId = $this->cidReqHelper->getSessionId();
        $groupId = $this->cidReqHelper->getGroupId();
        $user = $this->userHelper->getCurrent();
        $accessUrl = $this->accessUrlHelper->getCurrent();

        $inCourseBase = !empty($courseId);
        $inSession = !empty($sessionId);
        $inCourseSession = $inCourseBase && $inSession;

        $inPersonalList = !$inCourseBase && !$inCourseSession;

        $alias = $qb->getRootAliases()[0];

        $qb
            ->innerJoin("$alias.resourceNode", 'node')
            ->leftJoin('node.resourceLinks', 'resource_links')
        ;

        if ($inPersonalList && $user) {
            $this->addPersonalCalendarConditions($qb, $user);
            $this->addCourseConditions($qb, $user, $accessUrl);
            $this->addSessionConditions($qb, $user, $accessUrl);

            return;
        }

        if (!$inCourseBase || null === $user) {
            return;
        }

        if (!$this->canSeeAllCourseEvents(!empty($groupId))) {
            // Inside a group, GroupContextQueryExtension already requires a link to that group.
            $recipients = empty($groupId)
                ? $this->getRecipientCondition($qb, $user, $this->getGroupIdList($user, (int) $courseId), true)
                : $this->getRecipientCondition($qb, $user, [(int) $groupId], false);

            $qb->andWhere($recipients);

            return;
        }

        $this->addRecipientFilter($qb, $context['filters']['recipient'] ?? null);
    }

    /**
     * Course editors can list only the events sent to one user ("USER:<id>") or to one group
     * ("GROUP:<id>"), as the user filter of the legacy agenda did.
     */
    private function addRecipientFilter(QueryBuilder $qb, mixed $recipient): void
    {
        if (!\is_string($recipient) || !preg_match('/^(USER|GROUP):(\d+)$/', $recipient, $matches)) {
            return;
        }

        $field = 'USER' === $matches[1] ? 'resource_links.user' : 'resource_links.group';

        $qb
            ->andWhere("$field = :recipient_filter")
            ->setParameter('recipient_filter', (int) $matches[2])
        ;
    }

    /**
     * Course editors see the events sent to any user or group, as in Agenda::getCourseEvents().
     * The context roles are read from the User, see CourseLinkExtensionTrait::canViewDraftResources().
     */
    private function canSeeAllCourseEvents(bool $inGroup): bool
    {
        $roles = $this->security->getUser()?->getRoles() ?? [];

        return $this->security->isGranted('ROLE_ADMIN')
            || \in_array('ROLE_CURRENT_COURSE_TEACHER', $roles, true)
            || \in_array('ROLE_CURRENT_COURSE_SESSION_TEACHER', $roles, true)
            || ($inGroup && \in_array('ROLE_CURRENT_COURSE_GROUP_TEACHER', $roles, true));
    }

    /**
     * A course link sent to a user or a group is only for that user, or for the members and
     * tutors of that group.
     *
     * @param list<int> $groupIdList the groups of the user that may receive the event
     */
    private function getRecipientCondition(QueryBuilder $qb, User $user, array $groupIdList, bool $withoutGroup): Andx
    {
        $qb->setParameter('recipient_user', $user->getId());

        $groupCondition = $qb->expr()->orX();

        if ($withoutGroup) {
            $groupCondition->add($qb->expr()->isNull('resource_links.group'));
        }

        if ($groupIdList) {
            $groupCondition->add($qb->expr()->in('resource_links.group', ':recipient_group_list'));
            $qb->setParameter('recipient_group_list', $groupIdList);
        }

        return $qb->expr()->andX(
            $qb->expr()->orX(
                $qb->expr()->isNull('resource_links.user'),
                $qb->expr()->eq('resource_links.user', ':recipient_user')
            ),
            $groupCondition
        );
    }

    /**
     * The course groups the user is a member or a tutor of, in one course or in all of them.
     *
     * @return list<int>
     */
    private function getGroupIdList(User $user, ?int $courseId = null): array
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

    private function addPersonalCalendarConditions(QueryBuilder $qb, User $user): void
    {
        $qb
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('resource_links.user', ':user'),
                    $qb->expr()->eq('node.creator', ':user')
                )
            )
            ->setParameter('user', $user->getId())
        ;

        $this->addSubscriptionsConditions($qb, $user);
    }

    private function addSubscriptionsConditions(QueryBuilder $qb, User $user): void
    {
        $groupList = (new UserGroupModel())->getUserGroupListByUser($user->getId(), Usergroup::NORMAL_CLASS);
        $groupIdList = $groupList ? array_column($groupList, 'id') : [];

        $alias = $qb->getRootAliases()[0];

        $expr = $qb->expr()->orX(
            $qb->expr()->eq("$alias.subscriptionVisibility", ':visibility_all'),
        );

        if ($groupIdList) {
            $expr->add(
                $qb->expr()->orX(
                    $qb->expr()->eq("$alias.subscriptionVisibility", ':visibility_class'),
                    $qb->expr()->in("$alias.subscriptionItemId", ':item_id_list')
                )
            );

            $qb->setParameter('visibility_class', CCalendarEvent::SUBSCRIPTION_VISIBILITY_CLASS);
            $qb->setParameter('item_id_list', $groupIdList);
        }

        $qb
            ->orWhere($expr)
            ->setParameter(':visibility_all', CCalendarEvent::SUBSCRIPTION_VISIBILITY_ALL)
        ;
    }

    private function addCourseConditions(QueryBuilder $qb, User $user, AccessUrl $accessUrl): void
    {
        $courseSubscriptions = $this->courseRepository->getCoursesByUser($user, $accessUrl);

        $courseIdList = [];

        foreach ($courseSubscriptions as $courseSubscription) {
            $courseIdList[] = $courseSubscription->getCourse()->getId();
        }

        if ($courseIdList) {
            $qb
                ->orWhere(
                    $qb->expr()->andX(
                        $qb->expr()->in('resource_links.course', ':course_id_list'),
                        $qb->expr()->isNull('resource_links.session'),
                        $this->getRecipientCondition($qb, $user, $this->getGroupIdList($user), true)
                    )
                )
                ->setParameter('course_id_list', $courseIdList)
            ;
        }
    }

    private function addSessionConditions(QueryBuilder $qb, User $user, AccessUrl $accessUrl): void
    {
        $sessionIdList = [];
        $courseIdList = [];

        if ($user->isHRM()
            && 'true' === $this->settingsManager->getSetting('session.hrm_can_access_all_session_content')
        ) {
            $sessions = $this->sessionRepository
                ->getUserFollowedSessionsInAccessUrl($user, $accessUrl)
                ->getQuery()
                ->getResult()
            ;

            foreach ($sessions as $session) {
                foreach ($session->getCourses() as $sessionRelCourse) {
                    $courseIdList[] = $sessionRelCourse->getCourse()->getId();
                }

                $sessionIdList[] = $session->getId();
            }
        } else {
            $sessions = $this->sessionRepository->getSessionsByUser($user, $accessUrl)->getQuery()->getResult();

            foreach ($sessions as $session) {
                foreach ($session->getSessionRelCourseByUser($user) as $sessionRelCourse) {
                    $courseIdList[] = $sessionRelCourse->getCourse()->getId();
                }

                $sessionIdList[] = $session->getId();
            }
        }

        if ($sessionIdList && $courseIdList) {
            $qb
                ->orWhere(
                    $qb->expr()->andX(
                        $qb->expr()->in('resource_links.session', ':session_id_list'),
                        $qb->expr()->in('resource_links.course', ':session_course_id_list'),
                        $this->getRecipientCondition($qb, $user, $this->getGroupIdList($user), true)
                    )
                )
                ->setParameter('session_id_list', array_unique($sessionIdList))
                ->setParameter('session_course_id_list', $courseIdList)
            ;
        }
    }
}
