<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\DataProvider\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Keeps the session course subscriptions collection to what the caller may see:
 * their own subscriptions, plus the ones of the courses they teach — as a coach
 * of that session course, as a general coach of the session, or as a teacher of
 * the base course.
 */
final readonly class SessionRelCourseRelUserExtension implements QueryCollectionExtensionInterface
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
        if (SessionRelCourseRelUser::class !== $resourceClass
            || $this->security->isGranted('ROLE_ADMIN')
        ) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (null === $user) {
            throw new AccessDeniedException('Access Denied.');
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->orX(
                    \sprintf('%s.user = :current_user', $alias),
                    \sprintf(
                        'EXISTS (
                            SELECT 1 FROM %s course_coach
                            WHERE course_coach.user = :current_user
                                AND course_coach.session = %s.session
                                AND course_coach.course = %s.course
                                AND course_coach.status = :course_coach_status
                        )',
                        SessionRelCourseRelUser::class,
                        $alias,
                        $alias
                    ),
                    \sprintf(
                        'EXISTS (
                            SELECT 1 FROM %s general_coach
                            WHERE general_coach.user = :current_user
                                AND general_coach.session = %s.session
                                AND general_coach.relationType = :general_coach_status
                        )',
                        SessionRelUser::class,
                        $alias
                    ),
                    // A teacher of the base course keeps managing it inside a session
                    // even when nobody registered them as a coach of that session.
                    \sprintf(
                        'EXISTS (
                            SELECT 1 FROM %s course_teacher
                            WHERE course_teacher.user = :current_user
                                AND course_teacher.course = %s.course
                                AND course_teacher.status = :course_teacher_status
                        )',
                        CourseRelUser::class,
                        $alias
                    )
                )
            )
            ->setParameter('current_user', (int) $user->getId())
            ->setParameter('course_coach_status', Session::COURSE_COACH)
            ->setParameter('general_coach_status', Session::GENERAL_COACH)
            ->setParameter('course_teacher_status', CourseRelUser::TEACHER)
        ;
    }
}
