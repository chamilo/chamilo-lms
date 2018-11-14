<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\UserBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Class CNotebookRepository.
 *
 * @package Chamilo\CourseBundle\Repository
 */
class CNotebookRepository extends ServiceEntityRepository
{
    /**
     * CNotebookRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CNotebook::class);
    }

    /**
     * Get the user notebooks in a course.
     *
     * @param User         $user
     * @param Course       $course
     * @param Session|null $session
     * @param string       $orderField
     * @param string       $orderDirection
     *
     * @return array
     */
    public function findByUser(
        User $user,
        Course $course,
        Session $session = null,
        $orderField = 'creation_date',
        $orderDirection = 'DESC'
    ) {
        switch ($orderField) {
            case 'creation_date':
                $orderField = 'N.creationDate';
                break;
            case 'update_date':
                $orderField = 'N.updateDate';
                break;
            case 'title':
                $orderField = 'N.title';
                break;
        }

        $qb = $this->createQueryBuilder('N');
        $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('N.userId', $user->getId()),
                    $qb->expr()->eq('N.cId', $course->getId())
                )
            );

        if ($session) {
            $qb->andWhere(
                $qb->expr()->eq('N.sessionId', $session->getId())
            );
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('N.sessionId', 0),
                    $qb->expr()->isNull('N.sessionId')
                )
            );
        }

        if ($orderField === 'N.updateDate') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->neq('N.updateDate', ''),
                    $qb->expr()->isNotNull('N.updateDate')
                )
            );
        }

        $qb->orderBy($orderField, $orderDirection);

        return $qb->getQuery()->getResult();
    }
}
