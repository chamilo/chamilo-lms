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

final readonly class UpsertCourseTestAnswerDescriptionLanguageTool
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
        name: 'upsert_course_test_answer_description_language',
        description: 'Add or replace ONE language variant of an exercise answer HTML body without rewriting the full multi-language body. Locate the test by testId or testTitle, the question by questionId, and the answer by answerId. Call get_course_test_question_answers with mode=source first. Pass language + INNER HTML only. Correctness and score are never modified; use upsert_course_test_answer_feedback_language for the feedback comment. mode is upsert (default), create_only or replace_only. Prefer over edit_course_test_answer_description for translation.',
    )]
    public function upsertCourseTestAnswerDescriptionLanguage(
        int $courseId,
        int $questionId,
        int $answerId,
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

            return $this->testContentEditor->upsertAnswerDescriptionLanguage(
                $course,
                $quiz,
                $questionId,
                $answerId,
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
            throw new ToolCallException('The test answer language variant could not be saved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
