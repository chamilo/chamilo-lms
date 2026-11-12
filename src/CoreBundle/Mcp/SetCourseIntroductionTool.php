<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Helpers\CToolIntroHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class SetCourseIntroductionTool
{
    private const string HOMEPAGE_TOOL = 'course_homepage';
    private const int MAX_CONTENT_LENGTH = 2_000_000;

    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CToolIntroHelper $toolIntroHelper,
        private CourseDocumentContentService $documentContentService,
        private EntityManagerInterface $entityManager,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @return array{updated: true, created: bool, changed: bool, scope: string, course_id: int, session_id: int|null, intro_id: int, content_length: int}
     */
    #[McpTool(
        name: 'set_course_introduction',
        description: 'Set the introduction section shown on the homepage of a course managed by the authenticated teacher. The whole introduction is replaced by the HTML content supplied (a new introduction is created when there is none); pass an empty string to clear it. Without sessionId the base-course introduction is set. With sessionId (a session that includes the course) the introduction is set for that session only: it supersedes the base-course introduction inside the session and leaves the base-course one untouched, and an empty string then shows no introduction in that session. The HTML is sanitized with the same rules as course documents, so write it with the style conventions of create_course_document. Read the current introduction first if it must be kept in part: this tool does not merge.',
    )]
    public function setCourseIntroduction(int $courseId, string $content, int $sessionId = 0): array
    {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $session = $this->resolveSession($course, $sessionId);
            if ($session instanceof Session
                && !$this->isAllowedToEditHelper->check(checkStudentView: false, course: $course, session: $session)
            ) {
                throw new AccessDeniedException('You are not allowed to edit this course inside this session.');
            }

            if (mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
                throw new InvalidArgumentException('The course introduction is too long.');
            }
            $html = '' === trim($content) ? '' : $this->documentContentService->sanitizeHtml($content);
            if ('' !== trim($content) && '' === trim($html)) {
                throw new InvalidArgumentException('The course introduction is empty after sanitization.');
            }

            $courseTool = $this->toolIntroHelper->getOrCreateCourseTool($course, self::HOMEPAGE_TOOL, $session);
            if (null === $courseTool) {
                throw new RuntimeException('The course homepage tool is not available on this platform.');
            }

            $intro = $this->entityManager->getRepository(CToolIntro::class)
                ->findOneBy(['courseTool' => $courseTool], ['iid' => 'DESC'])
            ;
            $created = !$intro instanceof CToolIntro;
            $changed = $created || $html !== $intro->getIntroText();

            if ($created) {
                $intro = (new CToolIntro())
                    ->setCourseTool($courseTool)
                    ->setIntroText($html)
                    ->setParent($course)
                ;
                $intro->addCourseLink($course, $session);
            } else {
                $intro->setIntroText($html);
            }

            $this->entityManager->persist($intro);
            $this->entityManager->flush();

            return [
                'updated' => true,
                'created' => $created,
                'changed' => $changed,
                'scope' => $session instanceof Session ? 'course_session' : 'base_course',
                'course_id' => (int) $course->getId(),
                'session_id' => $session?->getId(),
                'intro_id' => (int) $intro->getIid(),
                'content_length' => mb_strlen($html),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course introduction could not be saved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
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
}
