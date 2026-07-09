<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseDescription;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseDescription\CourseDescriptionItem;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<CourseDescriptionItem, void>
 */
final readonly class CourseDescriptionDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CCourseDescriptionRepository $courseDescriptionRepository,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);

        if ($this->isStudentView($request) || !$this->canManage($course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to delete course descriptions in this context.');
        }

        $this->validateCsrfToken($this->getSubmittedCsrfToken($request));

        $descriptionId = isset($uriVariables['iid']) ? (int) $uriVariables['iid'] : 0;
        if ($descriptionId <= 0) {
            throw new BadRequestHttpException('A valid course description id is required.');
        }

        $description = $this->courseDescriptionRepository->find($descriptionId);
        if (!$description instanceof CCourseDescription) {
            throw new NotFoundHttpException('The requested course description was not found.');
        }

        if (!$this->belongsToExactContext($description, $course, $session)) {
            throw new AccessDeniedHttpException('The requested course description does not belong to the current course context.');
        }

        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('DELETE', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to delete this course description.');
        }

        $this->courseDescriptionRepository->delete($description);
    }

    private function canManage(Course $course, ?Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (null === $session) {
            return $course->hasUserAsTeacher($user);
        }

        return $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function belongsToExactContext(CCourseDescription $description, Course $course, ?Session $session): bool
    {
        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();

            if ($sameCourse && $sameSession) {
                return true;
            }
        }

        return false;
    }

    private function getSubmittedCsrfToken(Request $request): string
    {
        $content = trim($request->getContent());
        if ('' === $content) {
            return '';
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        if (!\is_array($payload)) {
            return '';
        }

        $token = $payload['csrfToken'] ?? '';

        return \is_string($token) ? $token : '';
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(CourseDescriptionItemProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    private function isStudentView(Request $request): bool
    {
        if ($request->query->has('isStudentView')) {
            return $request->query->getBoolean('isStudentView');
        }

        if (!$request->hasSession()) {
            return false;
        }

        return 'studentview' === $request->getSession()->get('studentview');
    }
}
