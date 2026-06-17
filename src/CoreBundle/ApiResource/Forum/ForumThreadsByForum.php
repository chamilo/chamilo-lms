<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\Forum;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Forum\ForumThreadCollectionStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/forums/{forumId}/threads',
            name: 'get_forum_threads_by_forum',
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'forumId',
                        in: 'path',
                        description: 'Forum id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'cid',
                        in: 'query',
                        description: 'Course id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'sid',
                        in: 'query',
                        description: 'Session id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'gid',
                        in: 'query',
                        description: 'Group id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
            provider: ForumThreadCollectionStateProvider::class,
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
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
