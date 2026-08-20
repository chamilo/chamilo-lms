<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Gradebook\GradebookAchievementActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookAchievementAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/achievements/sync',
            openapi: new Operation(
                summary: 'Synchronize Gradebook achievements for the authenticated learner',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "(is_granted('ROLE_CURRENT_COURSE_STUDENT')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT'))
                and not is_granted('ROLE_CURRENT_COURSE_TEACHER')
                and not is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                and not is_granted('ROLE_ADMIN')",
            name: 'post_gradebook_achievement_sync',
            processor: GradebookAchievementActionProcessor::class,
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
            ],
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_achievement_action:read']],
    denormalizationContext: ['groups' => ['gradebook_achievement_action:write']],
)]
final class GradebookAchievementAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_achievement_action:read'])]
    public string $id = 'gradebook_achievement_action';

    #[Groups(['gradebook_achievement_action:write'])]
    public ?int $categoryId = null;

    #[Groups(['gradebook_achievement_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_achievement_action:read'])]
    public bool $eligible = false;

    #[Groups(['gradebook_achievement_action:read'])]
    public bool $scoreRegistered = false;

    #[Groups(['gradebook_achievement_action:read'])]
    public bool $certificateGenerated = false;

    #[Groups(['gradebook_achievement_action:read'])]
    public bool $customCertificateFallback = false;

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_achievement_action:read'])]
    public ?array $certificate = null;

    #[Groups(['gradebook_achievement_action:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
