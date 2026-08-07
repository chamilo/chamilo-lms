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

final readonly class GetCourseSurveysTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseSurveyContentService $surveyContentService,
    ) {}

    /**
     * @return array{course_id: int, surveys: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'get_course_surveys',
        description: 'List surveys in a base course managed by the authenticated teacher, including each survey\'s title (unchanged English label), HTML description/intro, language and question count. Optionally filter to a single survey by surveyId or exact surveyTitle. Use get_course_survey_questions next, then the edit_course_survey_*_description tools to update HTML bodies without renaming titles.',
    )]
    public function getCourseSurveys(
        int $courseId,
        ?int $surveyId = null,
        ?string $surveyTitle = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];

            $hasFilter = (null !== $surveyId && $surveyId > 0)
                || (null !== $surveyTitle && '' !== trim($surveyTitle));
            $surveys = $hasFilter
                ? [$this->surveyContentService->resolveSurvey($course, $surveyId, $surveyTitle)]
                : $this->surveyContentService->listSurveys($course);

            return [
                'course_id' => $courseId,
                'surveys' => array_map(
                    fn ($survey) => $this->surveyContentService->normalizeSurvey($survey, $course),
                    $surveys,
                ),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course surveys could not be retrieved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
