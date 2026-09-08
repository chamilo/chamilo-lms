<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Gradebook\GradebookGraphProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookGraph',
    operations: [
        new Get(
            uriTemplate: '/gradebook/graph.{_format}',
            openapi: new Operation(
                summary: 'Gradebook score-distribution graph data for the current course context',
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_graph',
            provider: GradebookGraphProvider::class,
            // Declared here rather than in the openapi operation: a QueryParameter
            // documents AND validates, while an openapi Parameter only documents,
            // and a twin declaration there would hide this one.
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
                    description: 'Group identifier. The group must belong to the course',
                ),
                'node' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Resource node of the course, which the provider checks against the course itself',
                    required: true,
                ),
                'categoryId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Gradebook category to chart. Defaults to the root category of the course',
                ),
                'search' => new QueryParameter(
                    schema: ['type' => 'string'],
                    description: 'Filters the learners whose scores feed the distribution, by name, login or code',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_graph:read']],
)]
final class GradebookGraph
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_graph:read'])]
    public string $id = 'gradebook_graph';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_graph:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_graph:read'])]
    public ?array $category = null;

    #[Groups(['gradebook_graph:read'])]
    public bool $enabled = false;

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_graph:read'])]
    public array $resources = [];

    public function getId(): string
    {
        return $this->id;
    }
}
