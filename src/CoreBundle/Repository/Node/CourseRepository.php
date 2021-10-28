<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CourseRepository.
 *
 * The functions inside this class should return an instance of QueryBuilder.
 */
class CourseRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function deleteCourse(Course $course): void
    {
        $em = $this->getEntityManager();

        // Deleting all nodes connected to the course:
        //$node = $course->getResourceNode();
        //$children = $node->getChildren();
        ///* var ResourceNode $child
        /*foreach ($children as $child) {
            var_dump($child->getId().'-'.$child->getTitle().'<br />');
            var_dump(get_class($child));
            $em->remove($child);
        }*/

        $em->remove($course);
        $em->flush();
        $em->clear();
    }

    public function findOneByCode(string $code): ?Course
    {
        return $this->findOneBy([
            'code' => $code,
        ]);
    }

    /**
     * Get course user relationship based in the course_rel_user table.
     *
     * @return CourseRelUser[]
     */
    public function getCoursesByUser(User $user, AccessUrl $url)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('courseRelUser')
            ->from(Course::class, 'c')
            ->innerJoin(CourseRelUser::class, 'courseRelUser')
            //->innerJoin('c.users', 'courseRelUser')
            ->innerJoin('c.urls', 'accessUrlRelCourse')
            ->where('courseRelUser.user = :user')
            ->andWhere('accessUrlRelCourse.url = :url')
            ->setParameters([
                'user' => $user,
                'url' => $url,
            ])
        ;

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * Get all users that are registered in the course. No matter the status.
     *
     * @return QueryBuilder
     */
    public function getSubscribedUsers(Course $course)
    {
        // Course builder
        $queryBuilder = $this->createQueryBuilder('c');

        // Selecting user info.
        $queryBuilder->select('DISTINCT user');

        // Selecting courses for users.
        $queryBuilder->innerJoin('c.users', 'subscriptions');
        $queryBuilder->innerJoin(
            User::class,
            'user',
            Join::WITH,
            'subscriptions.user = user.id'
        );

        if (api_is_western_name_order()) {
            $queryBuilder->orderBy('user.firstname', Criteria::ASC);
        } else {
            $queryBuilder->orderBy('user.lastname', Criteria::ASC);
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
        return $this->getSubscribedUsersByStatus($course, ChamiloApi::COURSE_MANAGER);
    }

    /**
     * @param int $status use legacy chamilo constants COURSEMANAGER|STUDENT
     *
     * @return QueryBuilder
     */
    public function getSubscribedUsersByStatus(Course $course, int $status)
    {
        $queryBuilder = $this->getSubscribedUsers($course);
        $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->eq('subscriptions.status', $status)
            )
        ;

        return $queryBuilder;
    }
}
