<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Exercise\CourseTestContentEditorService;
use Chamilo\CoreBundle\Service\Exercise\CourseTestReaderService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class EditCourseTestQuestionDescriptionTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseTestReaderService $testReader,
        private CourseTestContentEditorService $testContentEditor,
    ) {}

    /**
     * @return array{
     *     updated: true,
     *     course_id: int,
     *     test: array{quiz_id: int, title: string},
     *     question: array<string, mixed>
     * }
     */
    #[McpTool(
        name: 'edit_course_test_question_description',
        description: 'Replace the HTML description of one exercise question in a base course managed by the authenticated teacher. Locate the test by testId or exact testTitle, and the question by questionId (from get_course_test_questions). Only the question description HTML is changed — the short question title text, type, score and answers stay as they are (titles remain in English). Pass clean semantic HTML; it is sanitized like course documents. Empty description is allowed to clear it. Call get_course_test_questions first to inspect the current description.',
    )]
    public function editCourseTestQuestionDescription(
        int $courseId,
        int $questionId,
        string $description,
        ?int $testId = null,
        ?string $testTitle = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $quiz = $this->testReader->resolveQuiz($course, $testId, $testTitle);
            $result = $this->testContentEditor->editQuestionDescription(
                $quiz,
                $questionId,
                $description,
            );

            return [
                'updated' => true,
                'course_id' => $courseId,
                'test' => [
                    'quiz_id' => (int) $quiz->getIid(),
                    'title' => $quiz->getTitle(),
                ],
                'question' => $result['question'],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The test question description could not be edited because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
