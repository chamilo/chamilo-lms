<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\Forum;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Forum\ForumSearchStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/forum/search',
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'q',
                        in: 'query',
                        description: 'Search query',
                        required: true,
                        schema: ['type' => 'string'],
                    ),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            provider: ForumSearchStateProvider::class,
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
                    description: 'Group identifier',
                ),
            ],
        ),
    ],
    normalizationContext: [
        'groups' => ['forum_search:read'],
    ],
)]
final class ForumSearchResult
{
    #[ApiProperty(identifier: true)]
    #[Groups(['forum_search:read'])]
    public string $id = 'forum_search';

    #[Groups(['forum_search:read'])]
    public string $query = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['forum_search:read'])]
    public array $items = [];

    #[Groups(['forum_search:read'])]
    public int $totalItems = 0;

    #[Groups(['forum_search:read'])]
    public int $minLength = 3;

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $result = new self();
        $result->query = (string) ($data['query'] ?? '');
        $items = $data['items'] ?? [];
        $result->items = \is_array($items) ? $items : [];
        $result->totalItems = (int) ($data['totalItems'] ?? \count($result->items));
        $result->minLength = (int) ($data['minLength'] ?? 3);

        return $result;
    }
}
