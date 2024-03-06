<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
// use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
// use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\ServiceHelper\CidReqHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Doctrine\ORM\QueryBuilder;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

final class CCalendarEventExtension implements QueryCollectionExtensionInterface // , QueryItemExtensionInterface
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
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $qb, string $resourceClass): void
    {
        if (CCalendarEvent::class !== $resourceClass) {
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
    }
}
