<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseLearningPathItemProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseLearningPathItem',
    operations: [
        new Post(
            uriTemplate: '/exercise/questions/{exerciseId}/learning-path-item',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Attach an exercise created from the LP flow as a learning path item',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'origin', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'lp_id', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'post_exercise_learning_path_item',
            processor: ExerciseLearningPathItemProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_learning_path_item:read']],
    denormalizationContext: ['groups' => ['exercise_learning_path_item:write']],
)]
final class ExerciseLearningPathItem
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_learning_path_item:read', 'exercise_learning_path_item:write'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_learning_path_item:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['exercise_learning_path_item:read'])]
    public ?int $lpItemId = null;

    #[Groups(['exercise_learning_path_item:read'])]
    public bool $success = false;

    #[Groups(['exercise_learning_path_item:read'])]
    public string $message = '';
}
