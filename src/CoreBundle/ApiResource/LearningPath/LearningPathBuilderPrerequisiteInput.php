<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\LearningPath\LearningPathBuilderMutationProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_paths/{lpId}/builder/prerequisites',
            requirements: ['lpId' => '\\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            name: 'update_learning_path_builder_prerequisites',
            processor: LearningPathBuilderMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_builder_prerequisite:read']],
    denormalizationContext: ['groups' => ['learning_path_builder_prerequisite:write']],
)]
final class LearningPathBuilderPrerequisiteInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder_prerequisite:read'])]
    public ?int $lpId = null;

    #[Groups(['learning_path_builder_prerequisite:write'])]
    public string $action = '';

    #[Groups(['learning_path_builder_prerequisite:write'])]
    public string $csrfToken = '';

    #[Groups(['learning_path_builder_prerequisite:read'])]
    public bool $saved = false;

    public function getLpId(): ?int
    {
        return $this->lpId;
    }
}
