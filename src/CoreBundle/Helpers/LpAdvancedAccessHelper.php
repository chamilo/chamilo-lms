<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpRelUser;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class LpAdvancedAccessHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function isAllowed(Course $course, CLp $lp, ?Session $session, User $user): bool
    {
        $individualRestriction = $this->findIndividualRestriction($course, $lp, $session, $user);

        if ($individualRestriction instanceof CLpRelUser) {
            return $this->restrictionAllowsAccess($individualRestriction);
        }

        $groupRestrictions = $this->findGroupRestrictions($course, $lp, $session, $user);

        if ([] === $groupRestrictions) {
            return true;
        }

        foreach ($groupRestrictions as $restriction) {
            if ($this->restrictionAllowsAccess($restriction)) {
                return true;
            }
        }

        return false;
    }

    private function findIndividualRestriction(Course $course, CLp $lp, ?Session $session, User $user): ?CLpRelUser
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb
            ->select('rel')
            ->from(CLpRelUser::class, 'rel')
            ->where('rel.course = :course')
            ->andWhere('rel.lp = :lp')
            ->andWhere('rel.user = :user')
            ->andWhere('rel.group IS NULL')
            ->setParameter('course', $course)
            ->setParameter('lp', $lp)
            ->setParameter('user', $user)
            ->setMaxResults(1)
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('rel.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('rel.session IS NULL');
        }

        $restriction = $qb->getQuery()->getOneOrNullResult();

        return $restriction instanceof CLpRelUser ? $restriction : null;
    }

    /**
     * @return list<CLpRelUser>
     */
    private function findGroupRestrictions(Course $course, CLp $lp, ?Session $session, User $user): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb
            ->select('rel')
            ->from(CLpRelUser::class, 'rel')
            ->join(
                CGroupRelUser::class,
                'groupRelUser',
                'WITH',
                'groupRelUser.group = rel.group AND groupRelUser.user = :user AND groupRelUser.cId = :courseId'
            )
            ->where('rel.course = :course')
            ->andWhere('rel.lp = :lp')
            ->andWhere('rel.user = :user')
            ->andWhere('rel.group IS NOT NULL')
            ->setParameter('course', $course)
            ->setParameter('lp', $lp)
            ->setParameter('user', $user)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('rel.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('rel.session IS NULL');
        }

        /** @var list<CLpRelUser> $restrictions */
        $restrictions = $qb->getQuery()->getResult();

        return $restrictions;
    }

    private function restrictionAllowsAccess(CLpRelUser $restriction): bool
    {
        if ($restriction->getIsOpenWithoutDate()) {
            return true;
        }

        $now = new DateTimeImmutable();

        $startDate = $restriction->getStartDate();
        if ($startDate instanceof DateTimeInterface && $startDate > $now) {
            return false;
        }

        $endDate = $restriction->getEndDate();
        if ($endDate instanceof DateTimeInterface && $endDate < $now) {
            return false;
        }

        return true;
    }
}
