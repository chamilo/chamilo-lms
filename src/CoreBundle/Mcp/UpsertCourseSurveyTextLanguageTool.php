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

final readonly class UpsertCourseSurveyTextLanguageTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CourseSurveyContentService $surveyContentService,
    ) {}

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    #[McpTool(
        name: 'upsert_course_survey_text_language',
        description: 'Add or replace ONE language variant of a survey\'s "subtitle" or "thanks" (post-submission confirmation message) HTML text, without rewriting the full multi-language body. Locate the survey by surveyId or exact surveyTitle. Call get_course_surveys with mode=source first — its "subtitle_*" and "thanks_*" keys mirror the intro/description projection. Pass field ("subtitle" or "thanks"), language, and INNER HTML only. mode is upsert (default), create_only or replace_only. Optional ifMatchSha256 / sourceLanguage. The survey title is never translatable here — it doubles as the surveyId/surveyTitle lookup key, so it stays English-facing; use upsert_course_survey_description_language for the intro instead.',
    )]
    public function upsertCourseSurveyTextLanguage(
        int $courseId,
        string $field,
        string $language,
        string $content,
        ?int $surveyId = null,
        ?string $surveyTitle = null,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $survey = $this->surveyContentService->resolveSurvey($course, $surveyId, $surveyTitle);

            return $this->surveyContentService->upsertSurveyTextLanguage(
                $course,
                $survey,
                $field,
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
            throw new ToolCallException('The survey text language variant could not be saved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
