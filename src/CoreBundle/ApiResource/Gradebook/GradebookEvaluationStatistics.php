<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Gradebook\GradebookEvaluationStatisticsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookEvaluationStatistics',
    operations: [
        new Get(
            uriTemplate: '/gradebook/evaluation-statistics',
            openapi: new Operation(
                summary: 'Read Gradebook statistics for a manual evaluation',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'evaluationId', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_evaluation_statistics',
            provider: GradebookEvaluationStatisticsProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_evaluation_statistics:read']],
)]
final class GradebookEvaluationStatistics
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_evaluation_statistics:read'])]
    public string $id = 'gradebook_evaluation_statistics';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_evaluation_statistics:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_evaluation_statistics:read'])]
    public ?array $evaluation = null;

    #[Groups(['gradebook_evaluation_statistics:read'])]
    public bool $customEnabled = false;

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_evaluation_statistics:read'])]
    public array $rows = [];

    #[Groups(['gradebook_evaluation_statistics:read'])]
    public int $resultCount = 0;

    public function getId(): string
    {
        return $this->id;
    }
}
