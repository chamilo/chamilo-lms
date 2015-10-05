<?php
/* For licensing terms, see /license.txt */
namespace Chamilo\CoreBundle\Entity\Repository;

use \Doctrine\ORM\EntityRepository;
use \Chamilo\UserBundle\Entity\User;
use \Chamilo\CoreBundle\Entity\Course;
use \Chamilo\CoreBundle\Entity\Session;
use \Doctrine\ORM\Query\Expr\Join;

/**
 * SkillRepository class
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class SkillRepository extends EntityRepository{

    /**
     * Get the last acquired skill by a user on course and/or session
     * @param User $user The user
     * @param Course $course The course
     * @param Session $session The session
     * @return Skill
     */
    public function getLastByUser(User $user, Course $course = null, Session $session = null)
    {
        $qb = $this->createQueryBuilder('s');

        $qb->innerJoin(
            'ChamiloCoreBundle:SkillRelUser',
            'su',
            Join::WITH,
            's.id = su.skillId'
        )
        ->where(
            $qb->expr()->eq('su.userId', $user->getId())
        );

        if ($course) {
            $qb->andWhere(
                $qb->expr()->eq('su.courseId', $course->getId())
            );
        }

        if ($session) {
            $qb->andWhere(
                $qb->expr()->eq('su.sessionId', $session->getId())
            );
        }

        $qb->setMaxResults(1)
            ->orderBy('su.id', 'DESC');

        return $qb->getQuery()->getOneOrNullResult();
    }

}
