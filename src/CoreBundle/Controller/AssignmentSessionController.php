<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\SessionVisibilityHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Service\Assignment\AssignmentSessionDuplicator;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/assignments')]
final class AssignmentSessionController extends AbstractController
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly Security $security,
        private readonly StudentViewHelper $studentViewHelper,
        private readonly IsAllowedToEditHelper $editHelper,
        private readonly SessionVisibilityHelper $sessionVisibilityHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly CStudentPublicationRepository $repository,
        private readonly AssignmentSessionDuplicator $duplicator,
    ) {}

    #[Route('/base-course', name: 'chamilo_core_assignment_base_course_list', methods: ['GET'])]
    public function listBaseAssignments(): JsonResponse
    {
        [$course, $session, $user] = $this->requireManagementContext();
        $canEdit = $this->canDuplicateInSession($course, $session, $user);
        $assignments = $this->repository->createQueryBuilder('assignment')
            ->join('assignment.resourceNode', 'node')
            ->join('node.resourceLinks', 'link')
            ->where("assignment.filetype = 'folder'")
            ->andWhere('assignment.publicationParent IS NULL AND assignment.active IN (0, 1)')
            ->andWhere('link.course = :course AND link.session IS NULL AND link.deletedAt IS NULL')
            ->setParameter('course', (int) $course->getId())
            ->orderBy('assignment.title', 'ASC')
            ->distinct()
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($assignments as $assignment) {
            $supported = $this->duplicator->isDuplicableSource($assignment, $course);
            $copy = $supported ? $this->duplicator->findExisting($assignment, $course, $session) : null;
            // Return only the metadata necessary for the copy action, not base-course submissions.
            $items[] = [
                'id' => $assignment->getIid(),
                'title' => $assignment->getTitle(),
                'copied' => null !== $copy,
                'canDuplicate' => $canEdit && $supported && null === $copy,
            ];
        }

        return new JsonResponse(['items' => $items]);
    }

    #[Route('/{assignmentId}/duplicate-to-session', name: 'chamilo_core_assignment_duplicate_to_session', requirements: ['assignmentId' => '\d+'], methods: ['POST'])]
    public function duplicateToSession(int $assignmentId): JsonResponse
    {
        [$course, $session, $user] = $this->requireManagementContext();
        if (!$this->canDuplicateInSession($course, $session, $user)) {
            throw new AccessDeniedHttpException('You cannot duplicate assignments in this session.');
        }

        $assignment = $this->repository->find($assignmentId);
        if (!$assignment instanceof CStudentPublication
            || 'folder' !== $assignment->getFiletype()
            || null !== $assignment->getPublicationParent()
            || null === $this->duplicator->findExactCourseLink($assignment, $course, null)
        ) {
            throw $this->createNotFoundException('Base course assignment not found.');
        }

        try {
            $copy = $this->duplicator->getOrCreate($assignment, $course, $session);
        } catch (InvalidArgumentException $exception) {
            throw new UnprocessableEntityHttpException('This assignment cannot be duplicated automatically in the current session.', $exception);
        }

        return new JsonResponse(['id' => $copy->getIid()]);
    }

    /**
     * @return array{Course, Session, User}
     */
    private function requireManagementContext(): array
    {
        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $user = $this->security->getUser();
        if (!$course instanceof Course || !$session instanceof Session || !$user instanceof User
            || !$session->hasCourse($course) || 0 !== (int) $this->cidReqHelper->getGroupId()
            || $this->studentViewHelper->isActive()
        ) {
            throw new AccessDeniedHttpException('A course session management context is required.');
        }

        if (!$this->security->isGranted('ROLE_ADMIN')
            && !$course->hasUserAsTeacher($user)
            && !$session->hasCourseCoachInCourse($user, $course)
            && !$session->hasUserAsGeneralCoach($user)
        ) {
            throw new AccessDeniedHttpException('Only teachers of this course or coaches of this session can manage its assignments.');
        }

        if ($this->accessUrlHelper->isMultiple()) {
            $url = $this->accessUrlHelper->getCurrent();
            $sessionInUrl = false;
            foreach ($session->getUrls() as $relation) {
                if (null !== $url && $relation->getUrl()?->getId() === $url->getId()) {
                    $sessionInUrl = true;

                    break;
                }
            }
            if (null === $url || !$url->hasUser($user) || !$url->hasCourse($course) || !$sessionInUrl) {
                throw new AccessDeniedHttpException('This course session is outside the current portal.');
            }
        }

        return [$course, $session, $user];
    }

    private function canDuplicateInSession(Course $course, Session $session, User $user): bool
    {
        return \in_array($this->sessionVisibilityHelper->getSessionVisibility($session, $user), [Session::AVAILABLE, Session::VISIBLE], true)
            && !$this->editHelper->isCourseLockedInsideSessions($course)
            && $this->editHelper->check(coach: true, sessionCoach: true, course: $course, session: $session);
    }
}
