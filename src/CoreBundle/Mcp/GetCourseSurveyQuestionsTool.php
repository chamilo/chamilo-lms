<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CoreBundle\Service\Survey\CourseSurveyContentService;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class GetCourseSurveyQuestionsTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseSurveyContentService $surveyContentService,
    ) {}

    /**
     * @return array{
     *     course_id: int,
     *     mode: string,
     *     survey: array{survey_id: int, title: string},
     *     questions: list<array<string, mixed>>
     * }
     */
    #[McpTool(
        name: 'get_course_survey_questions',
        description: 'Read the questions and proposed answers/options of a survey in a base course managed by the authenticated teacher. Locate the survey by surveyId or exact surveyTitle. Modes full/inventory/source project each question and answer HTML body for translatehtml workflows. Prefer mode=source + upsert_course_survey_question_description_language / upsert_course_survey_answer_description_language for iterative translation. The survey title itself is never modified by the edit tools.',
    )]
    public function getCourseSurveyQuestions(
        int $courseId,
        ?int $surveyId = null,
        ?string $surveyTitle = null,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $survey = $this->surveyContentService->resolveSurvey($course, $surveyId, $surveyTitle);

            $questions = [];
            foreach ($this->surveyContentService->listQuestions($survey) as $index => $question) {
                $questions[] = $this->surveyContentService->normalizeQuestion(
                    $question,
                    $index + 1,
                    $mode,
                    $sourceLanguage,
                    $course,
                );
            }

            return [
                'course_id' => $courseId,
                'mode' => $mode,
                'survey' => [
                    'survey_id' => (int) $survey->getIid(),
                    'title' => $survey->getTitle(),
                ],
                'questions' => $questions,
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The survey questions could not be retrieved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
