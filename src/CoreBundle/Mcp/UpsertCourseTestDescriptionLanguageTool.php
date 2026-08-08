<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Exercise\CourseTestContentEditorService;
use Chamilo\CoreBundle\Service\Exercise\CourseTestReaderService;
use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class UpsertCourseTestDescriptionLanguageTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseTestReaderService $testReader,
        private CourseTestContentEditorService $testContentEditor,
    ) {}

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    #[McpTool(
        name: 'upsert_course_test_description_language',
        description: 'Add or replace ONE language variant of a test (exercise) description HTML without rewriting the full multi-language body. Locate the test by testId or exact testTitle. Call get_course_tests with mode=source first. Pass language + INNER HTML only. mode is upsert (default), create_only or replace_only. Optional ifMatchSha256 / sourceLanguage. Prefer over update_course_test_settings description replacement for translation.',
    )]
    public function upsertCourseTestDescriptionLanguage(
        int $courseId,
        string $language,
        string $content,
        ?int $testId = null,
        ?string $testTitle = null,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $quiz = $this->testReader->resolveQuiz($course, $testId, $testTitle);

            return $this->testContentEditor->upsertTestDescriptionLanguage(
                $course,
                $quiz,
                $language,
                $content,
                $mode,
                $sourceLanguage,
                $ifMatchSha256,
            );
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The test description language variant could not be saved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
