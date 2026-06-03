<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CBlogAttachment;
use Chamilo\CourseBundle\Entity\CBlogComment;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Entity\CBlogTask;
use Chamilo\CourseBundle\Entity\CBlogTaskRelUser;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Scopes blog sub-resource collections to the course resolved from the session
 * (CidReqHelper) — the same course that gated the operation's contextual role.
 *
 * These entities do not extend AbstractResource, so their course link lives on
 * the related CBlog (project). Without this extension the collections declare no
 * course filter, so any authenticated user enrolled in one course could list
 * another course's blog data straight from the collection endpoint (IDOR).
 *
 * The extension joins each resource to its CBlog, then to the ResourceNode and
 * ResourceLink, and restricts to the session course. The item (Get) operations
 * are unaffected: they keep their own per-object VIEW security.
 */
final class CBlogCourseLinkExtension implements QueryCollectionExtensionInterface
{
    use CourseLinkExtensionTrait;

    /**
     * resourceClass => [intermediate relation from the root or null, blog relation].
     *
     * CBlogComment may have a null blog but always belongs to a post, so it is
     * scoped through post.blog.
     */
    private const BLOG_PATHS = [
        CBlogPost::class => [null, 'blog'],
        CBlogTask::class => [null, 'blog'],
        CBlogTaskRelUser::class => [null, 'blog'],
        CBlogAttachment::class => [null, 'blog'],
        CBlogComment::class => ['post', 'blog'],
    ];

    public function __construct(
        private readonly Security $security,
        private readonly CidReqHelper $cidReqHelper,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (!isset(self::BLOG_PATHS[$resourceClass])) {
            return;
        }

        if (null === $this->security->getUser()) {
            throw new AccessDeniedException();
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        [$intermediate, $blogRelation] = self::BLOG_PATHS[$resourceClass];

        $blogFromAlias = $rootAlias;
        if (null !== $intermediate) {
            $queryBuilder->innerJoin("$rootAlias.$intermediate", 'blog_cl_inter');
            $blogFromAlias = 'blog_cl_inter';
        }

        $queryBuilder
            ->innerJoin("$blogFromAlias.$blogRelation", 'blog_cl')
            ->innerJoin('blog_cl.resourceNode', 'blog_cl_node')
            ->innerJoin('blog_cl_node.resourceLinks', 'blog_cl_links')
            ->distinct()
        ;

        $courseId = (int) $this->cidReqHelper->getCourseId();
        if ($courseId <= 0) {
            // No resolved course context: fail closed rather than leak every course.
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $queryBuilder
            ->andWhere('blog_cl_links.course = :blogClCourseId')
            ->setParameter('blogClCourseId', $courseId, Types::INTEGER)
        ;

        // Students never see draft (unpublished) blogs; teachers/admins do.
        if (!$this->canViewDraftResources()) {
            $queryBuilder
                ->andWhere('blog_cl_links.visibility != :blogClDraft')
                ->setParameter('blogClDraft', ResourceLink::VISIBILITY_DRAFT, Types::INTEGER)
            ;
        }
    }
}
