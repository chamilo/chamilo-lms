<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CoreBundle\Service\Survey\TrainingSatisfactionSurveyCreator;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Server\RequestContext;
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
        ?RequestContext $context = null,
    ): array {
        try {
            $context?->getClientGateway()?->progress(0.05, 1.0, 'Preparing the training satisfaction survey...');
            $resolved = $this->courseContext->resolve($courseId);
            $survey = $this->surveyCreator->create(
                $resolved['course'],
                $resolved['user'],
                $title,
                $language,
                $provider,
                $publish,
                $anonymous,
            );
            $context?->getClientGateway()?->progress(1.0, 1.0, 'Training satisfaction survey created and verified.');

            return [
                'created' => true,
                'survey' => $survey,
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The satisfaction survey could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
