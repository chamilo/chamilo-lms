<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpRelUser;
use Chamilo\CourseBundle\Entity\CLpRelUserGroup;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;

final readonly class LpAdvancedAccessHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
    ) {}

    public function isAllowed(Course $course, CLp $lp, ?Session $session, User $user): bool
    {
        $individualRestriction = $this->findIndividualRestriction($course, $lp, $session, $user);

        if ($individualRestriction instanceof CLpRelUser) {
            return $this->restrictionAllowsAccess($individualRestriction);
        }

        $groupRestrictions = $this->findGroupRestrictions($course, $lp, $session, $user);

        foreach ($groupRestrictions as $restriction) {
            if ($this->restrictionAllowsAccess($restriction)) {
                return true;
            }
        }

        $userGroupAccess = $this->getUserGroupAccessState($course, $lp, $session, $user);
        if ($userGroupAccess['restricted']) {
            return $userGroupAccess['allowed'];
        }

        return [] === $groupRestrictions;
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
            ->setParameter('course', (int) $course->getId(), Types::INTEGER)
            ->setParameter('lp', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('user', (int) $user->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('rel.session = :session')
                ->setParameter('session', (int) $session->getId(), Types::INTEGER)
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
            ->setParameter('course', (int) $course->getId(), Types::INTEGER)
            ->setParameter('lp', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('user', (int) $user->getId(), Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('rel.session = :session')
                ->setParameter('session', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $qb->andWhere('rel.session IS NULL');
        }

        /** @var list<CLpRelUser> $restrictions */
        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{restricted: bool, allowed: bool}
     */
    private function getUserGroupAccessState(Course $course, CLp $lp, ?Session $session, User $user): array
    {
        if (!$this->settingEnabled('lp.allow_lp_subscription_to_usergroups')) {
            return [
                'restricted' => false,
                'allowed' => false,
            ];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('COUNT(DISTINCT relation.id) AS assignmentCount')
            ->addSelect('COUNT(DISTINCT membership.id) AS membershipCount')
            ->from(CLpRelUserGroup::class, 'relation')
            ->leftJoin('relation.userGroup', 'userGroup')
            ->leftJoin('userGroup.users', 'membership', 'WITH', 'membership.user = :user')
            ->where('relation.course = :course')
            ->andWhere('relation.lp = :lp')
            ->setParameter('course', (int) $course->getId(), Types::INTEGER)
            ->setParameter('lp', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('user', (int) $user->getId(), Types::INTEGER)
        ;

        if ($session instanceof Session) {
            $qb
                ->andWhere('relation.session = :session')
                ->setParameter('session', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $qb->andWhere('relation.session IS NULL');
        }

        /** @var array{assignmentCount: string|int, membershipCount: string|int} $result */
        $result = $qb->getQuery()->getSingleResult();

        return [
            'restricted' => (int) $result['assignmentCount'] > 0,
            'allowed' => (int) $result['membershipCount'] > 0,
        ];
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

    private function settingEnabled(string $name): bool
    {
        return \in_array(
            strtolower(trim((string) $this->settingsManager->getSetting($name))),
            ['1', 'true', 'yes', 'on'],
            true,
        );
    }
}
