<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CoreBundle\Service\Survey\TrainingSatisfactionSurveyCreator;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class CreateTrainingSatisfactionSurveyTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private TrainingSatisfactionSurveyCreator $surveyCreator,
    ) {}

    /**
     * @return array{created: true, survey: array<string, mixed>}
     */
    #[McpTool(
        name: 'create_training_satisfaction_survey',
        description: 'Create a seven-question training satisfaction survey in a base course managed by the authenticated teacher. The survey is a draft unless publish is true.',
    )]
    public function createTrainingSatisfactionSurvey(
        int $courseId,
        string $title,
        ?string $language = null,
        ?string $provider = null,
        bool $publish = false,
        bool $anonymous = true,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return [
                'created' => true,
                'survey' => $this->surveyCreator->create(
                    $context['course'],
                    $context['user'],
                    $title,
                    $language,
                    $provider,
                    $publish,
                    $anonymous,
                ),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException(
                'The satisfaction survey could not be created because of an unexpected server error. Check the Chamilo log for technical details.',
                0,
                $throwable,
            );
        }
    }
}
