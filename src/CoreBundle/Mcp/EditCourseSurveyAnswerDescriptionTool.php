<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CoreBundle\Service\Survey\CourseSurveyContentService;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class EditCourseSurveyAnswerDescriptionTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseSurveyContentService $surveyContentService,
    ) {}

    /**
     * @return array{
     *     updated: true,
     *     course_id: int,
     *     survey: array{survey_id: int, title: string},
     *     question: array<string, mixed>,
     *     answer: array<string, mixed>
     * }
     */
    #[McpTool(
        name: 'edit_course_survey_answer_description',
        description: 'Replace the HTML text of one survey answer/option in a base course managed by the authenticated teacher. Locate the survey by surveyId or exact surveyTitle, the question by questionId, and the answer by answerId (from get_course_survey_questions). Only the option HTML body is changed — sort order and numeric value stay as they are. The parent survey title is never modified. Pass clean semantic HTML; it is sanitized like course documents.',
    )]
    public function editCourseSurveyAnswerDescription(
        int $courseId,
        int $questionId,
        int $answerId,
        string $description,
        ?int $surveyId = null,
        ?string $surveyTitle = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $survey = $this->surveyContentService->resolveSurvey($course, $surveyId, $surveyTitle);
            $result = $this->surveyContentService->editAnswerDescription(
                $course,
                $survey,
                $questionId,
                $answerId,
                $description,
            );

            return [
                'updated' => true,
                'course_id' => $courseId,
                'survey' => [
                    'survey_id' => (int) $survey->getIid(),
                    'title' => $survey->getTitle(),
                ],
                'question' => $result['question'],
                'answer' => $result['answer'],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The survey answer description could not be edited because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
