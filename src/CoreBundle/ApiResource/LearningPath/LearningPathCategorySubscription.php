<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\State\LearningPath\LearningPathCategorySubscriptionProcessor;
use Chamilo\CoreBundle\State\LearningPath\LearningPathCategorySubscriptionProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_path_categories/{categoryId}/subscriptions',
            requirements: ['categoryId' => '\\d+'],
            provider: LearningPathCategorySubscriptionProvider::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
        new Put(
            uriTemplate: '/learning_path_categories/{categoryId}/subscriptions',
            requirements: ['categoryId' => '\\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            processor: LearningPathCategorySubscriptionProcessor::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['lp_category_subscription:read']],
    denormalizationContext: ['groups' => ['lp_category_subscription:write']],
)]
final class LearningPathCategorySubscription
{
    #[ApiProperty(identifier: true)]
    #[Groups(['lp_category_subscription:read'])]
    public ?int $categoryId = null;

    #[Groups(['lp_category_subscription:read'])]
    public string $categoryTitle = '';

    #[Groups(['lp_category_subscription:read'])]
    public bool $allowUserGroups = false;

    #[Groups(['lp_category_subscription:read'])]
    public string $csrfToken = '';

    /** @var array<int, array{id: int, title: string}> */
    #[Groups(['lp_category_subscription:read'])]
    public array $users = [];

    /** @var array<int, int> */
    #[Groups(['lp_category_subscription:read'])]
    public array $selectedUserIds = [];

    /** @var array<int, array{id: int, title: string}> */
    #[Groups(['lp_category_subscription:read'])]
    public array $groups = [];

    /** @var array<int, int> */
    #[Groups(['lp_category_subscription:read'])]
    public array $selectedGroupIds = [];

    /** @var array<int, array{id: int, title: string}> */
    #[Groups(['lp_category_subscription:read'])]
    public array $userGroups = [];

    /** @var array<int, int> */
    #[Groups(['lp_category_subscription:read'])]
    public array $selectedUserGroupIds = [];

    #[Groups(['lp_category_subscription:write'])]
    public string $section = '';

    /** @var array<int, int|string> */
    #[Groups(['lp_category_subscription:write'])]
    public array $selectedIds = [];

    #[Groups(['lp_category_subscription:write'])]
    public string $csrfTokenInput = '';

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }
}
