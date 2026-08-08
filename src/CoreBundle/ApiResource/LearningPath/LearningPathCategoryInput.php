<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\LearningPath\LearningPathCategoryMutationProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_path_categories/manage',
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            output: false,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
            processor: LearningPathCategoryMutationProcessor::class,
        ),
        new Post(
            uriTemplate: '/learning_path_categories/{id}/manage-action',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            output: false,
            read: false,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
            processor: LearningPathCategoryMutationProcessor::class,
        ),
        new Patch(
            uriTemplate: '/learning_path_categories/{id}/manage',
            requirements: ['id' => '\d+'],
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            output: false,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
            processor: LearningPathCategoryMutationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['lp_category_input:read']],
    denormalizationContext: ['groups' => ['lp_category_input:write']],
)]
final class LearningPathCategoryInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['lp_category_input:read'])]
    public ?int $id = null;

    #[Groups(['lp_category_input:write'])]
    public string $title = '';

    #[Groups(['lp_category_input:write'])]
    public string $action = '';

    public function getId(): ?int
    {
        return $this->id;
    }
}
