<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CForumAttachment;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Doctrine\ORM\QueryBuilder;

/**
 * Limits course resource collections to the course (and session) of the request.
 *
 * The contextual course roles that gate these collections describe the course of the
 * request, not the course each row belongs to, so the rows must be filtered by the
 * course links of the resource itself, or of the resource it belongs to.
 */
final readonly class CourseResourceLinkExtension implements QueryCollectionExtensionInterface
{
    /**
     * resourceClass => relation from the root to the resource whose links set the course,
     * or null when the root resource has its own links.
     */
    private const array LINK_OWNERS = [
        CForumPost::class => null,
        CForumAttachment::class => 'post',
        CStudentPublicationComment::class => 'publication',
        CGroup::class => null,
        CLinkCategory::class => null,
        CAttendanceCalendar::class => 'attendance',
    ];

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
        if (!\array_key_exists($resourceClass, self::LINK_OWNERS)) {
            return;
        }

        $courseId = (int) ($this->cidReqHelper->getCourseId() ?? 0);

        if ($courseId <= 0) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $ownerAlias = $queryBuilder->getRootAliases()[0];
        $relation = self::LINK_OWNERS[$resourceClass];

        if (null !== $relation) {
            $relationAlias = $queryNameGenerator->generateJoinAlias($relation);
            $queryBuilder->innerJoin($ownerAlias.'.'.$relation, $relationAlias);
            $ownerAlias = $relationAlias;
        }

        $resourceNodeAlias = $queryNameGenerator->generateJoinAlias('resourceNode');
        $resourceLinkAlias = $queryNameGenerator->generateJoinAlias('resourceLinks');

        $queryBuilder
            ->innerJoin($ownerAlias.'.resourceNode', $resourceNodeAlias)
            ->innerJoin($resourceNodeAlias.'.resourceLinks', $resourceLinkAlias)
            ->andWhere('IDENTITY('.$resourceLinkAlias.'.course) = :courseResourceCourseId')
            ->setParameter('courseResourceCourseId', $courseId)
            ->distinct()
        ;

        $sessionId = (int) ($this->cidReqHelper->getSessionId() ?? 0);

        // Inside a session: its own resources plus those of the base course.
        if ($sessionId > 0) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        'IDENTITY('.$resourceLinkAlias.'.session) = :courseResourceSessionId',
                        $resourceLinkAlias.'.session IS NULL'
                    )
                )
                ->setParameter('courseResourceSessionId', $sessionId)
            ;

            return;
        }

        $queryBuilder->andWhere($resourceLinkAlias.'.session IS NULL');
    }
}
