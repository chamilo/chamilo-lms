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
use Chamilo\CoreBundle\State\Gradebook\GradebookLinkOptionsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookLinkOptions',
    operations: [
        new Get(
            uriTemplate: '/gradebook/link-options',
            openapi: new Operation(
                summary: 'Available resources for a Gradebook online activity link',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'linkId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'refId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_link_options',
            provider: GradebookLinkOptionsProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_link_options:read']],
)]
final class GradebookLinkOptions
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_link_options:read'])]
    public string $id = 'gradebook_link_options';

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_link_options:read'])]
    public array $types = [];

    /**
     * @var list<array{value: int, label: string}>
     */
    #[Groups(['gradebook_link_options:read'])]
    public array $categories = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_link_options:read'])]
    public ?array $link = null;

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_link_options:read'])]
    public array $context = [];

    #[Groups(['gradebook_link_options:read'])]
    public string $csrfToken = '';

    public function getId(): string
    {
        return $this->id;
    }
}
