<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SkillRepository class.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    /**
     * Get the last acquired skill by a user on course and/or session.
     *
     * @param User    $user    The user
     * @param Course  $course  The course
     * @param Session $session The session
     *
     * @return Skill
     */
    public function getLastByUser(User $user, Course $course = null, Session $session = null)
    {
        $qb = $this->createQueryBuilder('s');

        $qb
            ->innerJoin(
                'ChamiloCoreBundle:SkillRelUser',
                'su',
                Join::WITH,
                's.id = su.skill'
            )
            ->where(
                $qb->expr()->eq('su.user', $user->getId())
            )
        ;

        if (null !== $course) {
            $qb->andWhere(
                $qb->expr()->eq('su.course', $course->getId())
            );
        }

        if (null !== $session) {
            $qb->andWhere(
                $qb->expr()->eq('su.session', $session->getId())
            );
        }

        $qb
            ->setMaxResults(1)
            ->orderBy('su.id', Criteria::DESC)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }
}
