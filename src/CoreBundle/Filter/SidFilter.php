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
        LoggerInterface $logger = null,
        array $properties = null,
        NameConverterInterface $nameConverter = null
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
                'description' => 'Course identifier',
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
        Operation $operation = null,
        array $context = []
    ): void {
        if ('sid' !== $property) {
            return;
        }

        $reflection = new ReflectionClass($resourceClass);

        $loadBaseSessionContent = \in_array(
            ResourceShowCourseResourcesInSessionInterface::class,
            $reflection->getInterfaceNames(),
            true
        );

        // Session was set with a kernel request from CoreBundle\EventListener\CourseListener class
        $session = $this->getSession();

        if (null === $session) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->isNull('resource_links.session'),
                    $queryBuilder->expr()->eq('resource_links.session', 0)
                )
            );
        } elseif ($loadBaseSessionContent) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->eq('resource_links.session', ':session'),
                        $queryBuilder->expr()->isNull('resource_links.session')
                    )
                )
                ->setParameter('session', $session?->getId())
            ;
        } else {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->eq('resource_links.session', ':session')
                )
                ->setParameter('session', $session?->getId())
            ;
        }
    }
}
