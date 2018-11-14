<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * SkillRepository class.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class SkillRepository extends ServiceEntityRepository
{
    /**
     * SkillRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
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
            );

        if ($course) {
            $qb->andWhere(
                $qb->expr()->eq('su.course', $course->getId())
            );
        }

        if ($session) {
            $qb->andWhere(
                $qb->expr()->eq('su.session', $session->getId())
            );
        }

        $qb
            ->setMaxResults(1)
            ->orderBy('su.id', 'DESC');

        return $qb->getQuery()->getOneOrNullResult();
    }
}
