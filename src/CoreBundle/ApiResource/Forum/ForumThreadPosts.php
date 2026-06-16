<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\Forum;

/**
 * Plain DTO used by ForumThreadPostsStateProvider.
 *
 * This class intentionally has no ApiResource attribute because the route
 * /api/forum_threads/{threadId}/posts is currently served by the dedicated
 * controller while the provider conversion is fixed safely.
 */
final class ForumThreadPosts
{
    public int $threadId = 0;

    /**
     * @var array<string, mixed>
     */
    public array $forum = [];

    /**
     * @var array<string, mixed>
     */
    public array $thread = [];

    public bool $canReply = false;

    public bool $canManageThread = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $posts = [];

    public function getId(): int
    {
        return $this->threadId;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(int $threadId, array $data): self
    {
        $result = new self();
        $result->threadId = $threadId;

        $forum = $data['forum'] ?? [];
        $result->forum = \is_array($forum) ? $forum : [];

        $thread = $data['thread'] ?? [];
        $result->thread = \is_array($thread) ? $thread : [];

        $result->canReply = (bool) ($data['canReply'] ?? false);
        $result->canManageThread = (bool) ($data['canManageThread'] ?? false);

        $posts = $data['posts'] ?? [];
        $result->posts = \is_array($posts) ? $posts : [];

        return $result;
    }
}
