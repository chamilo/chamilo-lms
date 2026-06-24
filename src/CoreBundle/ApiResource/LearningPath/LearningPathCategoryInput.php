<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\State\LearningPath\LearningPathCategoryMutationProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_path_categories/manage',
            processor: LearningPathCategoryMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Put(
            uriTemplate: '/learning_path_categories/{categoryId}/manage',
            processor: LearningPathCategoryMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Delete(
            uriTemplate: '/learning_path_categories/{categoryId}/manage',
            processor: LearningPathCategoryMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            deserialize: false,
        ),
    ],
    normalizationContext: ['groups' => ['lp_category_input:read']],
    denormalizationContext: ['groups' => ['lp_category_input:write']],
)]
final class LearningPathCategoryInput
{
    #[Groups(['lp_category_input:read'])]
    public ?int $id = null;

    #[Groups(['lp_category_input:read', 'lp_category_input:write'])]
    public string $title = '';

    #[Groups(['lp_category_input:read', 'lp_category_input:write'])]
    public string $csrfToken = '';
}
