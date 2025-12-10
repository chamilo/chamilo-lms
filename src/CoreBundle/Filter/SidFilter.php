<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\DataProvider\Extension\CourseLinkExtensionTrait;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class SidFilter extends AbstractFilter
{
    use CourseLinkExtensionTrait;

    public function __construct(
        protected RequestStack $requestStack,
        protected EntityManagerInterface $entityManager,
        protected Security $security,
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

        $session = $this->entityManager->find(Session::class, $value);

        if ($value && !$session) {
            throw new NotFoundHttpException('Session not found');
        }

        $reflection = new ReflectionClass($resourceClass);
        $loadBaseSessionContent = \in_array(
            ResourceShowCourseResourcesInSessionInterface::class,
            $reflection->getInterfaceNames(),
            true
        );

        $this->addCourseLinkWithVisibilityConditions($queryBuilder, false);

        if (null === $session) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'resource_links.session IS NULL',
                    'resource_links.session = 0'
                )
            );
        } elseif ($loadBaseSessionContent) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'resource_links.session = :session',
                    'resource_links.session IS NULL'
                )
            )->setParameter('session', $session->getId());
        } else {
            $queryBuilder
                ->andWhere('resource_links.session = :session')
                ->setParameter('session', $session->getId())
            ;
        }
    }
}
