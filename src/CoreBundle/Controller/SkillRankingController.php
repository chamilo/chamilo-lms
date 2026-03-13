<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\SkillRelGradebook;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/skill/ranking-data')]
class SkillRankingController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route('', name: 'skill_ranking_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $rows = $this->em->createQueryBuilder()
            ->select('u.id AS userId, u.firstname, u.lastname, COUNT(sru.id) AS skillsAcquired')
            ->from(SkillRelUser::class, 'sru')
            ->join('sru.user', 'u')
            ->andWhere('u.active != :softDeleted')
            ->setParameter('softDeleted', -2)
            ->groupBy('u.id, u.firstname, u.lastname')
            ->orderBy('COUNT(sru.id)', 'DESC')
            ->getQuery()
            ->getArrayResult()
        ;

        $currentlyLearningByUser = $this->fetchCurrentlyLearningMap(
            array_column($rows, 'userId')
        );

        $rank = 1;
        $result = [];
        foreach ($rows as $row) {
            $userId = (int) $row['userId'];
            $result[] = [
                'rank' => $rank++,
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'avatarUrl' => $this->userRepository->getUserPicture($userId),
                'skillsAcquired' => (int) $row['skillsAcquired'],
                'currentlyLearning' => $currentlyLearningByUser[$userId] ?? 0,
            ];
        }

        return $this->json($result);
    }

    /**
     * Returns a map of userId => currently_learning count.
     *
     * "currently_learning" = total number of skills attached (via gradebook)
     * to all courses the user is enrolled in (directly or via a session).
     *
     * @param int[] $userIds
     *
     * @return array<int, int>
     */
    private function fetchCurrentlyLearningMap(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $direct = $this->em->createQueryBuilder()
            ->select('IDENTITY(cru.user) AS userId, IDENTITY(cru.course) AS courseId')
            ->from(CourseRelUser::class, 'cru')
            ->where('IDENTITY(cru.user) IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->distinct()
            ->getQuery()
            ->getArrayResult()
        ;

        $viaSession = $this->em->createQueryBuilder()
            ->select('IDENTITY(srcru.user) AS userId, IDENTITY(srcru.course) AS courseId')
            ->from(SessionRelCourseRelUser::class, 'srcru')
            ->where('IDENTITY(srcru.user) IN (:userIds)')
            ->setParameter('userIds', $userIds)
            ->distinct()
            ->getQuery()
            ->getArrayResult()
        ;

        $enrolmentRows = array_merge($direct, $viaSession);

        if (empty($enrolmentRows)) {
            return [];
        }

        $courseIds = array_values(array_unique(array_column($enrolmentRows, 'courseId')));

        $skillCountRows = $this->em->createQueryBuilder()
            ->select('IDENTITY(gc.course) AS courseId, COUNT(srg.id) AS cnt')
            ->from(SkillRelGradebook::class, 'srg')
            ->join('srg.gradeBookCategory', 'gc')
            ->where('IDENTITY(gc.course) IN (:courseIds)')
            ->setParameter('courseIds', $courseIds)
            ->groupBy('gc.course')
            ->getQuery()
            ->getArrayResult()
        ;

        $skillsPerCourse = [];
        foreach ($skillCountRows as $row) {
            $skillsPerCourse[(int) $row['courseId']] = (int) $row['cnt'];
        }

        $map = [];
        foreach ($enrolmentRows as $row) {
            $uid = (int) $row['userId'];
            $cid = (int) $row['courseId'];
            $map[$uid] = ($map[$uid] ?? 0) + ($skillsPerCourse[$cid] ?? 0);
        }

        return $map;
    }
}
