<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Controller\Api\LearningPathConfigurationAction;
use Chamilo\CoreBundle\State\LearningPath\LearningPathConfigurationProcessor;
use Chamilo\CoreBundle\State\LearningPath\LearningPathConfigurationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/configuration',
            provider: LearningPathConfigurationProvider::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Get(
            uriTemplate: '/learning_paths/{id}/configuration',
            requirements: ['id' => '\\d+'],
            name: 'get_learning_path_configuration',
            provider: LearningPathConfigurationProvider::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Post(
            uriTemplate: '/learning_paths/configuration',
            controller: LearningPathConfigurationAction::class,
            processor: LearningPathConfigurationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            read: false,
            deserialize: false,
        ),
        new Post(
            uriTemplate: '/learning_paths/{id}/configuration',
            requirements: ['id' => '\\d+'],
            name: 'update_learning_path_configuration',
            controller: LearningPathConfigurationAction::class,
            processor: LearningPathConfigurationProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            read: false,
            deserialize: false,
        ),
    ],
    normalizationContext: ['groups' => ['lp_configuration:read']],
)]
final class LearningPathConfiguration
{
    #[Groups(['lp_configuration:read'])]
    public ?int $id = null;

    #[Groups(['lp_configuration:read'])]
    public bool $isEdit = false;

    #[Groups(['lp_configuration:read'])]
    public string $title = '';

    #[Groups(['lp_configuration:read'])]
    public ?int $categoryId = null;

    #[Groups(['lp_configuration:read'])]
    public string $language = '';

    #[Groups(['lp_configuration:read'])]
    public bool $hideTocFrame = false;

    #[Groups(['lp_configuration:read'])]
    public string $defaultViewMode = 'embedded';

    #[Groups(['lp_configuration:read'])]
    public string $theme = '';

    #[Groups(['lp_configuration:read'])]
    public string $author = '';

    #[Groups(['lp_configuration:read'])]
    public bool $searchIndexEnabled = true;

    #[Groups(['lp_configuration:read'])]
    public int $prerequisiteId = 0;

    #[Groups(['lp_configuration:read'])]
    public int $accumulateWorkTime = 0;

    #[Groups(['lp_configuration:read'])]
    public int $nextLpId = 0;

    #[Groups(['lp_configuration:read'])]
    public bool $activateStartDate = true;

    #[Groups(['lp_configuration:read'])]
    public ?string $publishedOn = null;

    #[Groups(['lp_configuration:read'])]
    public bool $activateEndDate = false;

    #[Groups(['lp_configuration:read'])]
    public ?string $expiredOn = null;

    #[Groups(['lp_configuration:read'])]
    public bool $useMaxScore = true;

    #[Groups(['lp_configuration:read'])]
    public bool $subscribeUsers = false;

    #[Groups(['lp_configuration:read'])]
    public bool $accumulateScormTime = false;

    #[Groups(['lp_configuration:read'])]
    public bool $useScoreAsProgress = false;

    #[Groups(['lp_configuration:read'])]
    public string $icon = '';

    #[Groups(['lp_configuration:read'])]
    public bool $titleAsHtml = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showLanguage = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showTheme = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showSearchIndex = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showMinimumTime = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showFlow = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showUseMaxScore = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showSubscribeUsers = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showScoreAsProgress = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showIcon = false;

    #[Groups(['lp_configuration:read'])]
    public bool $showSkills = false;

    #[Groups(['lp_configuration:read'])]
    public ?string $imageUrl = null;

    /** @var array<int, array{label:string, value:int|null}> */
    #[Groups(['lp_configuration:read'])]
    public array $categoryOptions = [];

    /** @var array<int, array{label:string, value:string}> */
    #[Groups(['lp_configuration:read'])]
    public array $languageOptions = [];

    /** @var array<int, array{label:string, value:string}> */
    #[Groups(['lp_configuration:read'])]
    public array $themeOptions = [];

    /** @var array<int, array{label:string, value:int}> */
    #[Groups(['lp_configuration:read'])]
    public array $prerequisiteOptions = [];

    /** @var array<int, array{label:string, value:int}> */
    #[Groups(['lp_configuration:read'])]
    public array $nextLpOptions = [];

    /** @var array<int, array{label:string, value:string}> */
    #[Groups(['lp_configuration:read'])]
    public array $iconOptions = [];

    /** @var array<int, array<string, mixed>> */
    #[Groups(['lp_configuration:read'])]
    public array $extraFields = [];

    /** @var array<int, array{label:string, value:int}> */
    #[Groups(['lp_configuration:read'])]
    public array $skillOptions = [];

    /** @var int[] */
    #[Groups(['lp_configuration:read'])]
    public array $skillIds = [];

    #[Groups(['lp_configuration:read'])]
    public string $csrfToken = '';
}
