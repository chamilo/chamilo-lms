<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

class CWikiCategoryRepository extends NestedTreeRepository
{
    public function findByCourse(Course $course, ?Session $session): array
    {
        return $this->findBy(['course' => $course, 'session' => $session], ['lft' => 'ASC']);
    }

    public function countByCourse(Course $course, ?Session $session): int
    {
        return $this->count(['course' => $course, 'session' => $session]);
    }

    /**
     * @return array|string
     */
    public function buildCourseTree(Course $course, ?Session $session, array $options = [])
    {
        $whereParams = ['course' => $course];

        if ($session) {
            $whereParams['session'] = $session;
        }

        $qb = $this->createQueryBuilder('c')
            ->where('c.course = :course')
            ->andWhere($session ? 'c.session = :session' : 'c.session IS NULL')
            ->orderBy('c.lft', 'ASC')
            ->setParameters($whereParams)
            ->getQuery()
        ;

        return $this->buildTree($qb->getArrayResult(), $options);
    }
}
