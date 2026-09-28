<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Entity\CBlogAttachment;
use Chamilo\CourseBundle\Entity\CBlogComment;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Entity\CBlogRating;
use Chamilo\CourseBundle\Entity\CBlogRelUser;
use Chamilo\CourseBundle\Entity\CBlogTask;
use Chamilo\CourseBundle\Entity\CBlogTaskRelUser;
use Doctrine\ORM\QueryBuilder;

/**
 * Keeps Blog resources strictly inside the active course/session context.
 *
 * Blogs are session-only resources: a session must not inherit base-course
 * blogs, and resources from one session must not leak into another one.
 */
final readonly class CBlogContextExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private const array BLOG_RESOURCE_CLASSES = [
        CBlog::class,
        CBlogAttachment::class,
        CBlogComment::class,
        CBlogPost::class,
        CBlogRating::class,
        CBlogRelUser::class,
        CBlogTask::class,
        CBlogTaskRelUser::class,
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
        $this->applyContextRestriction($queryBuilder, $queryNameGenerator, $resourceClass, $operation);
    }

    /**
     * @param array<string, mixed> $identifiers
     * @param array<string, mixed> $context
     */
    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->applyContextRestriction($queryBuilder, $queryNameGenerator, $resourceClass, $operation);
    }

    private function applyContextRestriction(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation
    ): void {
        $effectiveClass = $operation?->getClass() ?? $resourceClass;
        if (!\in_array($effectiveClass, self::BLOG_RESOURCE_CLASSES, true)) {
            return;
        }

        $courseId = (int) ($this->cidReqHelper->getCourseId() ?? 0);
        if ($courseId <= 0) {
            return;
        }

        $sessionId = (int) ($this->cidReqHelper->getSessionId() ?? 0);
        $rootAliases = $queryBuilder->getRootAliases();
        if ([] === $rootAliases) {
            return;
        }

        $rootAlias = $rootAliases[0];
        $blogAlias = $rootAlias;

        if (CBlog::class !== $effectiveClass) {
            $blogAlias = $queryNameGenerator->generateJoinAlias('blog');
            $queryBuilder->innerJoin($rootAlias.'.blog', $blogAlias);
        }

        $resourceNodeAlias = $queryNameGenerator->generateJoinAlias('resourceNode');
        $resourceLinkAlias = $queryNameGenerator->generateJoinAlias('resourceLinks');

        $queryBuilder
            ->innerJoin($blogAlias.'.resourceNode', $resourceNodeAlias)
            ->innerJoin($resourceNodeAlias.'.resourceLinks', $resourceLinkAlias)
            ->andWhere('IDENTITY('.$resourceLinkAlias.'.course) = :blogContextCourseId')
            ->setParameter('blogContextCourseId', $courseId)
            ->distinct()
        ;

        if ($sessionId > 0) {
            $queryBuilder
                ->andWhere('IDENTITY('.$resourceLinkAlias.'.session) = :blogContextSessionId')
                ->setParameter('blogContextSessionId', $sessionId)
            ;

            return;
        }

        $queryBuilder->andWhere($resourceLinkAlias.'.session IS NULL');
    }
}
