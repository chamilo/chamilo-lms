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
use Chamilo\CoreBundle\State\Forum\ForumThreadCollectionStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/forums/{forumId}/threads',
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'forumId',
                        in: 'path',
                        description: 'Forum id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'get_forum_threads_by_forum',
            provider: ForumThreadCollectionStateProvider::class,
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
        'groups' => ['forum_threads_by_forum:read'],
    ],
)]
final class ForumThreadsByForum
{
    #[ApiProperty(identifier: true)]
    #[Groups(['forum_threads_by_forum:read'])]
    public int $forumId = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['forum_threads_by_forum:read'])]
    public array $items = [];

    #[Groups(['forum_threads_by_forum:read'])]
    public int $totalItems = 0;

    public function getId(): int
    {
        return $this->forumId;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public static function fromItems(int $forumId, array $items): self
    {
        $result = new self();
        $result->forumId = $forumId;
        $result->items = $items;
        $result->totalItems = \count($items);

        return $result;
    }
}
