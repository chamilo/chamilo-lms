<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CourseFromRequestHelper;
use Chamilo\CoreBundle\Service\LearningPath\LearningPathTrackingRecycleBin;
use Chamilo\CoreBundle\State\LearningPath\LearningPathReportingProvider;
use Chamilo\CoreBundle\State\LearningPath\LearningPathStateHelperTrait;
use Chamilo\CourseBundle\Entity\CLp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_CURRENT_COURSE_TEACHER") or is_granted("ROLE_CURRENT_COURSE_SESSION_TEACHER")'))]
final class LearningPathTrackingRecycleBinController extends AbstractController
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseFromRequestHelper $courseFromRequestHelper,
        private readonly Security $security,
        private readonly LearningPathReportingProvider $reportingProvider,
        private readonly LearningPathTrackingRecycleBin $recycleBin,
    ) {}

    #[Route('/api/learning_paths/{lpId}/reporting/recycle-bin', name: 'learning_path_tracking_recycle_list', requirements: ['lpId' => '\d+'], methods: ['GET'])]
    public function list(int $lpId, Request $request): JsonResponse
    {
        [, $course, $session, $users] = $this->requireContext($lpId, $request);

        return $this->json(['items' => $this->recycleBin->listForLearningPath(
            $lpId,
            (int) $course->getId(),
            $session?->getId(),
            $users,
        )]);
    }

    #[Route('/api/learning_paths/{lpId}/reporting/recycle-bin/{archiveId}/restore', name: 'learning_path_tracking_recycle_restore', requirements: ['lpId' => '\d+', 'archiveId' => '\d+'], methods: ['POST'])]
    public function restore(int $lpId, int $archiveId, Request $request): JsonResponse
    {
        [, $course, $session, $users] = $this->requireContext($lpId, $request);
        $owner = $this->recycleBin->getArchiveUserId($archiveId, $lpId, (int) $course->getId(), $session?->getId());
        if (!\in_array($owner, $users, true)) {
            throw new AccessDeniedHttpException('The learner is outside the current learning path reporting context.');
        }
        $actor = $this->security->getUser();
        if (!$actor instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }
        $this->recycleBin->restore($archiveId, $lpId, $course, $session, (int) $actor->getId());

        return $this->json(['success' => true, 'message' => 'Learning path tracking restored']);
    }

    /**
     * @return array{CLp, Course, ?Session, list<int>}
     */
    private function requireContext(int $lpId, Request $request): array
    {
        $this->assertLearningPathTeacher($this->security);

        // A recycle-bin request carries its own course context. Do not depend on
        // mutable PHP session state, which other concurrent requests may change.
        if (null === $this->courseFromRequestHelper->getCourseReference($request)) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->courseFromRequestHelper->resolveCourse($request);
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('Course not found.');
        }

        $sessionId = $this->courseFromRequestHelper->getSessionId($request);
        $session = null;
        if (null !== $sessionId) {
            $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
            if (!$session instanceof Session) {
                throw new NotFoundHttpException('Session not found.');
            }
            $this->assertSessionBelongsToCourse($session, $course);
        }

        $groupId = $this->courseFromRequestHelper->getGroupId($request);
        $group = null === $groupId ? null : $this->findValidatedGroup($this->entityManager, $groupId, $course);
        $lp = $this->entityManager->getRepository(CLp::class)->find($lpId);
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }
        $this->getEditableResourceLink($lp, $course, $session, $group, $this->security);
        $users = $this->reportingProvider->getUsersForContext($lp, $course, $session, $request);

        return [$lp, $course, $session, array_map('intval', array_keys($users))];
    }
}
