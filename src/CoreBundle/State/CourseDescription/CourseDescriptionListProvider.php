<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseDescription;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CourseDescription\CourseDescriptionList;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<CourseDescriptionList>
 */
final readonly class CourseDescriptionListProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CCourseDescriptionRepository $courseDescriptionRepository,
        private Security $security,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseDescriptionList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $studentView = $this->isStudentView($request);
        $canManage = !$studentView && (
            $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        );

        $list = new CourseDescriptionList();
        $list->courseId = (int) $course->getId();
        $list->sessionId = null !== $session ? (int) $session->getId() : null;
        $list->canManage = $canManage;
        $list->studentView = $studentView;

        $descriptions = $this->courseDescriptionRepository->findAllInCourse($course, $session);

        foreach ($descriptions as $description) {
            if (!$description instanceof CCourseDescription || null === $description->getIid()) {
                continue;
            }

            $list->items[] = $this->normalizeDescription($description, $course, $session);
        }

        $list->totalItems = \count($list->items);

        return $list;
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

    /**
     * @return array<string, mixed>
     */
    private function normalizeDescription(
        CCourseDescription $description,
        Course $course,
        ?Session $session,
    ): array {
        $resourceNode = $description->getResourceNode();
        $contextLink = $description->getFirstResourceLinkFromCourseSession($course, $session);

        if (!$contextLink instanceof ResourceLink && null !== $session) {
            $contextLink = $description->getFirstResourceLinkFromCourseSession($course);
        }

        $sourceSession = $contextLink?->getSession();
        $language = $resourceNode?->getLanguage();

        return [
            'iid' => (int) $description->getIid(),
            'title' => (string) $description->getTitle(),
            'content' => (string) $description->getContent(),
            'descriptionType' => (int) $description->getDescriptionType(),
            'progress' => (int) $description->getProgress(),
            'resourceNodeId' => null !== $resourceNode?->getId() ? (int) $resourceNode->getId() : null,
            'sessionId' => null !== $sourceSession?->getId() ? (int) $sourceSession->getId() : null,
            'language' => null !== $language ? $language->getIsocode() : null,
            'isInheritedFromCourse' => null !== $session && null === $sourceSession,
        ];
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
