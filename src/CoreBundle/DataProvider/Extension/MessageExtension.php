<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;

final class MessageExtension implements QueryCollectionExtensionInterface //, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
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

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        string $operationName = null,
        array $context = []
    ): void {
        //error_log('applyToItem1');
        //$this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $qb, string $resourceClass): void
    {
        if (Message::class !== $resourceClass) {
            return;
        }

        /*if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }*/

        /** @var User $user */
        $user = $this->security->getUser();
        $alias = $qb->getRootAliases()[0];

        //$qb->leftJoin("$alias.receivers", 'r');

        $qb->leftJoin("$alias.receivers", 'r', Join::WITH, "r.receiver = :current OR $alias.sender = :current ");
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

        $qb->andWhere(
            "
            ($alias.sender = :current AND $alias.status <> :deleted) OR 
                ($alias.sender <> :current AND r.receiver = :current AND (
                    ($alias.msgType = :inbox) OR
                    ($alias.msgType = :invitation) OR
                    ($alias.msgType = :conversation)
                ) 
            )
        "
        );

        $qb->setParameters([
            'current' => $user,
            'deleted' => Message::MESSAGE_STATUS_DELETED,
            //'currentList' => [$user->getId()],
            'inbox' => Message::MESSAGE_TYPE_INBOX,
            //'outbox' => Message::MESSAGE_TYPE_OUTBOX,
            'invitation' => Message::MESSAGE_TYPE_INVITATION,
            'conversation' => Message::MESSAGE_TYPE_CONVERSATION,
        ]);
    }
}
