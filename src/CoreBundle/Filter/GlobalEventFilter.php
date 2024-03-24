<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class GlobalEventFilter extends AbstractFilter
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    protected function filterProperty(
        string $property,
               $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $isGlobalType = isset($context['filters']['type']) && $context['filters']['type'] === 'global';

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $resourceNodeAlias = $queryNameGenerator->generateJoinAlias('resourceNode');
        $resourceLinkAlias = $queryNameGenerator->generateJoinAlias('resourceLink');
        if ($isGlobalType) {
            $queryBuilder
                ->innerJoin("$rootAlias.resourceNode", $resourceNodeAlias)
                ->innerJoin("$resourceNodeAlias.resourceLinks", $resourceLinkAlias)
                ->andWhere("$resourceLinkAlias.course IS NULL")
                ->andWhere("$resourceLinkAlias.session IS NULL")
                ->andWhere("$resourceLinkAlias.group IS NULL")
                ->andWhere("$resourceLinkAlias.user IS NULL");

            return;
        }

        if (!$isGlobalType) {
            $subQueryBuilder = $queryBuilder->getEntityManager()->createQueryBuilder();
            $subRN = $queryNameGenerator->generateJoinAlias('subResourceNode');
            $subRL = $queryNameGenerator->generateJoinAlias('subResourceLink');
            $subQueryBuilder->select('1')
                ->from(ResourceNode::class, $subRN)
                ->innerJoin("$subRN.resourceLinks", $subRL)
                ->where("$subRL.course IS NULL")
                ->andWhere("$subRL.session IS NULL")
                ->andWhere("$subRL.group IS NULL")
                ->andWhere("$subRL.user IS NULL")
                ->andWhere("$subRN.id = $rootAlias.resourceNode");

            $queryBuilder->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($subQueryBuilder->getDQL())));
        }
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'type' => [
                'property' => 'type',
                'type' => 'string',
                'required' => false,
                'description' => 'Filter events by type. Use "global" to get global events.',
            ],
        ];
    }
}
