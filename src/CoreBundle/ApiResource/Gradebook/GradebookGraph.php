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
use Chamilo\CoreBundle\State\Gradebook\GradebookGraphProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookGraph',
    operations: [
        new Get(
            uriTemplate: '/gradebook/graph',
            openapi: new Operation(
                summary: 'Gradebook score-distribution graph data for the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_graph',
            provider: GradebookGraphProvider::class,
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
