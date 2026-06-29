<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExercisePendingAttemptsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExercisePendingAttempts',
    operations: [
        new Get(
            uriTemplate: '/exercise/pending-attempts',
            openapi: new Operation(
                summary: 'Pending exercise attempts across accessible courses',
                parameters: [
                    new Parameter(name: 'courseId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'exerciseId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'filterByUser', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'status', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'questionTypeId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'startDate', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'endDate', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('ROLE_USER')",
            name: 'get_exercise_pending_attempts',
            provider: ExercisePendingAttemptsProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_pending_attempts:read']],
)]
final class ExercisePendingAttempts
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_pending_attempts:read'])]
    public string $id = 'exercise_pending_attempts';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_pending_attempts:read'])]
    public array $items = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_pending_attempts:read'])]
    public array $filters = [];

    /**
     * @var array<int, array<string, int|string>>
     */
    #[Groups(['exercise_pending_attempts:read'])]
    public array $courseOptions = [];

    /**
     * @var array<int, array<string, int|string>>
     */
    #[Groups(['exercise_pending_attempts:read'])]
    public array $exerciseOptions = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_pending_attempts:read'])]
    public array $settings = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_pending_attempts:read'])]
    public array $actionUrls = [];

    #[Groups(['exercise_pending_attempts:read'])]
    public int $totalItems = 0;

    #[Groups(['exercise_pending_attempts:read'])]
    public bool $canManage = false;

    public function getId(): string
    {
        return $this->id;
    }
}
