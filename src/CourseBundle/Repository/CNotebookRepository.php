<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CNotebook;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResourceRepository<CNotebook>
 */
class CNotebookRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CNotebook::class);
    }

    /**
     * Get the user notebooks in a course.
     */
    public function findByUser(
        User $user,
        Course $course,
        ?Session $session = null,
        string $orderField = 'creation_date',
        string $orderDirection = 'DESC'
    ): array {
        $qb = $this->getResourcesByCourse($course, $session);

        $alias = $qb->getRootAliases()[0] ?? 'n';

        $map = [
            'creation_date' => 'creationDate',
            'update_date'   => 'updateDate',
            'title'         => 'title',
        ];
        $prop = $map[$orderField] ?? 'creationDate';

        $qb->andWhere($qb->expr()->eq($alias.'.user', ':owner'))
            ->setParameter('owner', $user);

        $direction = strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy($alias.'.'.$prop, $direction);

        return $qb->getQuery()->getResult();
    }
}
