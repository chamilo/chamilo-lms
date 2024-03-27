<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\ServiceHelper\CidReqHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use UserGroupModel;

final class CCalendarEventExtension implements QueryCollectionExtensionInterface
{
    use CourseLinkExtensionTrait;

    public function __construct(
        private readonly Security $security,
        private readonly CidReqHelper $cidReqHelper,
        private readonly SettingsManager $settingsManager,
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

        $isGlobalType = isset($context['filters']['type']) && $context['filters']['type'] === 'global';
        if ($isGlobalType) {
            return;
        }

        $courseId = $this->cidReqHelper->getCourseId();
        $sessionId = $this->cidReqHelper->getSessionId();
        $groupId = $this->cidReqHelper->getGroupId();

        /** @var ?User $user */
        $user = $this->security->getUser();

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
        }
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

        if ('true' === $this->settingsManager->getSetting('agenda.agenda_event_subscriptions')) {
            $this->addSubscriptionsConditions($qb, $user);
        }
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
}
