<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Room;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\ORM\QueryBuilder;

final readonly class RoomExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (Room::class !== $resourceClass) {
            return;
        }

        $accessUrlId = $this->accessUrlHelper->getCurrent()?->getId();
        $rootAlias = $queryBuilder->getRootAliases()[0];
        $branchAlias = $queryNameGenerator->generateJoinAlias('branch');

        if (null === $accessUrlId) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $queryBuilder
            ->innerJoin("$rootAlias.branch", $branchAlias)
            ->andWhere("IDENTITY($branchAlias.url) = :current_access_url_id")
            ->setParameter('current_access_url_id', $accessUrlId)
        ;
    }
}
