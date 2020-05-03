<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Class CourseRepository.
 *
 * The functions inside this class must return an instance of QueryBuilder.
 */
class CourseRepository extends ResourceRepository
{
    /**
     * @param Session $session
     *
     * @return ArrayCollection
     */
    public function getTools(Course $course, Session $session = null)
    {
        $orWhere = Criteria::expr()->eq('sessionId', 0);

        if ($session) {
            $orWhere = Criteria::expr()->in('sessionId', [0, $session->getId()]);
        }

        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('sessionId'))
            ->orWhere($orWhere);

        return $course->getTools()->matching($criteria);
    }

    public function deleteCourse(Course $course): void
    {
        $em = $this->getEntityManager();

        // Deleting all nodes connected to the course:
        $node = $course->getResourceNode();
        $children = $node->getChildren();
        /** @var ResourceNode $child */
        foreach ($children as $child) {
            $em->remove($child);
        }

        $em->remove($course);
        $em->flush();
    }

    /**
     * @param string $code
     *
     * @return Course
     */
    public function findOneByCode($code)
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * Get all users that are registered in the course. No matter the status.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getSubscribedUsers(Course $course)
    {
        // Course builder
        $queryBuilder = $this->getRepository()->createQueryBuilder('c');

        // Selecting user info.
        $queryBuilder->select('DISTINCT user');

        // Selecting courses for users.
        $queryBuilder->innerJoin('c.users', 'subscriptions');
        $queryBuilder->innerJoin(
            'ChamiloCoreBundle:User',
            'user',
            Join::WITH,
            'subscriptions.user = user.id'
        );

        if (api_is_western_name_order()) {
            $queryBuilder->add('orderBy', 'user.firstname ASC');
        } else {
            $queryBuilder->add('orderBy', 'user.lastname ASC');
        }

        $wherePart = $queryBuilder->expr()->andx();

        // Get only users subscribed to this course
        $wherePart->add($queryBuilder->expr()->eq('c.id', $course->getId()));

        // $wherePart->add($queryBuilder->expr()->eq('c.status', $status));

        $queryBuilder->where($wherePart);

        return $queryBuilder;
    }

    /**
     * Gets students subscribed in the course.
     *
     * @return QueryBuilder
     */
    public function getSubscribedStudents(Course $course)
    {
        return $this->getSubscribedUsersByStatus($course, STUDENT);
    }

    /**
     * Gets the students subscribed in the course.
     *
     * @return QueryBuilder
     */
    public function getSubscribedCoaches(Course $course)
    {
        return $this->getSubscribedUsers($course);
    }

    /**
     * Gets the teachers subscribed in the course.
     *
     * @return QueryBuilder
     */
    public function getSubscribedTeachers(Course $course)
    {
        return $this->getSubscribedUsersByStatus($course, COURSEMANAGER);
    }

    /**
     * @param int $status use legacy chamilo constants COURSEMANAGER|STUDENT
     *
     * @return QueryBuilder
     */
    public function getSubscribedUsersByStatus(Course $course, $status)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq('subscriptions.status', $status)
            );

        return $queryBuilder;
    }

    public function getCoursesWithNoSession($urlId)
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('c');
        $queryBuilder = $queryBuilder
            ->select('c')
            ->leftJoin('c.urls', 'u')
            ->leftJoin('c.sessions', 's')
            /*->leftJoin(
                'ChamiloCoreBundle:SessionRelCourse',
                'sc',
                Join::WITH,
                'c != sc.course'
            )->leftJoin(
                'ChamiloCoreBundle:AccessUrlRelCourse',
                'ac',
                Join::WITH,
                'c = ac.course'
            )*/
            ->where($queryBuilder->expr()->isNull('s'))
            //->where($queryBuilder->expr()->eq('s', 0))
            ->where($queryBuilder->expr()->eq('u.url', $urlId))
            ->getQuery();

        $courses = $queryBuilder->getResult();
        $courseList = [];
        /** @var Course $course */
        foreach ($courses as $course) {
            if (empty(0 == $course->getSessions()->count())) {
                $courseList[] = $course;
            }
        }

        return $courseList;
    }
}
