<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\LearningPath\LearningPathManagementProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_paths/{lpId}/manage',
            requirements: ['lpId' => '\\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            name: 'manage_learning_path',
            processor: LearningPathManagementProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_management:read']],
    denormalizationContext: ['groups' => ['learning_path_management:write']],
)]
final class LearningPathManagementInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_management:read'])]
    public ?int $lpId = null;

    #[Groups(['learning_path_management:write'])]
    public string $action = '';

    #[Groups(['learning_path_management:write'])]
    public ?bool $enabled = null;

    #[Groups(['learning_path_management:write'])]
    public string $csrfToken = '';

    public function getLpId(): ?int
    {
        return $this->lpId;
    }
}
