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
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
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
        $canManage = !$studentView && $this->canManage($course, $session);

        $list = new CourseDescriptionList();
        $list->courseId = (int) $course->getId();
        $list->sessionId = null !== $session ? (int) $session->getId() : null;
        $list->canManage = $canManage;
        $list->studentView = $studentView;
        $list->csrfToken = $canManage
            ? (string) $this->csrfTokenManager->getToken(CourseDescriptionItemProvider::CSRF_TOKEN_ID)
            : '';
        $list->types = $this->getTypes();
        $list->settings = $this->getSettings();

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
            'canEdit' => $this->canEditDescription($description, $course, $session),
            'canDelete' => $this->canDeleteDescription($description, $course, $session),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTypes(): array
    {
        return [
            ['value' => CCourseDescription::TYPE_DESCRIPTION, 'label' => 'Description', 'icon' => 'image-text'],
            ['value' => CCourseDescription::TYPE_OBJECTIVES, 'label' => 'Objectives', 'icon' => 'flag-checkered'],
            ['value' => CCourseDescription::TYPE_TOPICS, 'label' => 'Topics', 'icon' => 'table-of-contents'],
            ['value' => CCourseDescription::TYPE_METHODOLOGY, 'label' => 'Methodology', 'icon' => 'strategy'],
            ['value' => CCourseDescription::TYPE_COURSE_MATERIAL, 'label' => 'Course material', 'icon' => 'laptop'],
            ['value' => CCourseDescription::TYPE_RESOURCES, 'label' => 'Resources', 'icon' => 'human-male-board'],
            ['value' => CCourseDescription::TYPE_ASSESSMENT, 'label' => 'Assessment', 'icon' => 'order-bool-ascending-variant'],
            ['value' => CCourseDescription::TYPE_CUSTOM, 'label' => 'Other', 'icon' => 'magic-staff'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return [
            'searchEnabled' => $this->isSettingEnabled('search.search_enabled'),
            'saveTitlesAsHtml' => $this->isSettingEnabled('editor.save_titles_as_html'),
        ];
    }

    private function canEditDescription(CCourseDescription $description, Course $course, ?Session $session): bool
    {
        if (!$this->canManage($course, $session) || !$this->belongsToExactContext($description, $course, $session)) {
            return false;
        }

        $resourceNode = $description->getResourceNode();

        return null !== $resourceNode && $this->security->isGranted('EDIT', $resourceNode);
    }

    private function canDeleteDescription(CCourseDescription $description, Course $course, ?Session $session): bool
    {
        if (!$this->canManage($course, $session) || !$this->belongsToExactContext($description, $course, $session)) {
            return false;
        }

        $resourceNode = $description->getResourceNode();

        return null !== $resourceNode && $this->security->isGranted('DELETE', $resourceNode);
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

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
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
