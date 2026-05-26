<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\UsergroupVoter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class MessageByGroupQueryExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private UsergroupRepository $usergroupRepository,
        private Security $security,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (Message::class !== $resourceClass) {
            return;
        }

        if (null === $operation || 'get_messages_by_social_group' !== $operation->getName()) {
            return;
        }

        $usergroupId = (int) ($context['uri_variables']['usergroupId'] ?? 0);
        $usergroup = $this->usergroupRepository->find($usergroupId);

        if (null === $usergroup) {
            throw new NotFoundHttpException('Usergroup not found');
        }

        if (!$this->security->isGranted(UsergroupVoter::VIEW, $usergroup)) {
            throw new AccessDeniedHttpException();
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $msgTypeParam = $queryNameGenerator->generateParameterName('msgType');
        $zeroParentParam = $queryNameGenerator->generateParameterName('zeroParent');

        $queryBuilder
            ->andWhere(\sprintf('%s.msgType = :%s', $alias, $msgTypeParam))
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->isNull($alias.'.parent'),
                $queryBuilder->expr()->eq($alias.'.parent', ':'.$zeroParentParam),
            ))
            ->setParameter($msgTypeParam, Message::MESSAGE_TYPE_GROUP)
            ->setParameter($zeroParentParam, 0)
        ;
    }
}
