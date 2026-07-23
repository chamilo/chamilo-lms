<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseLiveResultsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseLiveResults',
    operations: [
        new Get(
            uriTemplate: '/exercise/runtime/{exerciseId}/live-results',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise live results',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'minutes', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'status', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_live_results',
            provider: ExerciseLiveResultsProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_live_results:read']],
)]
final class ExerciseLiveResults
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_live_results:read'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_live_results:read'])]
    public string $title = '';

    #[Groups(['exercise_live_results:read'])]
    public string $description = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_live_results:read'])]
    public array $attempts = [];

    /**
     * @var array<string, int|float|string>
     */
    #[Groups(['exercise_live_results:read'])]
    public array $summary = [];

    /**
     * @var array<string, int|string>
     */
    #[Groups(['exercise_live_results:read'])]
    public array $filters = [];

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_live_results:read'])]
    public array $legacyUrls = [];

    #[Groups(['exercise_live_results:read'])]
    public int $totalItems = 0;

    #[Groups(['exercise_live_results:read'])]
    public bool $canManage = true;

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
