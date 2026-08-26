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
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseDescriptionHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProviderInterface<CourseDescriptionList>
 */
final readonly class CourseDescriptionListProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CCourseDescriptionRepository $courseDescriptionRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private StudentViewHelper $studentViewHelper,
        private CourseDescriptionHelper $courseDescriptionHelper,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseDescriptionList
    {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->courseDescriptionHelper->assertToolEnabled($course);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->courseDescriptionHelper->assertSessionBelongsToCourse($session, $course);

        if (!$this->courseDescriptionHelper->canRead($course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to view course descriptions in this context.');
        }

        // The helper already denies the student view, so the flag is only reported, not applied.
        $studentView = $this->studentViewHelper->isActive();
        $canManage = $this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session);

        $list = new CourseDescriptionList();
        $list->courseId = (int) $course->getId();
        $list->sessionId = null !== $session ? (int) $session->getId() : null;
        $list->canManage = $canManage;
        $list->studentView = $studentView;
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
            'title' => $this->sanitizeHtmlTitle((string) $description->getTitle()),
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

    private function sanitizeHtmlTitle(string $title): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($title, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
        if (!$this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)
            || !$this->belongsToExactContext($description, $course, $session)) {
            return false;
        }

        $resourceNode = $description->getResourceNode();

        return null !== $resourceNode && $this->security->isGranted('EDIT', $resourceNode);
    }

    private function canDeleteDescription(CCourseDescription $description, Course $course, ?Session $session): bool
    {
        if (!$this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)
            || !$this->belongsToExactContext($description, $course, $session)) {
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

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
