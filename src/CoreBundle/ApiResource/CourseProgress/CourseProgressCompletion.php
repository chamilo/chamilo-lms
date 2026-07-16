<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseProgress;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressCompletionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseProgressCompletion',
    operations: [
        new Post(
            uriTemplate: '/course-progress/completion',
            openapi: new Operation(
                summary: 'Set the last completed thematic advance',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_course_progress_completion',
            processor: CourseProgressCompletionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_progress_completion:read']],
    denormalizationContext: ['groups' => ['course_progress_completion:write']],
)]
final class CourseProgressCompletion
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_progress_completion:read'])]
    public string $id = 'course_progress_completion';

    #[Groups(['course_progress_completion:read', 'course_progress_completion:write'])]
    public int $advanceId = 0;

    #[Groups(['course_progress_completion:write'])]
    public string $csrfToken = '';

    /**
     * @var int[]
     */
    #[Groups(['course_progress_completion:read'])]
    public array $doneAdvanceIds = [];

    #[Groups(['course_progress_completion:read'])]
    public float $totalAverage = 0.0;

    public function getId(): string
    {
        return $this->id;
    }
}
