<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CCalendarEvent;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

final class CCalendarEventExtension implements QueryCollectionExtensionInterface //, QueryItemExtensionInterface
{
    private Security $security;
    private RequestStack $requestStack;

    public function __construct(Security $security, RequestStack $request)
    {
        $this->security = $security;
        $this->requestStack = $request;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        /*if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }*/
        /*
        if ('collection_query' === $operationName) {
            if (null === $user = $this->security->getUser()) {
                throw new AccessDeniedException('Access Denied.');
            }

            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
            $queryBuilder->setParameter('current_user', $user);
        }*/

        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = []): void
    {
        //$this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $qb, string $resourceClass): void
    {
        if (CCalendarEvent::class !== $resourceClass) {
            return;
        }

        /*if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }*/

        /** @var User $user */
        $user = $this->security->getUser();
        $alias = $qb->getRootAliases()[0];

        $qb
            ->innerJoin("$alias.resourceNode", 'node')
            ->leftJoin('node.resourceLinks', 'links')
        ;

        $request = $this->requestStack->getCurrentRequest();
        $courseId = $request->query->get('cid');
        $sessionId = $request->query->get('sid');
        $groupId = $request->query->get('gid');

        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        if (!empty($startDate) && !empty($endDate)) {
            $qb->andWhere(
                "
                $alias.startDate BETWEEN :start AND :end OR
                $alias.endDate BETWEEN :start AND :end 
            "
            );
            $qb
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate);
        }

        if (empty($courseId)) {
            $qb
                ->andWhere('links.user = :user OR node.creator = :user')
                ->setParameter('user', $user)
            ;
        } else {
            $qb
                ->andWhere('links.course = :course')
                ->setParameter('course', $courseId)
            ;

            if (empty($sessionId)) {
                $qb->andWhere('links.session IS NULL');
            } else {
                $qb
                    ->andWhere('links.session = :session')
                    ->setParameter('session', $sessionId)
                ;
            }

            if (empty($groupId)) {
                $qb->andWhere('links.group IS NULL');
            } else {
                $qb
                    ->andWhere('links.group = :group')
                    ->setParameter('group', $groupId)
                ;
            }
        }

        //$qb->leftJoin("$alias.receivers", 'r');
        //$qb->leftJoin("$alias.receivers", 'r', Join::WITH, "r.receiver = :current OR $alias.sender = :current ");
        //$qb->leftJoin("$alias.receivers", 'r');
        /*$qb->andWhere(
            $qb->expr()->orX(
                $qb->andWhere(
                    $qb->expr()->eq("$alias.sender", $user->getId()),
                    $qb->expr()->eq("$alias.msgType", Message::MESSAGE_TYPE_OUTBOX)
                ),
                $qb->andWhere(
                    $qb->expr()->in("r", $user->getId()),
                    $qb->expr()->eq("$alias.msgType", Message::MESSAGE_TYPE_INBOX)
                )
            ),
        );*/
    }

    /*public function generateBetweenRange($qb, $alias, $field, $range)
    {
        $value = $range['between'];
        $rangeValue = explode('..', $value);
        $valueParameter = $field.'1';
        $qb
            ->andWhere(sprintf('%1$s.%2$s BETWEEN :%3$s_1 AND :%3$s_2', $alias, $field, $valueParameter))
            ->setParameter(sprintf('%s_1', $valueParameter), $rangeValue[0])
            ->setParameter(sprintf('%s_2', $valueParameter), $rangeValue[1]);

        return $qb;
    }*/
}
