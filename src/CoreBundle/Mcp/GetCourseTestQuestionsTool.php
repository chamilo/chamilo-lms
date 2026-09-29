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

final readonly class GetCourseTestQuestionsTool
{
    public function __construct(
        private McpTestReadCourseContext $courseContext,
        private CourseTestReaderService $testReader,
    ) {}

    /**
     * @return array{
     *     course_id: int,
     *     mode: string,
     *     test: array{quiz_id: int, title: string},
     *     questions: list<array<string, mixed>>
     * }
     */
    #[McpTool(
        name: 'get_course_test_questions',
        description: 'Read the questions of a test (exercise) in a base course, including each question\'s type, total score and category. Modes full/inventory/source project the question description HTML for translatehtml workflows. Prefer mode=source + upsert_course_test_question_description_language for iterative translation. Locate the test by testId or exact testTitle. Available to the course\'s own teacher, and platform-wide to question managers and administrators. Use get_course_test_question_answers to read the proposed answers of a specific question.',
    )]
    public function getCourseTestQuestions(
        int $courseId,
        ?int $testId = null,
        ?string $testTitle = null,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $quiz = $this->testReader->resolveQuiz($course, $testId, $testTitle);

            $questions = [];
            foreach ($this->testReader->listQuestionLinks($quiz) as $position => $rel) {
                $questions[] = $this->testReader->normalizeQuestion(
                    $rel->getQuestion(),
                    $position + 1,
                    $mode,
                    $sourceLanguage,
                    $course,
                );
            }

            return [
                'course_id' => $courseId,
                'mode' => $mode,
                'test' => [
                    'quiz_id' => (int) $quiz->getIid(),
                    'title' => $quiz->getTitle(),
                ],
                'questions' => $questions,
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The test questions could not be retrieved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
