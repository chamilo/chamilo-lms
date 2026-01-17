<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelUser;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

final class CStudentPublicationRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CStudentPublication::class);
    }

    public function findAllByCourse(
        Course $course,
        ?Session $session = null,
        ?string $title = null,
        ?int $active = null,
        ?string $fileType = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session);

        $this->addTitleQueryBuilder($title, $qb);
        $this->addActiveQueryBuilder($active, $qb);
        $this->addFileTypeQueryBuilder($fileType, $qb);

        return $qb;
    }

    public function getStudentAssignments(
        CStudentPublication $publication,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null,
        ?User $user = null
    ): QueryBuilder {
        $qb = $this->getResourcesByCourse($course, $session, $group);

        $this->addNotDeletedPublicationQueryBuilder($qb);
        $qb
            ->andWhere('resource.publicationParent =:publicationParent')
            ->setParameter('publicationParent', $publication)
        ;

        return $qb;
    }

    public function getStudentPublicationByUser(User $user, Course $course, ?Session $session = null): array
    {
        $qb = $this->findAllByCourse($course, $session);

        /** @var CStudentPublication[] $works */
        $works = $qb->getQuery()->getResult();
        $list = [];
        foreach ($works as $work) {
            $qb = $this->getStudentAssignments($work, $course, $session, null, $user);
            $results = $qb->getQuery()->getResult();
            $list[$work->getIid()]['work'] = $work;
            $list[$work->getIid()]['results'] = $results;
        }

        return $list;
    }

    public function countUserPublications(
        User $user,
        Course $course,
        ?Session $session = null,
        ?CGroup $group = null
    ): int {
        $qb = $this->getResourcesByCourseLinkedToUser($user, $course, $session);
        $qb->andWhere('resource.publicationParent IS NOT NULL');

        return $this->getCount($qb);
    }

    public function countCoursePublications(Course $course, ?Session $session = null, ?CGroup $group = null): int
    {
        $qb = $this->getResourcesByCourse($course, $session, $group);

        $this->addNotDeletedPublicationQueryBuilder($qb);

        return $this->getCount($qb);
    }

    /**
     * Find all the works registered by a teacher.
     */
    public function findWorksByTeacher(User $user, Course $course, ?Session $session = null): array
    {
        $qb = $this->getResourcesByCourseLinkedToUser($user, $course, $session);
        $qb->andWhere('resource.publicationParent IS NOT NULL');

        return $qb
            ->orderBy('resource.sentDate', Criteria::ASC)
            ->getQuery()
            ->getResult()
        ;
    }

    private function addActiveQueryBuilder(?int $active = null, ?QueryBuilder $qb = null): void
    {
        $qb = $this->getOrCreateQueryBuilder($qb);

        if (null !== $active) {
            $qb
                ->andWhere('resource.active = :active')
                ->setParameter('active', $active)
            ;
        }
    }

    private function addNotDeletedPublicationQueryBuilder(?QueryBuilder $qb = null): void
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        $qb
            ->andWhere('resource.active <> 2')
        ;
    }

    private function addFileTypeQueryBuilder(?string $fileType, ?QueryBuilder $qb = null): void
    {
        $qb = $this->getOrCreateQueryBuilder($qb);
        if (null === $fileType) {
            return;
        }

        $qb
            ->andWhere('resource.filetype = :filetype')
            ->setParameter('filetype', $fileType)
        ;
    }

    public function findVisibleAssignmentsForStudent(Course $course, ?Session $session = null, int $groupId = 0): array
    {
        $userId = api_get_user_id();

        $qb = $this->createQueryBuilder('resource')
            ->select('resource')
            ->addSelect('(SELECT COUNT(comment.iid) FROM '.CStudentPublicationComment::class.' comment WHERE comment.publication = resource) AS commentsCount')
            ->addSelect('(SELECT COUNT(c1.iid) FROM '.CStudentPublication::class.' c1 WHERE c1.publicationParent = resource AND c1.extensions IS NOT NULL AND c1.extensions <> \'\') AS correctionsCount')
            ->addSelect('(SELECT MAX(c2.sentDate) FROM '.CStudentPublication::class.' c2 WHERE c2.publicationParent = resource) AS lastUpload')
            ->join('resource.resourceNode', 'rn')
            ->join('rn.resourceLinks', 'rl')
            ->leftJoin(CStudentPublicationRelUser::class, 'rel', 'WITH', 'rel.publication = resource AND rel.user = :userId')
            ->where('resource.publicationParent IS NULL')
            ->andWhere('resource.active IN (0, 1)')
            ->andWhere('resource.filetype = :filetype')
            ->setParameter('filetype', 'folder')
            ->andWhere('rl.visibility = 2')
            ->andWhere('rl.course = :course')
            ->setParameter('course', $course)
            ->setParameter('userId', $userId)
            ->orderBy('resource.sentDate', 'DESC')
            ->distinct()
        ;

        if ($session) {
            $qb->andWhere('rl.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('rl.session IS NULL');
        }

        // Group context filtering:
        // - groupId > 0: only resources linked to that group
        // - groupId = 0: exclude any resource that has at least one group link (avoid leaks into course context)
        if ($groupId > 0) {
            $qb->andWhere('IDENTITY(rl.group) = :gid')
                ->setParameter('gid', $groupId)
            ;
        } else {
            $with = 'rl_group.course = :course AND rl_group.group IS NOT NULL';
            if ($session) {
                $with .= ' AND rl_group.session = :session';
            } else {
                $with .= ' AND rl_group.session IS NULL';
            }

            $qb->leftJoin('rn.resourceLinks', 'rl_group', 'WITH', $with)
                ->andWhere('rl_group.id IS NULL')
            ;
        }

        $qb->andWhere('
        NOT EXISTS (
            SELECT 1 FROM '.CStudentPublicationRelUser::class.' rel_all
            WHERE rel_all.publication = resource
        )
        OR rel.iid IS NOT NULL
    ');

        return $qb->getQuery()->getResult();
    }

    public function findStudentProgressByCourse(Course $course, ?Session $session): array
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder();
        $qb->select('sp')
            ->from(CStudentPublication::class, 'sp')
            ->join('sp.resourceNode', 'rn')
            ->join(ResourceLink::class, 'rl', 'WITH', 'rl.resourceNode = rn')
            ->where('rl.course = :course')
            ->andWhere($session ? 'rl.session = :session' : 'rl.session IS NULL')
            ->andWhere('sp.active IN (0, 1)')
            ->andWhere('sp.filetype = :filetype')
            ->andWhere('sp.publicationParent IS NULL')
            ->setParameter('course', $course)
            ->setParameter('filetype', 'folder')
        ;

        if ($session) {
            $qb->setParameter('session', $session);
        }

        $workFolders = $qb->getQuery()->getResult();

        if (empty($workFolders)) {
            return [];
        }

        $workIds = array_map(fn (CStudentPublication $sp) => $sp->getIid(), $workFolders);

        if ($session) {
            $students = $em->getRepository(SessionRelCourseRelUser::class)->findBy([
                'session' => $session,
                'course' => $course,
                'status' => Session::STUDENT,
            ]);
        } else {
            $students = $em->getRepository(CourseRelUser::class)->findBy([
                'course' => $course,
                'status' => CourseRelUser::STUDENT,
            ]);
        }

        if (empty($students)) {
            return [];
        }

        $studentProgress = [];

        foreach ($students as $studentRel) {
            $user = $studentRel->getUser();

            $qb = $em->createQueryBuilder();
            $qb->select('COUNT(DISTINCT sp.publicationParent)')
                ->from(CStudentPublication::class, 'sp')
                ->where('sp.user = :user')
                ->andWhere('sp.publicationParent IN (:workIds)')
                ->andWhere('sp.active IN (0, 1)')
                ->setParameter('user', $user)
                ->setParameter('workIds', $workIds)
            ;

            $submissionCount = (int) $qb->getQuery()->getSingleScalarResult();

            $studentProgress[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'submissions' => $submissionCount,
                'totalAssignments' => \count($workIds),
            ];
        }

        return $studentProgress;
    }

    public function findAssignmentSubmissionsPaginated(
        int $assignmentId,
        User $user,
        int $page,
        int $itemsPerPage,
        array $order = []
    ): array {
        $qb = $this->createQueryBuilder('submission')
            ->leftJoin('submission.resourceNode', 'resourceNode')
            ->join('resourceNode.resourceLinks', 'resourceLink')
            ->leftJoin('submission.comments', 'comments')
            ->addSelect('comments')
            ->leftJoin('submission.publicationParent', 'assignment')
            ->addSelect('assignment')
            ->where('submission.publicationParent = :assignmentId')
            ->andWhere('submission.filetype = :filetype')
            ->andWhere('resourceLink.visibility = :publishedVisibility')
            ->setParameter('assignmentId', $assignmentId)
            ->setParameter('filetype', 'file')
            ->setParameter('publishedVisibility', 2)
        ;

        $qb->andWhere('submission.user = :user')
            ->setParameter('user', $user)
        ;

        foreach ($order as $field => $direction) {
            $qb->addOrderBy('submission.'.$field, $direction);
        }

        $qb->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;

        $paginator = new Paginator($qb);

        return [
            iterator_to_array($paginator),
            \count($paginator),
        ];
    }

    public function findAllSubmissionsByAssignment(
        int $assignmentId,
        int $page,
        int $itemsPerPage,
        array $order = []
    ): array {
        $qb = $this->createQueryBuilder('submission')
            ->leftJoin('submission.user', 'user')
            ->addSelect('user')
            ->leftJoin('submission.comments', 'comments')
            ->addSelect('comments')
            ->where('submission.publicationParent = :assignmentId')
            ->andWhere('submission.filetype = :filetype')
            ->setParameter('assignmentId', $assignmentId)
            ->setParameter('filetype', 'file')
        ;

        foreach ($order as $field => $direction) {
            $qb->addOrderBy('submission.'.$field, $direction);
        }

        $qb->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;

        $paginator = new Paginator($qb);

        return [
            iterator_to_array($paginator),
            \count($paginator),
        ];
    }

    public function findUserIdsWithSubmissions(int $assignmentId): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->select('DISTINCT u.id')
            ->join('sp.user', 'u')
            ->where('sp.publicationParent = :assignmentId')
            ->andWhere('sp.active IN (0,1)')
            ->setParameter('assignmentId', $assignmentId)
        ;

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }

    public function findAllCorrectionsByAssignment(int $assignmentId): array
    {
        return $this->createQueryBuilder('correction')
            ->leftJoin('correction.publicationParent', 'assignment')
            ->where('assignment.iid = :assignmentId')
            ->andWhere('correction.filetype = :filetype')
            ->andWhere('correction.extensions IS NOT NULL')
            ->setParameter('assignmentId', $assignmentId)
            ->setParameter('filetype', 'file')
            ->getQuery()
            ->getResult()
        ;
    }
}
