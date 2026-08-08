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

final readonly class EditCourseTestAnswerFeedbackTool
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
     *     question: array<string, mixed>,
     *     answer: array<string, mixed>
     * }
     */
    #[McpTool(
        name: 'edit_course_test_answer_feedback',
        description: 'Replace the HTML feedback comment shown to learners after they pick one proposed answer in a test question, in a base course managed by the authenticated teacher. Locate the test by testId or exact testTitle, the question by questionId, and the answer by answerId (from get_course_test_question_answers). Only the feedback is changed — the answer body, correctness and score stay as they are. Pass clean semantic HTML; it is sanitized like course documents. Pass an empty string to clear the feedback. Call get_course_test_question_answers first to inspect the current text.',
    )]
    public function editCourseTestAnswerFeedback(
        int $courseId,
        int $questionId,
        int $answerId,
        string $feedback,
        ?int $testId = null,
        ?string $testTitle = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $quiz = $this->testReader->resolveQuiz($course, $testId, $testTitle);
            $result = $this->testContentEditor->editAnswerFeedback(
                $quiz,
                $questionId,
                $answerId,
                $feedback,
            );

            return [
                'updated' => true,
                'course_id' => $courseId,
                'test' => [
                    'quiz_id' => (int) $quiz->getIid(),
                    'title' => $quiz->getTitle(),
                ],
                'question' => $result['question'],
                'answer' => $result['answer'],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The test answer feedback could not be edited because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
