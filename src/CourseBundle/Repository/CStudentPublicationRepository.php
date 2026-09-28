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
use Chamilo\CoreBundle\Service\Assignment\AssignmentGroupCategoryResolver;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupCategory;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelUser;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Join;
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
            ->leftJoin(CStudentPublicationRelUser::class, 'rel', Join::ON, 'rel.publication = resource AND rel.user = :userId')
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
        // - groupId > 0: direct group resources plus one shared assignment
        //   whose group_category_work_id matches the current group's category.
        // - groupId = 0: keep group-only resources and shared group assignments
        //   out of the normal learner list.
        if ($groupId > 0) {
            $groupCategoryId = $this->findGroupCategoryIdInContext($course, $session, $groupId);
            $groupConditions = $qb->expr()->orX(
                'IDENTITY(rl.group) = :gid',
                'resource.groupCategoryWorkId = :allGroupsWorkId',
            );

            if ($groupCategoryId > 0) {
                $groupConditions->add('resource.groupCategoryWorkId = :groupCategoryWorkId');
                $qb->setParameter('groupCategoryWorkId', $groupCategoryId);
            }

            $qb
                ->andWhere($groupConditions)
                ->setParameter('gid', $groupId)
                ->setParameter('allGroupsWorkId', AssignmentGroupCategoryResolver::ALL_GROUPS)
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
                ->andWhere('resource.groupCategoryWorkId = 0')
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

    private function findGroupCategoryIdInContext(Course $course, ?Session $session, int $groupId): int
    {
        /** @var CGroupRepository $groupRepository */
        $groupRepository = $this->getEntityManager()->getRepository(CGroup::class);
        $qb = $groupRepository->getResourcesByCourse($course, $session);
        $qb
            ->andWhere('resource.iid = :groupId')
            ->setParameter('groupId', $groupId)
        ;

        $group = $qb->getQuery()->getOneOrNullResult();
        if (!$group instanceof CGroup) {
            return 0;
        }

        $categoryId = (int) ($group->getCategory()?->getIid() ?? 0);
        if ($categoryId > 0) {
            return $categoryId;
        }

        /** @var CGroupCategoryRepository $categoryRepository */
        $categoryRepository = $this->getEntityManager()->getRepository(CGroupCategory::class);
        $categories = $categoryRepository
            ->getResourcesByCourse(
                course: $course,
                session: $session,
                displayOnlyPublished: false,
                displayOrder: true,
            )
            ->getQuery()
            ->getResult()
        ;
        $defaultCategory = $categories[0] ?? null;

        return $defaultCategory instanceof CGroupCategory ? (int) $defaultCategory->getIid() : 0;
    }

    public function findStudentProgressByCourse(Course $course, ?Session $session): array
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder();
        $qb->select('sp')
            ->from(CStudentPublication::class, 'sp')
            ->join('sp.resourceNode', 'rn')
            ->join(ResourceLink::class, 'rl', Join::ON, 'rl.resourceNode = rn')
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
        Course $course,
        ?Session $session,
        int $groupId,
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
            ->andWhere('resourceLink.course = :course')
            ->setParameter('assignmentId', $assignmentId)
            ->setParameter('filetype', 'file')
            ->setParameter('publishedVisibility', 2)
            ->setParameter('course', $course)
        ;

        if ($session instanceof Session) {
            $qb->andWhere('resourceLink.session = :session')
                ->setParameter('session', $session)
            ;
        } else {
            $qb->andWhere('resourceLink.session IS NULL');
        }

        if ($groupId > 0) {
            $qb->andWhere('IDENTITY(resourceLink.group) = :groupId')
                ->setParameter('groupId', $groupId)
            ;
        } else {
            $qb->andWhere('resourceLink.group IS NULL');
        }

        $qb->andWhere('submission.user = :user')
            ->setParameter('user', $user)
        ;

        $allowedOrderFields = ['title', 'sentDate', 'qualification'];
        foreach ($order as $field => $direction) {
            if (!\in_array($field, $allowedOrderFields, true)) {
                continue;
            }

            $sortDirection = 'asc' === strtolower((string) $direction) ? 'ASC' : 'DESC';
            $qb->addOrderBy('submission.'.$field, $sortDirection);
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
            if ('user.fullName' === $field) {
                $qb->addOrderBy('user.lastname', $direction);
                $qb->addOrderBy('user.firstname', $direction);
            } elseif (str_starts_with($field, 'user.')) {
                $qb->addOrderBy($field, $direction);
            } else {
                $qb->addOrderBy('submission.'.$field, $direction);
            }
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
