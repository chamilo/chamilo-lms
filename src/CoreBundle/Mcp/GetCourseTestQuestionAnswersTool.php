<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Exercise\CourseTestReaderService;
use Chamilo\CoreBundle\Service\Mcp\McpTestReadCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class GetCourseTestQuestionAnswersTool
{
    public function __construct(
        private McpTestReadCourseContext $courseContext,
        private CourseTestReaderService $testReader,
    ) {}

    /**
     * @return array{
     *     course_id: int,
     *     test: array{quiz_id: int, title: string},
     *     question: array<string, mixed>,
     *     answers: list<array<string, mixed>>
     * }
     */
    #[McpTool(
        name: 'get_course_test_question_answers',
        description: 'Read the proposed answers of a single question in a test (exercise), including each answer\'s feedback and score. Locate the test by testId or exact testTitle, and the question by questionId (from get_course_test_questions). Available to the course\'s own teacher, and platform-wide to question managers and administrators.',
    )]
    public function getCourseTestQuestionAnswers(
        int $courseId,
        int $questionId,
        ?int $testId = null,
        ?string $testTitle = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $quiz = $this->testReader->resolveQuiz($course, $testId, $testTitle);
            $resolved = $this->testReader->resolveQuestionWithPosition($quiz, $questionId);

            $answers = array_map(
                fn ($answer) => $this->testReader->normalizeAnswer($answer),
                $this->testReader->listAnswers($resolved['question']),
            );

            return [
                'course_id' => $courseId,
                'test' => [
                    'quiz_id' => (int) $quiz->getIid(),
                    'title' => $quiz->getTitle(),
                ],
                'question' => $this->testReader->normalizeQuestion($resolved['question'], $resolved['position']),
                'answers' => $answers,
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The test question answers could not be retrieved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
