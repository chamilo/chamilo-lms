<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\LearningPath\LearningPathManagementProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_paths/{lpId}/manage',
            requirements: ['lpId' => '\d+'],
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            output: false,
            read: false,
            name: 'manage_learning_path',
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
            processor: LearningPathManagementProcessor::class,
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

    public function getLpId(): ?int
    {
        return $this->lpId;
    }
}
