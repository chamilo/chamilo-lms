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
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class TrackEAttemptExtension implements QueryCollectionExtensionInterface
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
        if (TrackEAttempt::class !== $resourceClass) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        \assert($user instanceof User);

        $alias = $queryBuilder->getRootAliases()[0];

        $teacherExistsDql = \sprintf(
            'SELECT 1 FROM %s cru WHERE cru.course = tee.course AND cru.user = :current_user AND cru.status = :teacher_status',
            CourseRelUser::class
        );

        $coachExistsDql = \sprintf(
            'SELECT 1 FROM %s srcru WHERE srcru.course = tee.course AND srcru.session = tee.session AND srcru.user = :current_user AND srcru.status = :coach_status',
            SessionRelCourseRelUser::class
        );

        $queryBuilder
            ->innerJoin("$alias.trackExercise", 'tee')
            ->andWhere(
                $queryBuilder->expr()->orX(
                    'tee.user = :current_user',
                    $queryBuilder->expr()->exists($teacherExistsDql),
                    $queryBuilder->expr()->exists($coachExistsDql),
                )
            )
            ->setParameter('current_user', $user->getId(), Types::INTEGER)
            ->setParameter('teacher_status', CourseRelUser::TEACHER, Types::INTEGER)
            ->setParameter('coach_status', Session::COURSE_COACH, Types::INTEGER)
        ;
    }
}
