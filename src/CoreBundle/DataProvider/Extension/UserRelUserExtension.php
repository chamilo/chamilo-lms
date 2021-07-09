<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
//use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Chamilo\CoreBundle\Entity\Message;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

final class UserRelUserExtension implements QueryCollectionExtensionInterface //, QueryItemExtensionInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null): void
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, string $operationName = null, array $context = []): void
    {
        //error_log('applyToItem');
        //$this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (Message::class !== $resourceClass) {
            return;
        }

        /*if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }*/

        /*$user = $this->security->getUser();
        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder->andWhere("
            ($alias.userSender = :current AND $alias.msgType = :outbox) OR
            ($alias.userReceiver = :current AND $alias.msgType = :inbox) OR
            ($alias.userReceiver = :current AND $alias.msgType = :invitation) OR
            ($alias.userReceiver = :current AND $alias.msgType = :promoted) OR
            ($alias.userReceiver = :current AND $alias.msgType = :wallPost) OR
            ($alias.userReceiver = :current AND $alias.msgType = :conversation)
        ");
        $queryBuilder->setParameters([
            'current' => $user,
            'inbox' => Message::MESSAGE_TYPE_INBOX,
            'outbox' => Message::MESSAGE_TYPE_OUTBOX,
            'invitation' => Message::MESSAGE_TYPE_INVITATION,
            'promoted' => Message::MESSAGE_TYPE_PROMOTED,
            'wallPost' => Message::MESSAGE_TYPE_WALL,
            'conversation' => Message::MESSAGE_STATUS_CONVERSATION,
        ]);*/
    }
}
