<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\LearningPath\LearningPathCategorySubscriptionProcessor;
use Chamilo\CoreBundle\State\LearningPath\LearningPathCategorySubscriptionProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_path_categories/{categoryId}/subscriptions',
            requirements: ['categoryId' => '\d+'],
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
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
            provider: LearningPathCategorySubscriptionProvider::class,
        ),
        new Patch(
            uriTemplate: '/learning_path_categories/{categoryId}/subscriptions',
            requirements: ['categoryId' => '\d+'],
            status: Response::HTTP_NO_CONTENT,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            output: false,
            read: false,
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
            processor: LearningPathCategorySubscriptionProcessor::class,
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

    /**
     * @var array<int, array{id: int, title: string}>
     */
    #[Groups(['lp_category_subscription:read'])]
    public array $users = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['lp_category_subscription:read'])]
    public array $selectedUserIds = [];

    /**
     * @var array<int, array{id: int, title: string}>
     */
    #[Groups(['lp_category_subscription:read'])]
    public array $groups = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['lp_category_subscription:read'])]
    public array $selectedGroupIds = [];

    /**
     * @var array<int, array{id: int, title: string}>
     */
    #[Groups(['lp_category_subscription:read'])]
    public array $userGroups = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['lp_category_subscription:read'])]
    public array $selectedUserGroupIds = [];

    #[Groups(['lp_category_subscription:write'])]
    public string $section = '';

    /**
     * @var array<int, int|string>
     */
    #[Groups(['lp_category_subscription:write'])]
    public array $selectedIds = [];

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }
}
