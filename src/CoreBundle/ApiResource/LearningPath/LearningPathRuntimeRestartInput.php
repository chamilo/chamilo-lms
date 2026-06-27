<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeRestartProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_paths/{lpId}/runtime/restart',
            requirements: ['lpId' => '\\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            name: 'restart_learning_path_runtime',
            processor: LearningPathRuntimeRestartProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_GROUP_STUDENT')",
        ),
    ],
    denormalizationContext: ['groups' => ['learning_path_runtime_restart:write']],
)]
final class LearningPathRuntimeRestartInput
{
    #[ApiProperty(identifier: true)]
    public ?int $lpId = null;

    #[Groups(['learning_path_runtime_restart:write'])]
    public string $csrfToken = '';

    public function getLpId(): ?int
    {
        return $this->lpId;
    }
}
