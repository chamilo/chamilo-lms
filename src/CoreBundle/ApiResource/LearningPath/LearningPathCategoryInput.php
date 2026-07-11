<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\State\LearningPath\LearningPathCategoryMutationProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_path_categories/manage',
            output: false,
            status: Response::HTTP_NO_CONTENT,
            processor: LearningPathCategoryMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Post(
            uriTemplate: '/learning_path_categories/{id}/manage-action',
            requirements: ['id' => '\\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            processor: LearningPathCategoryMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Put(
            uriTemplate: '/learning_path_categories/{id}/manage',
            requirements: ['id' => '\\d+'],
            output: false,
            status: Response::HTTP_NO_CONTENT,
            processor: LearningPathCategoryMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
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

    #[Groups(['lp_category_input:write'])]
    public string $csrfToken = '';

    public function getId(): ?int
    {
        return $this->id;
    }
}
