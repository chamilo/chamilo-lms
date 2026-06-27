<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\LearningPath\LearningPathRuntimeProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/{lpId}/runtime',
            requirements: ['lpId' => '\\d+'],
            name: 'get_learning_path_runtime',
            provider: LearningPathRuntimeProvider::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_GROUP_STUDENT')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_runtime:read']],
)]
final class LearningPathRuntime
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_runtime:read'])]
    public int $lpId = 0;

    #[Groups(['learning_path_runtime:read'])]
    public string $title = '';

    #[Groups(['learning_path_runtime:read'])]
    public int $lpType = 1;

    #[Groups(['learning_path_runtime:read'])]
    public bool $runtimeSupported = true;

    #[Groups(['learning_path_runtime:read'])]
    public bool $canManage = false;

    #[Groups(['learning_path_runtime:read'])]
    public bool $canEdit = false;

    #[Groups(['learning_path_runtime:read'])]
    public string $previewImageUrl = '';

    #[Groups(['learning_path_runtime:read'])]
    public string $author = '';

    #[Groups(['learning_path_runtime:read'])]
    public string $homeUrl = '';

    #[Groups(['learning_path_runtime:read'])]
    public bool $showHome = true;

    #[Groups(['learning_path_runtime:read'])]
    public int $returnLink = 0;

    #[Groups(['learning_path_runtime:read'])]
    public string $reportingUrl = '';

    #[Groups(['learning_path_runtime:read'])]
    public bool $showReporting = false;

    #[Groups(['learning_path_runtime:read'])]
    public bool $showToolbarByDefault = false;

    #[Groups(['learning_path_runtime:read'])]
    public bool $navigationInTheMiddle = false;

    #[Groups(['learning_path_runtime:read'])]
    public bool $hideArrowNavigation = false;

    #[Groups(['learning_path_runtime:read'])]
    public string $menuLocation = 'left';

    #[Groups(['learning_path_runtime:read'])]
    public bool $accordionToc = false;

    #[Groups(['learning_path_runtime:read'])]
    public bool $hideToc = false;

    #[Groups(['learning_path_runtime:read'])]
    public string $displayMode = 'embedded';

    #[Groups(['learning_path_runtime:read'])]
    public int $progress = 0;

    #[Groups(['learning_path_runtime:read'])]
    public int $completedItems = 0;

    #[Groups(['learning_path_runtime:read'])]
    public int $totalItems = 0;

    #[Groups(['learning_path_runtime:read'])]
    public int $totalTime = 0;

    #[Groups(['learning_path_runtime:read'])]
    public string $attemptMode = 'single';

    #[Groups(['learning_path_runtime:read'])]
    public int $currentAttempt = 0;

    #[Groups(['learning_path_runtime:read'])]
    public int $currentItemAttempt = 0;

    #[Groups(['learning_path_runtime:read'])]
    public int $maxAttempts = 0;

    #[Groups(['learning_path_runtime:read'])]
    public bool $canRestart = false;

    #[Groups(['learning_path_runtime:read'])]
    public int $minimumTime = 0;

    #[Groups(['learning_path_runtime:read'])]
    public bool $minimumTimeReached = true;

    #[Groups(['learning_path_runtime:read'])]
    public int $currentItemId = 0;

    #[Groups(['learning_path_runtime:read'])]
    public int $previousItemId = 0;

    #[Groups(['learning_path_runtime:read'])]
    public int $nextItemId = 0;

    #[Groups(['learning_path_runtime:read'])]
    public string $contentUrl = '';

    #[Groups(['learning_path_runtime:read'])]
    public string $audioUrl = '';

    #[Groups(['learning_path_runtime:read'])]
    public string $audioTitle = '';

    #[Groups(['learning_path_runtime:read'])]
    public bool $audioAutoplay = false;

    #[Groups(['learning_path_runtime:read'])]
    public string $listUrl = '';

    #[Groups(['learning_path_runtime:read'])]
    public string $nextLearningPathUrl = '';

    #[Groups(['learning_path_runtime:read'])]
    public string $nextLearningPathTitle = '';

    #[Groups(['learning_path_runtime:read'])]
    public string $legacyFallbackUrl = '';

    #[Groups(['learning_path_runtime:read'])]
    public string $csrfToken = '';

    /** @var array<string, mixed> */
    #[Groups(['learning_path_runtime:read'])]
    public array $scorm = [];

    /** @var array<int, array<string, mixed>> */
    #[Groups(['learning_path_runtime:read'])]
    public array $items = [];

    public function getLpId(): int
    {
        return $this->lpId;
    }
}
