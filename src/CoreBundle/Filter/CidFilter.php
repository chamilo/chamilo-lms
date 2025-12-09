<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class CidFilter extends AbstractFilter
{
    public function __construct(
        protected RequestStack $requestStack,
        protected EntityManagerInterface $entityManager,
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'cid' => [
                'property' => null,
                'type' => 'int',
                'required' => false,
                'description' => 'Course identifier',
            ],
        ];
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
        if ('cid' !== $property) {
            return;
        }

        $course = $this->entityManager->find(Course::class, $value);

        if (!$course) {
            throw new NotFoundHttpException('Course not found');
        }

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq('resource_links.course', ':course')
            )
            ->setParameter('course', $course->getId())
        ;
    }
}
