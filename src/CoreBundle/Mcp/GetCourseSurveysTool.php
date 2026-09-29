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

final readonly class GetCourseSurveysTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseSurveyContentService $surveyContentService,
    ) {}

    /**
     * @return array{course_id: int, mode: string, surveys: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'get_course_surveys',
        description: 'List surveys in a base course managed by the authenticated teacher, including each survey\'s title (unchanged English label), language and question count. Modes for the HTML intro: full (default), inventory, source — same translatehtml projection as course descriptions/documents. Optionally filter to a single survey by surveyId or exact surveyTitle. Prefer mode=source + upsert_course_survey_description_language for iterative translation of intros. Use get_course_survey_questions next for question/option bodies.',
    )]
    public function getCourseSurveys(
        int $courseId,
        ?int $surveyId = null,
        ?string $surveyTitle = null,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
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
                'mode' => $mode,
                'surveys' => array_map(
                    fn ($survey) => $this->surveyContentService->normalizeSurvey($survey, $course, $mode, $sourceLanguage),
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
