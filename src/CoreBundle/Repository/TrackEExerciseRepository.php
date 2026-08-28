<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Security\ExerciseAttemptScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TrackEExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrackEExercise::class);
    }

    public function delete(TrackEExercise $track): void
    {
        $this->getEntityManager()->remove($track);
        $this->getEntityManager()->flush();
    }

    /**
     * Get exercises with pending corrections grouped by exercise ID.
     */
    public function getPendingCorrectionsByExercise(int $courseId, ?int $sessionId): array
    {
        $qb = $this->createQueryBuilder('te');

        $qb->select('IDENTITY(te.quiz) AS exerciseId, COUNT(te.exeId) AS pendingCount')
            ->where('te.status = :status')
            ->andWhere('te.course = :courseId')
        ;
        if (!empty($sessionId)) {
            $qb->andWhere('te.session = :sessionId')
                ->setParameter('sessionId', $sessionId)
            ;
        } else {
            $qb->andWhere('te.session IS NULL');
        }
        $qb->setParameter('status', 'incomplete')
            ->setParameter('courseId', $courseId)
            ->groupBy('te.quiz')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Restricts a collection to the attempts $scope may read, i.e. the predicate of
     * TrackEExerciseVoter::VIEW written as a WHERE.
     *
     * $trackExerciseAlias must be an already joined TrackEExercise. What the voter adds on top
     * of the teaching term — IsAllowedToEditHelper::check() — is not expressible here, so a
     * collection stays slightly more permissive than the item for a teacher in the student
     * view. The item operation is the one a teacher actually opens, and it fails closed.
     */
    public function addViewCriteria(QueryBuilder $queryBuilder, string $trackExerciseAlias, ExerciseAttemptScope $scope): void
    {
        $expr = $queryBuilder->expr();

        $terms = [
            \sprintf('%s.user = :access_current_user', $trackExerciseAlias),
            ...$this->getTeachingTerms($expr, $trackExerciseAlias),
        ];

        if ($scope->mayFollowAsStudentBoss) {
            $terms[] = $expr->exists($this->getFollowerDql($trackExerciseAlias, 'access_boss', 'access_boss_relation'));
            $queryBuilder->setParameter('access_boss_relation', UserRelUser::USER_RELATION_TYPE_BOSS);
        }

        if ($scope->mayFollowAsDrh) {
            $terms[] = $expr->exists($this->getFollowerDql($trackExerciseAlias, 'access_drh', 'access_drh_relation'));
            $queryBuilder->setParameter('access_drh_relation', UserRelUser::USER_RELATION_TYPE_RRHH);
        }

        if ($scope->mayAdministerSessions) {
            $terms[] = $expr->exists(\sprintf(
                'SELECT 1 FROM %s access_session_admin
                 WHERE access_session_admin.session = %s.session
                   AND access_session_admin.user = :access_current_user
                   AND access_session_admin.relationType = :access_session_admin_relation',
                SessionRelUser::class,
                $trackExerciseAlias
            ));
            $queryBuilder->setParameter('access_session_admin_relation', Session::SESSION_ADMIN);
        }

        $queryBuilder->andWhere($expr->orX(...$terms));

        $this->bindTeachingParameters($queryBuilder, $scope->userId);
    }

    /**
     * Restricts to the attempts of a course the user teaches or tutors, or of a session they
     * coach. Unlike addViewCriteria() this term depends on no role, only on subscriptions.
     */
    public function addTeachingCriteria(QueryBuilder $queryBuilder, string $trackExerciseAlias, int $userId): void
    {
        $expr = $queryBuilder->expr();

        $queryBuilder->andWhere($expr->orX(...$this->getTeachingTerms($expr, $trackExerciseAlias)));

        $this->bindTeachingParameters($queryBuilder, $userId);
    }

    /**
     * addTeachingCriteria() over a single attempt. TrackEExerciseVoter runs this to decide
     * whether the request's `cid` may vouch for the attempt at hand.
     */
    public function isTaughtBy(int $trackExerciseId, int $userId): bool
    {
        $queryBuilder = $this->createQueryBuilder('tee')
            ->select('COUNT(tee.exeId)')
            ->andWhere('tee.exeId = :access_track_exercise')
            ->setParameter('access_track_exercise', $trackExerciseId)
        ;

        $this->addTeachingCriteria($queryBuilder, 'tee', $userId);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Course teacher, course tutor, course coach in the session, or general coach of the
     * session. Single definition behind both public criteria methods above.
     *
     * @return array<int, Expr\Func>
     */
    private function getTeachingTerms(Expr $expr, string $trackExerciseAlias): array
    {
        return [
            $expr->exists(\sprintf(
                'SELECT 1 FROM %s access_teacher
                 WHERE access_teacher.course = %s.course
                   AND access_teacher.user = :access_current_user
                   AND (access_teacher.status = :access_teacher_status OR access_teacher.tutor = true)',
                CourseRelUser::class,
                $trackExerciseAlias
            )),
            $expr->exists(\sprintf(
                'SELECT 1 FROM %s access_course_coach
                 WHERE access_course_coach.course = %s.course
                   AND access_course_coach.session = %s.session
                   AND access_course_coach.user = :access_current_user
                   AND access_course_coach.status = :access_course_coach_status',
                SessionRelCourseRelUser::class,
                $trackExerciseAlias,
                $trackExerciseAlias
            )),
            $expr->exists(\sprintf(
                'SELECT 1 FROM %s access_general_coach
                 WHERE access_general_coach.session = %s.session
                   AND access_general_coach.user = :access_current_user
                   AND access_general_coach.relationType = :access_general_coach_relation',
                SessionRelUser::class,
                $trackExerciseAlias
            )),
        ];
    }

    private function getFollowerDql(string $trackExerciseAlias, string $alias, string $relationParameter): string
    {
        return \sprintf(
            'SELECT 1 FROM %s %s
             WHERE %s.user = %s.user
               AND %s.friend = :access_current_user
               AND %s.relationType = :%s',
            UserRelUser::class,
            $alias,
            $alias,
            $trackExerciseAlias,
            $alias,
            $alias,
            $relationParameter
        );
    }

    private function bindTeachingParameters(QueryBuilder $queryBuilder, int $userId): void
    {
        $queryBuilder
            ->setParameter('access_current_user', $userId)
            ->setParameter('access_teacher_status', CourseRelUser::TEACHER)
            ->setParameter('access_course_coach_status', Session::COURSE_COACH)
            ->setParameter('access_general_coach_relation', Session::GENERAL_COACH)
        ;
    }
}
