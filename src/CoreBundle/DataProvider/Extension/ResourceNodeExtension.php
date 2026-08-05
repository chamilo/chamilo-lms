<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Restricts the `/api/resource_nodes` collection to rows the requester may VIEW.
 *
 * Without this extension the GetCollection — gated only by ROLE_USER — would
 * return every row in `resource_node` to any authenticated user, allowing
 * platform-wide enumeration of documents, attachments, illustrations and
 * personal-file nodes across courses and users.
 *
 * Visibility rules applied here:
 *  - administrators see every node;
 *  - authenticated users see a node when ANY of the following holds:
 *      * they created it,
 *      * it has at least one ResourceLink targeting them as user
 *        (personal links: shares, personal files…),
 *      * it has at least one ResourceLink whose linked course they belong to
 *        (teacher or student via CourseRelUser),
 *      * it has at least one published ResourceLink whose linked course is
 *        OPEN_WORLD (public to any authenticated platform user).
 */
final class ResourceNodeExtension implements QueryCollectionExtensionInterface
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
        if (ResourceNode::class !== $resourceClass) {
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
            ->leftJoin("$rootAlias.resourceLinks", 'rn_acl_link')
            ->leftJoin('rn_acl_link.course', 'rn_acl_course')
            ->leftJoin('rn_acl_course.users', 'rn_acl_course_user')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    "$rootAlias.creator = :rn_acl_user",
                    'rn_acl_link.user = :rn_acl_user',
                    'rn_acl_course_user.user = :rn_acl_user',
                    $queryBuilder->expr()->andX(
                        'rn_acl_course.visibility = :rn_acl_open_world',
                        'rn_acl_link.visibility = :rn_acl_published'
                    )
                )
            )
            ->setParameter('rn_acl_user', $user->getId())
            ->setParameter('rn_acl_open_world', Course::OPEN_WORLD)
            ->setParameter('rn_acl_published', ResourceLink::VISIBILITY_PUBLISHED)
            ->distinct()
        ;
    }
}
