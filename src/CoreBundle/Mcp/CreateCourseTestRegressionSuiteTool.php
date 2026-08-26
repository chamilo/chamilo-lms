<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Exercise\ExerciseRegressionFixtureCreator;
use Chamilo\CoreBundle\Service\Mcp\McpCourseAiFeatureManager;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Server\RequestContext;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class CreateCourseTestRegressionSuiteTool
{
    public function __construct(
        private McpTeacherCourseContext $teacherCourseContext,
        private ExerciseRegressionFixtureCreator $fixtureCreator,
        private McpCourseAiFeatureManager $courseAiFeatureManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'create_course_test_regression_suite',
        description: 'Create a complete Chamilo Exercises regression suite in a course managed by the authenticated teacher. Without topic, it keeps the deterministic QA fixtures. With topic, Chamilo uses its configured AI exercise generator to create realistic topic-related visible content while preserving the exact 30 current question-type structures and deterministic regression assets. Hotspot delineation remains in a separate adaptive test. In topic mode the MCP tool enables the course exercise_generator feature when platform policy allows it, matching Chamilo\'s existing AI test-creation behavior. All modes require the existing OnlyOffice and quiz-scenario prerequisites so no supported type is silently skipped.',
    )]
    public function createCourseTestRegressionSuite(
        int $courseId,
        string $titlePrefix = 'Exercise question type regression',
        bool $publish = false,
        ?string $topic = null,
        string $language = 'en',
        ?string $aiProvider = null,
        ?RequestContext $context = null,
    ): array {
        try {
            $resolved = $this->teacherCourseContext->resolve($courseId);
            $client = $context?->getClientGateway();

            $client?->progress(0.0, 1.0, 'Validating all current exercise question-type prerequisites...');

            $courseFeaturesEnabled = [];
            if (null !== $topic && '' !== trim($topic)) {
                $courseFeaturesEnabled = $this->courseAiFeatureManager->ensureEnabled(
                    $resolved['course'],
                    $resolved['user'],
                    'exercise_generator',
                    'create_course_test_regression_suite',
                );
                $client?->progress(0.2, 1.0, 'Generating and validating topic-aware exercise content...');
            }

            $suite = $this->fixtureCreator->create(
                $resolved['course'],
                $resolved['user'],
                $titlePrefix,
                $publish,
                $topic,
                $language,
                $aiProvider,
            );

            $client?->progress(1.0, 1.0, 'Exercise question-type regression suite created.');

            return [
                'created' => true,
                'course_id' => $courseId,
                'course_features_enabled' => $courseFeaturesEnabled,
                'suite' => $suite,
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|HttpExceptionInterface|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The exercise question-type regression suite could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
