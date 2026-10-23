<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\DataProvider\Extension\CourseLinkExtensionTrait;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Limits a course resource collection to the course of the request.
 *
 * The course comes from the request context resolved by CidReqListener (the same one the
 * ROLE_CURRENT_COURSE_* roles describe), not from the "cid" query parameter, which this
 * filter only documents: the context can also come from another parameter (e.g. cidReq),
 * and a filter that only ran when "cid" is present would then return every course.
 */
class CidFilter extends AbstractFilter
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
            'cid' => [
                'property' => null,
                'type' => 'string',
                'required' => $this->isRequired(),
                'description' => 'Course identifier',
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
        $courseId = (int) ($this->cidReqHelper->getCourseId() ?? 0);

        if ($courseId <= 0) {
            if ($this->isRequired()) {
                $queryBuilder->andWhere('1 = 0');
            }

            return;
        }

        $this->addCourseLinkWithVisibilityConditions($queryBuilder, false, $this->linkOwner);

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq('resource_links.course', ':course')
            )
            ->setParameter('course', $courseId)
        ;
    }

    public function isRequired(): bool
    {
        return true;
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
        // The course does not come from the query string, see apply().
    }
}
