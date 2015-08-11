<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Chamilo\CoreBundle\Entity\Session;
use \Doctrine\ORM\Query\Expr\Join;
use Chamilo\CoreBundle\Entity\Course;

/**
 * SessionRepository
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 * @author  Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author  Julio Montoya <gugli100@gmail.com>
 */
class SessionRepository extends EntityRepository
{

    /**
     * Get session's courses ordered by position in session_rel_course
     * @param Session $session The session
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCoursesOrderedByPosition(Session $session)
    {
        $queryBuilder = $this->createQueryBuilder('s');

        $queryBuilder->select('DISTINCT c ')
            ->innerJoin('s.courses', 'src')
            ->innerJoin(
                'ChamiloCoreBundle:Course',
                'c',
                Join::WITH,
                'src.course = c.id'
            )
            ->where(
                $queryBuilder->expr()->eq('s.id', $session->getId())
            )
            ->orderBy('src.position');

        return $queryBuilder->getQuery()->getResult();
    }

}
