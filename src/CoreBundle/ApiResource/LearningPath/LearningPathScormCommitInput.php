<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\LearningPath\LearningPathScormCommitProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_paths/{lpId}/runtime/scorm/commit',
            requirements: ['lpId' => '\\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            name: 'commit_learning_path_scorm_runtime',
            processor: LearningPathScormCommitProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_GROUP_STUDENT')",
        ),
    ],
    denormalizationContext: ['groups' => ['learning_path_scorm_commit:write']],
)]
final class LearningPathScormCommitInput
{
    #[ApiProperty(identifier: true)]
    public ?int $lpId = null;

    #[Groups(['learning_path_scorm_commit:write'])]
    public int $itemId = 0;

    #[Groups(['learning_path_scorm_commit:write'])]
    public int $itemViewId = 0;

    #[Groups(['learning_path_scorm_commit:write'])]
    public string $version = '';

    /** @var array<string, mixed> */
    #[Groups(['learning_path_scorm_commit:write'])]
    public array $values = [];

    /** @var array<int, string> */
    #[Groups(['learning_path_scorm_commit:write'])]
    public array $changedKeys = [];

    #[Groups(['learning_path_scorm_commit:write'])]
    public bool $terminated = false;

    #[Groups(['learning_path_scorm_commit:write'])]
    public string $reason = 'commit';

    #[Groups(['learning_path_scorm_commit:write'])]
    public string $csrfToken = '';

    public function getLpId(): ?int
    {
        return $this->lpId;
    }
}
