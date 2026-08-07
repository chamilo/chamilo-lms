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

final readonly class EditCourseSurveyDescriptionTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseSurveyContentService $surveyContentService,
    ) {}

    /**
     * @return array{updated: true, course_id: int, survey: array<string, mixed>}
     */
    #[McpTool(
        name: 'edit_course_survey_description',
        description: 'Replace the HTML description/intro of a survey in a base course managed by the authenticated teacher. Locate the survey by surveyId or exact surveyTitle. Only the intro HTML is changed — the survey title stays as-is (English titles are preserved). Empty description is allowed to clear it. Pass clean semantic HTML; it is sanitized like course documents.',
    )]
    public function editCourseSurveyDescription(
        int $courseId,
        string $description,
        ?int $surveyId = null,
        ?string $surveyTitle = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $survey = $this->surveyContentService->resolveSurvey($course, $surveyId, $surveyTitle);
            $result = $this->surveyContentService->editSurveyDescription($course, $survey, $description);

            return [
                'updated' => true,
                'course_id' => $courseId,
                'survey' => $result['survey'],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The survey description could not be edited because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
