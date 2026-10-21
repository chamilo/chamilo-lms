<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Lists only the gradebook links the current user can read: the ones of the courses they teach
 * and of the courses, or sessions, they belong to. Keep in sync with GradebookLinkVoter.
 */
final readonly class GradebookLinkExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        if (GradebookLink::class !== $resourceClass || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedException('Access Denied.');
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->innerJoin("$alias.category", 'glx_category')
            ->andWhere(\sprintf(
                '(EXISTS (SELECT 1 FROM %1$s glx_teacher WHERE glx_teacher.course = %2$s.course AND glx_teacher.user = :glx_user AND glx_teacher.status = :glx_teacher_status)
                OR (glx_category.session IS NULL AND EXISTS (SELECT 1 FROM %1$s glx_member WHERE glx_member.course = %2$s.course AND glx_member.user = :glx_user))
                OR (glx_category.session IS NOT NULL AND (
                    EXISTS (SELECT 1 FROM %3$s glx_session_member WHERE glx_session_member.session = glx_category.session AND glx_session_member.course = %2$s.course AND glx_session_member.user = :glx_user)
                    OR EXISTS (SELECT 1 FROM %4$s glx_coach WHERE glx_coach.session = glx_category.session AND glx_coach.user = :glx_user AND glx_coach.relationType = :glx_coach_type)
                )))',
                CourseRelUser::class,
                $alias,
                SessionRelCourseRelUser::class,
                SessionRelUser::class,
            ))
            ->setParameter('glx_user', (int) $user->getId())
            ->setParameter('glx_teacher_status', CourseRelUser::TEACHER)
            ->setParameter('glx_coach_type', Session::GENERAL_COACH)
        ;
    }
}
