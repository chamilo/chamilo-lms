<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Tool\AbstractTool;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CourseBundle\Entity\CTool;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

/**
 * Mirrors the course-home "eye" toggle (ResourceController::changeVisibility()) so an MCP
 * caller can make a tool it just populated (a document, a test, a learning path...) actually
 * reachable by students, and inspect what is currently visible before doing so.
 *
 * Source of truth is ResourceLink.visibility, not CTool itself (see CTool::getVisibility()).
 */
final readonly class ManageCourseToolVisibilityTool
{
    /**
     * Structural rows, not real course-home tool cards. Excluded the same way
     * CToolExtension excludes them from the /api/c_tools collection.
     */
    private const array EXCLUDED_TOOL_TITLES = ['course_tool', 'course_homepage'];

    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private ToolChain $toolChain,
        private SettingsManager $settingsManager,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{scope: string, course_id: int, session_id: int|null, tools: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'list_course_tools',
        description: 'List the tools shown on the course homepage of a course managed by the authenticated teacher, including whether each is currently visible to students. Use sessionId only to inspect visibility inside a specific course session (falls back to the base-course setting where no session-specific override exists). Use the returned name with set_course_tool_visibility to show or hide a tool.',
    )]
    public function listCourseTools(int $courseId, int $sessionId = 0): array
    {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $session = $this->resolveSession($course, $sessionId);

            $tools = [];
            foreach ($this->fetchCanonicalCourseTools($course) as $courseTool) {
                $resolved = $this->resolveEffectiveLink($courseTool, $course, $session);
                $tools[] = $this->describeTool($courseTool, $resolved['link'], $resolved['source']);
            }

            return [
                'scope' => $session instanceof Session ? 'course_session' : 'base_course',
                'course_id' => $courseId,
                'session_id' => $session?->getId(),
                'tools' => $tools,
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course tools could not be listed because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{updated: bool, scope: string, course_id: int, session_id: int|null, tool: array<string, mixed>}
     */
    #[McpTool(
        name: 'set_course_tool_visibility',
        description: 'Show or hide one course-homepage tool for students, in a course managed by the authenticated teacher. Locate the tool with the exact name returned by list_course_tools (toolName) or its tool_id. Use sessionId only when the platform allows per-session tool visibility (session.allow_edit_tool_visibility_in_session is enabled); otherwise this always changes the base-course setting.',
    )]
    public function setCourseToolVisibility(
        int $courseId,
        bool $visible,
        ?string $toolName = null,
        ?int $toolId = null,
        int $sessionId = 0,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $session = $this->resolveSession($course, $sessionId);

            if ($session instanceof Session && !$this->isSettingEnabled(
                $this->settingsManager->getSetting('session.allow_edit_tool_visibility_in_session')
            )) {
                throw new InvalidArgumentException('Per-session tool visibility is disabled on this platform (session.allow_edit_tool_visibility_in_session). Omit sessionId to change the base-course visibility instead.');
            }

            $courseTool = $this->resolveCourseTool($course, $toolName, $toolId);
            $rawTitle = $courseTool->getTool()->getTitle();
            $normalizedName = $this->normalizeToolName($rawTitle);

            if ($this->isToolVisibilityLocked($course, $normalizedName)) {
                throw new AccessDeniedException('This tool\'s visibility is locked on this platform and cannot be changed (privacy.disable_change_user_visibility_for_public_courses).');
            }

            $resourceNode = $courseTool->getResourceNode();
            if (!$resourceNode instanceof ResourceNode) {
                throw new RuntimeException('The tool has no resource node and its visibility cannot be changed.');
            }

            $targetVisibility = $visible ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT;
            $link = $this->findContextLink($resourceNode, $course, $session);
            $updated = true;

            if (!$link instanceof ResourceLink) {
                // First time this course/session context is toggled: create the link, same as
                // ToolChain::addToolsInCourse() does at course creation for the base context.
                $courseTool->setParent($course);
                $courseTool->addCourseLink($course, $session, null, $targetVisibility);
                $link = $this->findContextLink($resourceNode, $course, $session);
                if (!$link instanceof ResourceLink) {
                    throw new RuntimeException('The tool visibility link could not be created.');
                }
                $this->entityManager->persist($link);
            } elseif ($targetVisibility === $link->getVisibility()) {
                // Already in the requested state: report it plainly instead of throwing, so an
                // agent asserting a desired end state can call this idempotently.
                $updated = false;
            } else {
                $link->setVisibility($targetVisibility);
                $this->entityManager->persist($link);
            }

            if ($updated) {
                $this->entityManager->flush();
            }

            return [
                'updated' => $updated,
                'scope' => $session instanceof Session ? 'course_session' : 'base_course',
                'course_id' => $courseId,
                'session_id' => $session?->getId(),
                'tool' => $this->describeTool($courseTool, $link, $session instanceof Session ? 'session' : 'base_course'),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The tool visibility could not be updated because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    private function resolveSession(Course $course, int $sessionId): ?Session
    {
        if ($sessionId < 0) {
            throw new InvalidArgumentException('The session ID cannot be negative.');
        }

        if (0 === $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session || !$session->getCourseSubscription($course) instanceof SessionRelCourse) {
            throw new InvalidArgumentException('Invalid course-session context: the session was not found or is not linked to this course.');
        }

        return $session;
    }

    /**
     * Base-course tool rows only (CTool.session is only ever populated by this migration path
     * with null; per-session state lives on ResourceLink, not on a separate CTool row), deduped
     * against legacy name aliases (e.g. the old "user" tool row when "member" also exists), the
     * same way CToolStateProvider dedupes them for the course-home page.
     *
     * @return list<CTool>
     */
    private function fetchCanonicalCourseTools(Course $course): array
    {
        /** @var list<CTool> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('ct')
            ->addSelect('t')
            ->from(CTool::class, 'ct')
            ->innerJoin('ct.tool', 't')
            ->andWhere('IDENTITY(ct.course) = :courseId')
            ->andWhere('ct.session IS NULL')
            ->andWhere('ct.title NOT IN (:excludedTitles)')
            ->orderBy('ct.position', 'ASC')
            ->setParameter('courseId', (int) $course->getId())
            ->setParameter('excludedTitles', self::EXCLUDED_TOOL_TITLES)
            ->getQuery()
            ->getResult()
        ;

        $canonicalRows = [];
        foreach ($rows as $row) {
            $stored = $this->rawToolName($row);
            $canonical = $this->normalizeToolName($stored);
            if ($stored === $canonical) {
                $canonicalRows[$canonical] = true;
            }
        }

        $result = [];
        $emitted = [];
        foreach ($rows as $row) {
            $stored = $this->rawToolName($row);
            $canonical = $this->normalizeToolName($stored);

            if ($stored !== $canonical && isset($canonicalRows[$canonical])) {
                continue;
            }
            if (isset($emitted[$canonical])) {
                continue;
            }

            $emitted[$canonical] = true;
            $result[] = $row;
        }

        return $result;
    }

    private function resolveCourseTool(Course $course, ?string $toolName, ?int $toolId): CTool
    {
        $toolId = null !== $toolId && $toolId > 0 ? $toolId : null;
        $toolName = null !== $toolName ? trim($toolName) : '';

        if (null === $toolId && '' === $toolName) {
            throw new InvalidArgumentException('Provide either toolName or toolId. Use list_course_tools to find them.');
        }

        $courseTools = $this->fetchCanonicalCourseTools($course);

        if (null !== $toolId) {
            foreach ($courseTools as $courseTool) {
                if ($toolId === $courseTool->getIid()) {
                    return $courseTool;
                }
            }

            throw new InvalidArgumentException('No course tool with this toolId was found in this course. Use list_course_tools to find it.');
        }

        $requestedName = $this->normalizeToolName($toolName);
        foreach ($courseTools as $courseTool) {
            if ($this->normalizeToolName($this->rawToolName($courseTool)) === $requestedName) {
                return $courseTool;
            }
        }

        throw new InvalidArgumentException(\sprintf('No course tool named "%s" was found in this course. Use list_course_tools to find the exact name.', $toolName));
    }

    /**
     * @return array{link: ?ResourceLink, source: ?string}
     */
    private function resolveEffectiveLink(CTool $courseTool, Course $course, ?Session $session): array
    {
        $resourceNode = $courseTool->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return ['link' => null, 'source' => null];
        }

        if ($session instanceof Session) {
            $sessionLink = $this->findContextLink($resourceNode, $course, $session);
            if ($sessionLink instanceof ResourceLink) {
                return ['link' => $sessionLink, 'source' => 'session'];
            }
        }

        $baseLink = $this->findContextLink($resourceNode, $course, null);

        return ['link' => $baseLink, 'source' => $baseLink instanceof ResourceLink ? 'base_course' : null];
    }

    /**
     * Exact-context lookup: same rule as ResourceController::findCourseToolResourceLink() —
     * matching course, matching session (including both null), and no group/userGroup/user
     * scoping (those belong to other resource types sharing the ResourceLink table).
     */
    private function findContextLink(ResourceNode $resourceNode, Course $course, ?Session $session): ?ResourceLink
    {
        $courseId = $course->getId();
        $sessionId = $session?->getId();

        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            if ($resourceLink->getCourse()?->getId() !== $courseId) {
                continue;
            }
            if ($resourceLink->getSession()?->getId() !== $sessionId) {
                continue;
            }
            if (null !== $resourceLink->getGroup() || null !== $resourceLink->getUserGroup() || null !== $resourceLink->getUser()) {
                continue;
            }

            return $resourceLink;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function describeTool(CTool $courseTool, ?ResourceLink $link, ?string $visibilitySource): array
    {
        $rawTitle = $this->rawToolName($courseTool);
        $normalizedName = $this->normalizeToolName($rawTitle);
        $toolModel = $this->resolveToolModel($rawTitle);

        $displayTitle = $toolModel instanceof AbstractTool ? trim($toolModel->getTitleToShow()) : '';
        if ('' === $displayTitle) {
            $displayTitle = $courseTool->getTitle();
        }

        $locked = $this->isToolVisibilityLocked($courseTool->getCourse(), $normalizedName);
        // No link at all is the backward-compatible default: CTool::getVisibility() treats it as visible.
        $visibility = $link?->getVisibility() ?? ResourceLink::VISIBILITY_PUBLISHED;

        return [
            'tool_id' => $courseTool->getIid(),
            'name' => $normalizedName,
            'title' => $displayTitle,
            'category' => $toolModel?->getCategory(),
            'position' => $courseTool->getPosition(),
            'resource_node_id' => $courseTool->getResourceNode()?->getId(),
            'visible' => !$locked && ResourceLink::VISIBILITY_PUBLISHED === $visibility,
            'visibility' => $visibility,
            'visibility_source' => $visibilitySource,
            'allow_change_visibility' => !$locked,
        ];
    }

    private function resolveToolModel(string $rawTitle): ?AbstractTool
    {
        try {
            return $this->toolChain->getToolFromName($rawTitle);
        } catch (Throwable) {
            // Legacy plugin tool rows without a registered handler: still listable, just
            // without a title-to-show / category to resolve from.
            return null;
        }
    }

    private function rawToolName(CTool $courseTool): string
    {
        return strtolower(trim($courseTool->getTool()->getTitle()));
    }

    private function normalizeToolName(string $toolName): string
    {
        return strtolower(trim($this->toolChain->normalizeCourseToolName($toolName)));
    }

    /**
     * Same rule as ResourceController::isUserToolVisibilityLocked(): the "member" tool cannot
     * be hidden in a public course when the platform forbids it.
     */
    private function isToolVisibilityLocked(Course $course, string $normalizedName): bool
    {
        if ('member' !== $normalizedName || !$course->isPublic()) {
            return false;
        }

        return $this->isSettingEnabled(
            $this->settingsManager->getSetting('privacy.disable_change_user_visibility_for_public_courses')
        );
    }

    private function isSettingEnabled(mixed $value): bool
    {
        return true === $value
            || 1 === $value
            || '1' === $value
            || 'true' === strtolower(trim((string) $value));
    }
}
