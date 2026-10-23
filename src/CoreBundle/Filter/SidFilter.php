<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\DataProvider\Extension\CourseLinkExtensionTrait;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Limits a course resource collection to the session of the request: the session's own
 * resources, plus the base course ones for resources shown in sessions.
 *
 * Like CidFilter, it reads the context resolved by CidReqListener, not the "sid" query
 * parameter, which it only documents. Without a course context it does nothing: CidFilter
 * decides that case.
 */
class SidFilter extends AbstractFilter
{
    use CourseLinkExtensionTrait;

    public function __construct(
        protected RequestStack $requestStack,
        protected EntityManagerInterface $entityManager,
        protected Security $security,
        private readonly CidReqHelper $cidReqHelper,
        ManagerRegistry $managerRegistry,
        ?LoggerInterface $logger = null,
        ?array $properties = null,
        ?NameConverterInterface $nameConverter = null,
        private readonly ?string $linkOwner = null,
    ) {
        parent::__construct($managerRegistry, $logger, $properties, $nameConverter);
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            'sid' => [
                'property' => null,
                'type' => 'int',
                'required' => false,
                'description' => 'Session identifier',
            ],
        ];
    }

    public function apply(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if ((int) ($this->cidReqHelper->getCourseId() ?? 0) <= 0) {
            return;
        }

        $sessionId = (int) ($this->cidReqHelper->getSessionId() ?? 0);

        $this->addCourseLinkWithVisibilityConditions($queryBuilder, false, $this->linkOwner);

        if ($sessionId <= 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'resource_links.session IS NULL',
                    'resource_links.session = 0'
                )
            );

            return;
        }

        if (is_subclass_of($resourceClass, ResourceShowCourseResourcesInSessionInterface::class)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    'resource_links.session = :session',
                    'resource_links.session IS NULL'
                )
            )->setParameter('session', $sessionId);

            return;
        }

        $queryBuilder
            ->andWhere('resource_links.session = :session')
            ->setParameter('session', $sessionId)
        ;
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
        // The session does not come from the query string, see apply().
    }
}
