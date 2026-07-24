<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\LearningPath\McpCourseLearningPathCreator;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class CreateCourseLearningPathTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private McpCourseLearningPathCreator $learningPathCreator,
    ) {}

    /**
     * @return array{created: true, learning_path: array<string, mixed>}
     */
    #[McpTool(
        name: 'create_course_learning_path',
        description: 'Create an AI-assisted learning path in a base course with a requested number of HTML pages and a single-answer mini-test after every page. The learning path is a draft unless publish is true.',
    )]
    public function createCourseLearningPath(
        int $courseId,
        string $topic,
        int $pageCount,
        int $wordsPerPage,
        int $questionsPerQuiz = 2,
        ?string $provider = null,
        bool $publish = false,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);

            return [
                'created' => true,
                'learning_path' => $this->learningPathCreator->create(
                    $context['course'],
                    $context['user'],
                    $topic,
                    $pageCount,
                    $wordsPerPage,
                    $questionsPerQuiz,
                    $provider,
                    $publish,
                ),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException(
                'The learning path could not be created because of an unexpected server error. Check the Chamilo log for technical details.',
                0,
                $throwable,
            );
        }
    }
}
