<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\LearningPath\McpCourseLearningPathCreator;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Server\RequestContext;
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
     * @param list<array{
     *     title: string,
     *     content: string,
     *     quiz?: array{
     *         title?: string,
     *         questions: list<array{
     *             title: string,
     *             answers: list<string>,
     *             correct_index: int,
     *             feedback?: string
     *         }>
     *     }
     * }> $pages
     *
     * @return array{created: true, learning_path: array<string, mixed>}
     */
    #[McpTool(
        name: 'create_course_learning_path',
        description: 'Create a learning path in a base course from pages supplied by the MCP client. The MCP client writes all content — each page\'s HTML and, optionally, a mini-test with its questions — Chamilo only persists what is provided and never generates or expands content itself. The learning path is a draft unless publish is true.',
    )]
    public function createCourseLearningPath(
        int $courseId,
        string $title,
        #[Schema(
            type: 'array',
            minItems: 1,
            maxItems: 10,
            items: [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string', 'description' => 'The page title, shown as the page <h1> and as the learning path item label.'],
                    'content' => ['type' => 'string', 'description' => 'The full page HTML content, written by the MCP client.'],
                    'quiz' => [
                        'type' => 'object',
                        'description' => 'Optional mini-test for this page. Omit entirely if this page should have no mini-test.',
                        'properties' => [
                            'title' => ['type' => 'string', 'description' => 'Defaults to "Mini-test N: <page title>" if omitted.'],
                            'questions' => [
                                'type' => 'array',
                                'minItems' => 1,
                                'maxItems' => 20,
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'title' => ['type' => 'string'],
                                        'answers' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'string'],
                                            'minItems' => 2,
                                            'maxItems' => 10,
                                        ],
                                        'correct_index' => ['type' => 'integer', 'description' => 'Zero-based index into answers of the correct option.'],
                                        'feedback' => ['type' => 'string', 'description' => 'Optional feedback shown after answering.'],
                                    ],
                                    'required' => ['title', 'answers', 'correct_index'],
                                ],
                            ],
                        ],
                        'required' => ['questions'],
                    ],
                ],
                'required' => ['title', 'content'],
            ],
        )]
        array $pages,
        ?string $language = null,
        bool $publish = false,
        ?RequestContext $context = null,
    ): array {
        try {
            $resolved = $this->courseContext->resolve($courseId);

            return [
                'created' => true,
                'learning_path' => $this->learningPathCreator->create(
                    $resolved['course'],
                    $resolved['user'],
                    $title,
                    $pages,
                    $language,
                    $publish,
                    $context?->getClientGateway(),
                ),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The learning path could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
