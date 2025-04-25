<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Traits\CourseFromRequestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SidFilter extends AbstractFilter
{
    use CourseFromRequestTrait;

    public function __construct(
        protected RequestStack $requestStack,
        protected EntityManagerInterface $entityManager,
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'sid' => [
                'property' => null,
                'type' => 'string',
                'required' => false,
                'description' => 'Session identifier',
            ],
        ];
    }

    /**
     * @param mixed $value
     *
     * @throws ReflectionException
     */
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ('sid' !== $property) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $session = $this->getSession();

        $reflection = new ReflectionClass($resourceClass);
        $loadBaseSessionContent = \in_array(
            ResourceShowCourseResourcesInSessionInterface::class,
            $reflection->getInterfaceNames(),
            true
        );

        $joins = $queryBuilder->getDQLPart('join');

        if (empty($joins[$alias]) || !array_filter($joins[$alias], fn ($j) => 'resourceNode' === $j->getAlias())) {
            $queryBuilder->leftJoin($alias.'.resourceNode', 'resourceNode');
        }

        if (empty($joins['resourceNode']) || !array_filter($joins['resourceNode'], fn ($j) => 'resourceLink' === $j->getAlias())) {
            $queryBuilder->leftJoin('resourceNode.resourceLinks', 'resourceLink');
        }

        if (null === $session) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'resourceLink.session IS NULL',
                    'resourceLink.session = 0'
                )
            );
        } elseif ($loadBaseSessionContent) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'resourceLink.session = :session',
                    'resourceLink.session IS NULL'
                )
            )->setParameter('session', $session->getId());
        } else {
            $queryBuilder
                ->andWhere('resourceLink.session = :session')
                ->setParameter('session', $session->getId())
            ;
        }
    }
}
