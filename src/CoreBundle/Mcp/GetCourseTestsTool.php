<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Exercise\CourseTestReaderService;
use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
use Chamilo\CoreBundle\Service\Mcp\McpTestReadCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class GetCourseTestsTool
{
    public function __construct(
        private McpTestReadCourseContext $courseContext,
        private CourseTestReaderService $testReader,
    ) {}

    /**
     * @return array{course_id: int, mode: string, tests: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'get_course_tests',
        description: 'Read the title and configuration settings of tests (exercises) in a base course. Returns every test in the course, or a single one when testId or testTitle is provided. Modes full/inventory/source project the test description HTML for translatehtml workflows. Prefer mode=source + upsert_course_test_description_language when translating intros. Available to the course\'s own teacher, and platform-wide to question managers and administrators.',
    )]
    public function getCourseTests(
        int $courseId,
        ?int $testId = null,
        ?string $testTitle = null,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];

            $hasFilter = (null !== $testId && $testId > 0) || null !== $testTitle && '' !== trim($testTitle);
            $quizzes = $hasFilter
                ? [$this->testReader->resolveQuiz($course, $testId, $testTitle)]
                : $this->testReader->listQuizzes($course);

            return [
                'course_id' => $courseId,
                'mode' => $mode,
                'tests' => array_map(
                    fn ($quiz) => $this->testReader->normalizeTest($quiz, $course, $mode, $sourceLanguage),
                    $quizzes,
                ),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course tests could not be retrieved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
