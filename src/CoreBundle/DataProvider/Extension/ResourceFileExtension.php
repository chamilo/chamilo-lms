<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Restricts the `/api/resource_files` collection to rows the requester may VIEW.
 *
 * Without this extension the GetCollection — gated only by ROLE_USER — would
 * return every row in `resource_file` to any authenticated user, leaking
 * uploader-supplied `originalName` values (frequently sensitive, e.g.
 * "firstname.lastname.cv.pdf") across the entire platform.
 *
 * Visibility rules walk through the `resourceNode` relation and mirror those
 * of {@see ResourceNodeExtension}:
 *  - administrators see every file;
 *  - authenticated users see a file when ANY of the following holds about
 *    the parent ResourceNode:
 *      * they created it,
 *      * it has at least one ResourceLink targeting them as user,
 *      * it has at least one ResourceLink whose linked course they belong to
 *        (teacher or student via CourseRelUser),
 *      * it has at least one published ResourceLink whose linked course is
 *        OPEN_WORLD (public to any authenticated platform user).
 */
final class ResourceFileExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private readonly Security $security
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (ResourceFile::class !== $resourceClass) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            // GetCollection requires ROLE_USER; reaching this branch means an
            // unexpected token shape. Deny by returning an impossible predicate.
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->leftJoin("$rootAlias.resourceNode", 'rf_acl_node')
            ->leftJoin('rf_acl_node.resourceLinks', 'rf_acl_link')
            ->leftJoin('rf_acl_link.course', 'rf_acl_course')
            ->leftJoin('rf_acl_course.users', 'rf_acl_course_user')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    'rf_acl_node.creator = :rf_acl_user',
                    'rf_acl_link.user = :rf_acl_user',
                    'rf_acl_course_user.user = :rf_acl_user',
                    $queryBuilder->expr()->andX(
                        'rf_acl_course.visibility = :rf_acl_open_world',
                        'rf_acl_link.visibility = :rf_acl_published'
                    )
                )
            )
            ->setParameter('rf_acl_user', $user->getId())
            ->setParameter('rf_acl_open_world', Course::OPEN_WORLD)
            ->setParameter('rf_acl_published', ResourceLink::VISIBILITY_PUBLISHED)
            ->distinct()
        ;
    }
}
