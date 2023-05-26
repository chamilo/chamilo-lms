<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;

final class SessionRelUserExtension implements QueryCollectionExtensionInterface //, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    /*public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = []): void
    {
        error_log('applyToItem');
        $this->addWhere($queryBuilder, $resourceClass);
    }*/

    private function addWhere(QueryBuilder $qb, string $resourceClass): void
    {
        if (SessionRelUser::class !== $resourceClass) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $alias = $qb->getRootAliases()[0];
        $content = $request->getContent();

        $now = new DateTime('now', new DateTimeZone('UTC'));
        $date = $now->format('Y-m-d H:i:s');

        $qb->innerJoin("$alias.session", 's');

        if (str_contains($content, 'getCurrentSessions')) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('s.accessEndDate'),
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('s.accessStartDate'),
                        $qb->expr()->isNotNull('s.accessEndDate'),
                        $qb->expr()->lte('s.accessStartDate', "'$date'"),
                        $qb->expr()->gte('s.accessEndDate', "'$date'")
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('s.accessStartDate'),
                        $qb->expr()->isNotNull('s.accessEndDate'),
                        $qb->expr()->gte('s.accessEndDate', "'$date'")
                    )
                )
            );
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (null === $user = $this->security->getUser()) {
            throw new AccessDeniedException('Access Denied SessionRelUser');
        }

        $qb->andWhere(sprintf('%s.user = :current_user', $alias));
        $qb->setParameter('current_user', $user);
    }
}
