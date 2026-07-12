<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseProgress;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressThematicActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseProgressThematicAction',
    operations: [
        new Post(
            uriTemplate: '/course-progress/thematic/actions/copy',
            openapi: new Operation(
                summary: 'Copy a course progress thematic',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_course_progress_thematic_copy',
            processor: CourseProgressThematicActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/course-progress/thematic/actions/move',
            openapi: new Operation(
                summary: 'Move a course progress thematic',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_course_progress_thematic_move',
            processor: CourseProgressThematicActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/course-progress/thematic/actions/bulk-delete',
            openapi: new Operation(
                summary: 'Delete course progress thematics from the current context',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_course_progress_thematic_bulk_delete',
            processor: CourseProgressThematicActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_progress_thematic_action:read']],
    denormalizationContext: ['groups' => ['course_progress_thematic_action:write']],
)]
final class CourseProgressThematicAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_progress_thematic_action:read'])]
    public string $id = 'course_progress_thematic_action';

    #[Groups(['course_progress_thematic_action:read', 'course_progress_thematic_action:write'])]
    public int $thematicId = 0;

    /**
     * @var int[]
     */
    #[Groups(['course_progress_thematic_action:read', 'course_progress_thematic_action:write'])]
    public array $thematicIds = [];

    #[Groups(['course_progress_thematic_action:read', 'course_progress_thematic_action:write'])]
    public string $direction = '';

    #[Groups(['course_progress_thematic_action:write'])]
    public string $csrfToken = '';

    #[Groups(['course_progress_thematic_action:read'])]
    public ?int $copiedThematicId = null;

    /**
     * @var int[]
     */
    #[Groups(['course_progress_thematic_action:read'])]
    public array $affectedThematicIds = [];

    #[Groups(['course_progress_thematic_action:read'])]
    public float $totalAverage = 0.0;

    public function getId(): string
    {
        return $this->id;
    }
}
