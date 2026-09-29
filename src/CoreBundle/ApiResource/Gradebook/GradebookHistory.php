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
use Chamilo\CoreBundle\State\Gradebook\GradebookHistoryProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookHistory',
    operations: [
        new Get(
            uriTemplate: '/gradebook/history',
            openapi: new Operation(
                summary: 'Gradebook evaluation or online-activity change history in the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'kind', in: 'query', required: true, schema: ['type' => 'string']),
                    new Parameter(name: 'itemId', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_history',
            provider: GradebookHistoryProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_history:read']],
)]
final class GradebookHistory
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_history:read'])]
    public string $id = 'gradebook_history';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_history:read'])]
    public array $context = [];

    #[Groups(['gradebook_history:read'])]
    public string $kind = '';

    #[Groups(['gradebook_history:read'])]
    public int $itemId = 0;

    #[Groups(['gradebook_history:read'])]
    public string $itemTitle = '';

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_history:read'])]
    public array $rows = [];

    public function getId(): string
    {
        return $this->id;
    }
}
