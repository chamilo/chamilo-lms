<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseOverviewProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseOverview',
    operations: [
        new Get(
            uriTemplate: '/exercise/overview/{exerciseId}',
            requirements: ['exerciseId' => '\\d+'],
            openapi: new Operation(
                summary: 'Exercise overview for a course',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_overview',
            provider: ExerciseOverviewProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_overview:read']],
)]
final class ExerciseOverview
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_overview:read'])]
    public ?int $exerciseId = null;

    #[Groups(['exercise_overview:read'])]
    public string $title = '';

    #[Groups(['exercise_overview:read'])]
    public string $description = '';

    #[Groups(['exercise_overview:read'])]
    public bool $visible = false;

    #[Groups(['exercise_overview:read'])]
    public string $categoryTitle = '';

    #[Groups(['exercise_overview:read'])]
    public int $questionCount = 0;

    #[Groups(['exercise_overview:read'])]
    public int $attemptCount = 0;

    #[Groups(['exercise_overview:read'])]
    public float $averageScore = 0.0;

    #[Groups(['exercise_overview:read'])]
    public float $maxScore = 0.0;

    #[Groups(['exercise_overview:read'])]
    public int $passPercentage = 0;

    #[Groups(['exercise_overview:read'])]
    public ?string $startTime = null;

    #[Groups(['exercise_overview:read'])]
    public ?string $endTime = null;

    #[Groups(['exercise_overview:read'])]
    public ?int $duration = null;

    #[Groups(['exercise_overview:read'])]
    public int $maxAttempt = 0;

    #[Groups(['exercise_overview:read'])]
    public int $feedbackType = 0;

    #[Groups(['exercise_overview:read'])]
    public int $resultsDisabled = 0;

    #[Groups(['exercise_overview:read'])]
    public bool $randomAnswers = false;

    #[Groups(['exercise_overview:read'])]
    public int $random = 0;

    #[Groups(['exercise_overview:read'])]
    public int $randomByCategory = 0;

    #[Groups(['exercise_overview:read'])]
    public bool $canManage = false;

    #[Groups(['exercise_overview:read'])]
    public bool $canOpen = false;

    #[Groups(['exercise_overview:read'])]
    public bool $canReport = false;

    #[Groups(['exercise_overview:read'])]
    public string $availabilityStatus = 'open';

    #[Groups(['exercise_overview:read'])]
    public int $currentUserAttemptCount = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_overview:read'])]
    public array $currentUserAttempts = [];

    #[Groups(['exercise_overview:read'])]
    public bool $showAttemptsTable = false;

    #[Groups(['exercise_overview:read'])]
    public bool $showScoreColumn = false;

    #[Groups(['exercise_overview:read'])]
    public bool $showDetailsColumn = false;

    #[Groups(['exercise_overview:read'])]
    public bool $attemptLimitReached = false;

    #[Groups(['exercise_overview:read'])]
    public bool $canStart = false;

    #[Groups(['exercise_overview:read'])]
    public string $startButtonLabel = 'Start test';

    #[Groups(['exercise_overview:read'])]
    public string $notice = '';

    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }
}
