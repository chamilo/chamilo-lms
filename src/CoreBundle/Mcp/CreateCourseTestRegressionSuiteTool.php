<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Exercise\ExerciseRegressionFixtureCreator;
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
    ) {}

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'create_course_test_regression_suite',
        description: 'Create a deterministic Chamilo Exercises regression suite in a course managed by the authenticated teacher. It covers every question type exposed by the current Vue question editor. Because hotspot delineation requires immediate/adaptive feedback and is incompatible with the standard question types, the suite creates two tests: one standard test with all compatible types and one adaptive test for hotspot delineation. The operation requires the OnlyOffice plugin and enable_quiz_scenario setting to be enabled so no supported type is silently skipped.',
    )]
    public function createCourseTestRegressionSuite(
        int $courseId,
        string $titlePrefix = 'Exercise question type regression',
        bool $publish = false,
        ?RequestContext $context = null,
    ): array {
        try {
            $resolved = $this->teacherCourseContext->resolve($courseId);
            $client = $context?->getClientGateway();

            $client?->progress(0.0, 1.0, 'Validating all current exercise question-type prerequisites...');

            $suite = $this->fixtureCreator->create(
                $resolved['course'],
                $resolved['user'],
                $titlePrefix,
                $publish,
            );

            $client?->progress(1.0, 1.0, 'Exercise question-type regression suite created.');

            return [
                'created' => true,
                'course_id' => $courseId,
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
