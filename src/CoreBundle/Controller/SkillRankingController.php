<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/skill/ranking-data')]
class SkillRankingController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly UserRepository $userRepository,
    ) {}

    #[Route('', name: 'skill_ranking_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Replicate the getUserListSkillRanking logic: rank users by number of acquired skills.
        $sql = '
            SELECT
                u.id        AS userId,
                u.firstname AS firstname,
                u.lastname  AS lastname,
                COUNT(su.skill_id) AS skillsAcquired
            FROM skill_rel_user su
            INNER JOIN user u ON u.id = su.user_id
            WHERE u.active <> -1
            GROUP BY u.id, u.firstname, u.lastname
            ORDER BY skillsAcquired DESC
        ';

        $rows = $this->connection->fetchAllAssociative($sql);

        // For each user calculate currently_learning:
        // sum of skills-per-course across all courses the user is enrolled in.
        $currentlyLearningByUser = $this->fetchCurrentlyLearningMap(
            array_column($rows, 'userId')
        );

        $rank = 1;
        $result = [];
        foreach ($rows as $row) {
            $userId = (int) $row['userId'];
            $result[] = [
                'rank'              => $rank++,
                'firstname'         => $row['firstname'],
                'lastname'          => $row['lastname'],
                'avatarUrl'         => $this->userRepository->getUserPicture($userId),
                'skillsAcquired'    => (int) $row['skillsAcquired'],
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

        // Build a list of (user_id, c_id) pairs from both direct and session enrolments.
        $sql = '
            SELECT DISTINCT user_id, c_id
            FROM course_rel_user
            WHERE user_id IN (:ids)
            UNION
            SELECT DISTINCT user_id, c_id
            FROM session_rel_course_rel_user
            WHERE user_id IN (:ids)
        ';

        $enrolmentRows = $this->connection->fetchAllAssociative(
            $sql,
            ['ids' => $userIds],
            ['ids' => Connection::PARAM_INT_ARRAY]
        );

        if (empty($enrolmentRows)) {
            return [];
        }

        // Get skill counts per course (c_id).
        $courseIds = array_unique(array_column($enrolmentRows, 'c_id'));

        $skillCountSql = '
            SELECT gc.c_id, COUNT(sg.skill_id) AS cnt
            FROM gradebook_category gc
            INNER JOIN skill_rel_gradebook sg ON sg.gradebook_id = gc.id
            WHERE gc.c_id IN (:cids)
            GROUP BY gc.c_id
        ';

        $skillCountRows = $this->connection->fetchAllAssociative(
            $skillCountSql,
            ['cids' => $courseIds],
            ['cids' => Connection::PARAM_INT_ARRAY]
        );

        $skillsPerCourse = [];
        foreach ($skillCountRows as $row) {
            $skillsPerCourse[(int) $row['c_id']] = (int) $row['cnt'];
        }

        // Aggregate per user.
        $map = [];
        foreach ($enrolmentRows as $row) {
            $uid = (int) $row['user_id'];
            $cid = (int) $row['c_id'];
            if (!isset($map[$uid])) {
                $map[$uid] = 0;
            }
            $map[$uid] += $skillsPerCourse[$cid] ?? 0;
        }

        return $map;
    }
}
