<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UserManager;

/**
 * Learner assignment for the "Student's superior follow up" report (formerly main/my_space/tc_report.php).
 * As in the legacy page, whose learner search and save endpoints required a platform administrator,
 * only administrators can assign learners; human resources managers only see the report.
 */
#[IsGranted('ROLE_ADMIN')]
class GlobalReportingStudentBossController extends BaseController
{
    private const int MAX_SEARCH_RESULTS = 20;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    #[Route('/global-reporting/student-bosses/learners-data', name: 'global_reporting_student_boss_learners', methods: ['GET'])]
    public function searchLearners(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query->get('q', ''));

        if ('' === $keyword) {
            return $this->json([]);
        }

        $learners = \array_slice(
            $this->userRepository->findByRole('ROLE_STUDENT', $keyword, $this->getAccessUrlId()),
            0,
            self::MAX_SEARCH_RESULTS
        );

        return $this->json(array_map(
            static fn (User $learner): array => [
                'id' => $learner->getId(),
                'label' => UserManager::formatUserFullName($learner, true),
            ],
            $learners
        ));
    }

    #[Route('/global-reporting/student-bosses/{bossId}/learners', name: 'global_reporting_student_boss_add_learner', requirements: ['bossId' => '\d+'], methods: ['POST'])]
    public function addLearner(int $bossId, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $learnerId = \is_array($payload) ? (int) ($payload['learnerId'] ?? 0) : 0;

        if ($learnerId <= 0) {
            throw new BadRequestHttpException('Missing learner.');
        }

        $boss = $this->findUserWithRoleInCurrentUrl($bossId, 'ROLE_STUDENT_BOSS');
        $learner = $this->findUserWithRoleInCurrentUrl($learnerId, 'ROLE_STUDENT');

        // Same call as the legacy page: the learner's boss list is replaced by this boss.
        UserManager::subscribeUserToBossList($learner->getId(), [$boss->getId()], true);

        return $this->json(['learnerId' => $learner->getId(), 'bossId' => $boss->getId()]);
    }

    /**
     * Same criteria as the legacy learner search: active, not anonymous, in the current URL, with the role.
     */
    private function findUserWithRoleInCurrentUrl(int $userId, string $role): User
    {
        $user = $this->userRepository->find($userId);

        if ($user instanceof User) {
            foreach ($this->userRepository->findByRole($role, $user->getUsername(), $this->getAccessUrlId()) as $candidate) {
                if ($candidate->getId() === $user->getId()) {
                    return $user;
                }
            }
        }

        throw new NotFoundHttpException('User not found.');
    }

    private function getAccessUrlId(): int
    {
        return (int) $this->accessUrlHelper->getCurrent()?->getId();
    }
}
