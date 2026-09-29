<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Exercise\ExerciseGlobalReportProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseGlobalReport',
    operations: [
        new Get(
            uriTemplate: '/exercise/global-report',
            openapi: new Operation(
                summary: 'Exercise global report export configuration',
            ),
            security: "is_granted('ROLE_ADMIN')",
            name: 'get_exercise_global_report',
            provider: ExerciseGlobalReportProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course to report on; the whole platform when absent',
                ),
                'courseId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Legacy alias of cid',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['exercise_global_report:read']],
)]
final class ExerciseGlobalReport
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_global_report:read'])]
    public string $id = 'exercise_global_report';

    /**
     * @var array<int, array<string, int|string>>
     */
    #[Groups(['exercise_global_report:read'])]
    public array $courseOptions = [];

    #[Groups(['exercise_global_report:read'])]
    public int $selectedCourseId = 0;

    /**
     * @var array<string, string>
     */
    #[Groups(['exercise_global_report:read'])]
    public array $actionUrls = [];

    #[Groups(['exercise_global_report:read'])]
    public bool $canExport = false;

    public function getId(): string
    {
        return $this->id;
    }
}
