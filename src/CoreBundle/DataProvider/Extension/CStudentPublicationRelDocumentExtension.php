<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelDocument;
use Doctrine\ORM\QueryBuilder;

/**
 * Limits assignment-document relations to the assignments of the current course/session.
 */
final readonly class CStudentPublicationRelDocumentExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (CStudentPublicationRelDocument::class !== $resourceClass) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $courseId = (int) ($this->cidReqHelper->getCourseId() ?? 0);

        if ($courseId <= 0) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $publicationAlias = $queryNameGenerator->generateJoinAlias('publication');
        $resourceNodeAlias = $queryNameGenerator->generateJoinAlias('resourceNode');
        $resourceLinkAlias = $queryNameGenerator->generateJoinAlias('resourceLinks');

        $queryBuilder
            ->innerJoin($rootAlias.'.publication', $publicationAlias)
            ->innerJoin($publicationAlias.'.resourceNode', $resourceNodeAlias)
            ->innerJoin($resourceNodeAlias.'.resourceLinks', $resourceLinkAlias)
            ->andWhere('IDENTITY('.$resourceLinkAlias.'.course) = :relDocumentCourseId')
            ->setParameter('relDocumentCourseId', $courseId)
            ->distinct()
        ;

        $sessionId = (int) ($this->cidReqHelper->getSessionId() ?? 0);

        // Same scope as the assignment list: a session only shows its own assignments.
        if ($sessionId > 0) {
            $queryBuilder
                ->andWhere('IDENTITY('.$resourceLinkAlias.'.session) = :relDocumentSessionId')
                ->setParameter('relDocumentSessionId', $sessionId)
            ;

            return;
        }

        $queryBuilder->andWhere($resourceLinkAlias.'.session IS NULL');
    }
}
